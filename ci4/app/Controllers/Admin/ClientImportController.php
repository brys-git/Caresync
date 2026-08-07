<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClientImportBatchModel;
use App\Models\ClientImportRecordModel;
use App\Services\Import\ClientImportCommitService;
use App\Services\Import\ClientImportParseService;
use App\Services\Import\ClientImportReviewService;
use App\Services\Import\CommitException;
use App\Services\Import\ParseException;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Client Record Document Import — system-wide (role: 1).
 * Same flow as the branch-admin version but sees every branch and can choose the
 * branch on upload.
 */
class ClientImportController extends BaseController
{
    private ClientImportBatchModel $batchModel;
    private ClientImportRecordModel $recordModel;
    private ClientImportParseService $parseService;
    private ClientImportReviewService $reviewService;
    private ClientImportCommitService $commitService;

    public function __construct()
    {
        $this->batchModel = new ClientImportBatchModel();
        $this->recordModel = new ClientImportRecordModel();
        $this->parseService = new ClientImportParseService();
        $this->reviewService = new ClientImportReviewService();
        $this->commitService = new ClientImportCommitService();
    }

    private function branches(): array
    {
        return db_connect()->table('branches')
            ->select('branch_id, branch_name')
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function index(): string
    {
        $this->ensureAdminAccess();

        $branchId = (int) $this->request->getGet('branch_id');
        $builder = $this->batchModel->orderBy('created_at', 'DESC')->limit(15);
        if ($branchId > 0) {
            $builder->where('branch_id', $branchId);
        }

        return view('client_import/import_home', [
            'role_layout' => 'layouts/admin',
            'batches' => $this->decorateBatches($builder->findAll()),
            'branches' => $this->branches(),
            'filter_branch_id' => $branchId,
        ]);
    }

    public function upload()
    {
        $this->ensureAdminAccess();

        $branchId = (int) $this->request->getPost('branch_id');
        if ($branchId <= 0) {
            return redirect()->back()->with('error', 'Please choose a branch for this import.');
        }

        $file = $this->request->getFile('import_file');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Please choose a valid document to import.');
        }

        $ext = strtolower((string) $file->getClientExtension());
        if (! in_array($ext, ['docx', 'csv'], true)) {
            return redirect()->back()->with('error', 'Only .docx and .csv files are supported.');
        }

        // Read metadata BEFORE move() — after the temp file is moved,
        // getMimeType()/getSize() can no longer read it (throws finfo error).
        $originalName = (string) $file->getClientName();
        $mimeType = (string) $file->getMimeType();
        $fileSize = (int) $file->getSize();

        $uploadDir = WRITEPATH . 'client_imports';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $storedName = $file->getRandomName();
        $file->move($uploadDir, $storedName);
        $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
        $storedPath = 'client_imports/' . $storedName;

        try {
            $batch = $this->parseService->parseAndStage($absolutePath, [
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'format' => $ext,
            ], $branchId, (int) session('user_id'), $storedPath);
        } catch (ParseException $e) {
            @unlink($absolutePath);

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            @unlink($absolutePath);
            log_message('error', 'Client import parse failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Could not read the document. Please check the file and try again.');
        }

        return redirect()->to('/admin/client-import/review/' . (int) $batch['import_batch_id'])
            ->with('success', 'Document parsed — ' . (int) $batch['total_records'] . ' records staged for review.');
    }

    public function review(int $batchId): string
    {
        $this->ensureAdminAccess();

        $batch = $this->loadBatch($batchId);
        $records = $this->loadRecords($batchId);
        $counts = $this->summarize($records);
        $packages = db_connect()->table('packages')
            ->select('package_id, package_name, base_price')
            ->where('is_available', 1)
            ->orderBy('package_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('client_import/review', [
            'role_layout' => 'layouts/admin',
            'batch' => $batch,
            'records' => $records,
            'counts' => $counts,
            'packages' => $packages,
            'commit_errors' => session('import_commit_errors') ? json_decode((string) session('import_commit_errors'), true) : [],
        ]);
    }

    public function saveRecord(int $recordId)
    {
        $this->ensureAdminAccess();

        if (! $this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $record = $this->loadRecord($recordId);
        $inBatch = $this->batchSiblings($record);

        $updated = $this->reviewService->applyEdit($record, $this->request->getPost(), $inBatch);
        $this->recordModel->update($recordId, $updated);
        $refreshed = $this->loadRecord($recordId);

        return $this->response->setJSON([
            'ok' => true,
            'record' => $refreshed,
            'status' => $refreshed['record_status'],
            'temp_username' => $refreshed['temp_username'],
            'temp_email' => $refreshed['temp_email'],
            'summary' => $this->summaryFor((int) $record['import_batch_id']),
        ]);
    }

    public function decideRecord(int $recordId)
    {
        $this->ensureAdminAccess();

        $record = $this->loadRecord($recordId);

        $decision = (string) $this->request->getPost('decision');
        if (! in_array($decision, ['pending', 'create_new', 'link_existing', 'skip'], true)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Invalid decision.']);
        }

        $update = ['admin_decision' => $decision];
        if ($decision === 'link_existing') {
            $targetId = (int) $this->request->getPost('linked_plan_holder_id');
            $target = db_connect()->table('plan_holders')
                ->select('plan_holder_id, user_id')
                ->where('plan_holder_id', $targetId)
                ->get()
                ->getRowArray();

            if (! $target) {
                return $this->response->setJSON(['ok' => false, 'error' => 'The linked client no longer exists.']);
            }
            $update['linked_plan_holder_id'] = $targetId;
            $update['linked_user_id'] = (int) $target['user_id'];
        } else {
            $update['linked_plan_holder_id'] = null;
            $update['linked_user_id'] = null;
        }

        $this->recordModel->update($recordId, $update);

        return $this->response->setJSON([
            'ok' => true,
            'decision' => $decision,
            'name' => trim((string) $record['first_name'] . ' ' . (string) $record['last_name']),
            'summary' => $this->summaryFor((int) $record['import_batch_id']),
        ]);
    }

    public function commit(int $batchId)
    {
        $this->ensureAdminAccess();

        $this->loadBatch($batchId);

        try {
            $result = $this->commitService->commitBatch($batchId, (int) session('user_id'), 0);
        } catch (CommitException $e) {
            session()->setFlashdata('import_commit_errors', json_encode([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], JSON_UNESCAPED_UNICODE));

            return redirect()->to('/admin/client-import/review/' . $batchId)->with('error', $e->getMessage());
        }

        session()->setFlashdata('import_commit_success', json_encode([
            'created' => $result['created'],
            'linked' => $result['linked'],
            'skipped' => $result['skipped'],
        ], JSON_UNESCAPED_UNICODE));

        return redirect()->to('/admin/client-import/history/' . $batchId)
            ->with('success', 'Import committed — ' . $result['created'] . ' created, ' . $result['linked'] . ' linked, ' . $result['skipped'] . ' skipped.');
    }

    public function history(): string
    {
        $this->ensureAdminAccess();

        $branchId = (int) $this->request->getGet('branch_id');
        $builder = $this->batchModel->orderBy('created_at', 'DESC');
        if ($branchId > 0) {
            $builder->where('branch_id', $branchId);
        }

        return view('client_import/history', [
            'role_layout' => 'layouts/admin',
            'batches' => $this->decorateBatches($builder->findAll()),
            'branches' => $this->branches(),
            'filter_branch_id' => $branchId,
        ]);
    }

    public function batchDetail(int $batchId): string
    {
        $this->ensureAdminAccess();

        $batch = $this->loadBatch($batchId);
        $records = $this->loadRecords($batchId);
        $counts = $this->summarize($records);

        return view('client_import/batch_detail', [
            'role_layout' => 'layouts/admin',
            'batch' => $batch,
            'records' => $records,
            'counts' => $counts,
            'commit_success' => session('import_commit_success') ? json_decode((string) session('import_commit_success'), true) : [],
        ]);
    }

    public function download(int $batchId)
    {
        $this->ensureAdminAccess();

        $batch = $this->loadBatch($batchId);
        $relativePath = (string) ($batch['file_path'] ?? '');

        $fullPath = str_starts_with($relativePath, 'client_imports/')
            ? WRITEPATH . $relativePath
            : $relativePath;

        if (! is_file($fullPath)) {
            return redirect()->back()->with('error', 'The original file is no longer available.');
        }

        return $this->response->download($fullPath, null)->setFileName((string) ($batch['original_name'] ?? basename($fullPath)));
    }

    public function clearCredentials(int $recordId)
    {
        $this->ensureAdminAccess();

        $record = $this->loadRecord($recordId);
        $this->recordModel->update($recordId, [
            'temp_password_plain' => null,
            'temp_password_hash' => null,
        ]);

        return $this->response->setJSON(['ok' => true, 'name' => trim((string) $record['first_name'] . ' ' . (string) $record['last_name'])]);
    }

    public function templateCsv()
    {
        $this->ensureAdminAccess();

        $header = ['record_no', 'coordinator', 'application_date_raw', 'plan_holder_name', 'date_of_birth_raw', 'address_raw', 'beneficiary_name', 'beneficiary_birthday', 'beneficiary_relation'];
        $sample = [
            '1', 'Maria Santos', '01-15-2024', 'Juan Dela Cruz', '02-20-1980', '123 Mabini St., Brgy. San Isidro, Calapan City, Oriental Mindoro', 'Maria Dela Cruz', '05-10-2005', 'Daughter',
            '1', 'Maria Santos', '', '', '', '', 'Jose Dela Cruz', '03-03-2008', 'Son',
        ];

        $lines = [implode(',', array_map([$this, 'csvCell'], $header))];
        for ($i = 0, $n = count($sample); $i < $n; $i += 9) {
            $lines[] = implode(',', array_map([$this, 'csvCell'], array_slice($sample, $i, 9)));
        }

        return $this->response->setContentType('text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="client-import-template.csv"')
            ->setBody(implode("\r\n", $lines) . "\r\n");
    }

    private function csvCell(string $value): string
    {
        if (strpbrk($value, ",\"\r\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function loadBatch(int $batchId): array
    {
        $batch = $this->batchModel->find($batchId);
        if (! $batch) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $batch;
    }

    private function loadRecord(int $recordId): array
    {
        $record = $this->recordModel->find($recordId);
        if (! $record) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->loadBatch((int) $record['import_batch_id']);

        return $this->decorateRecord($record);
    }

    /** @return array<int, array<string, mixed>> */
    private function loadRecords(int $batchId): array
    {
        $rows = db_connect()->table('client_import_records')
            ->where('import_batch_id', $batchId)
            ->orderBy('source_index', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(fn ($r) => $this->decorateRecord($r), $rows);
    }

    /** @param array<string, mixed> $record @return array<string, mixed> */
    private function decorateRecord(array $record): array
    {
        $record['validation_errors'] = json_decode((string) ($record['validation_errors_json'] ?? '[]'), true) ?: [];
        $record['match'] = json_decode((string) ($record['match_candidates_json'] ?? '{}'), true) ?: ['candidates' => [], 'status' => $record['record_status'] ?? 'ready'];
        $record['beneficiaries'] = json_decode((string) ($record['beneficiaries_json'] ?? '[]'), true) ?: [];
        $mapped = json_decode((string) ($record['mapped_data'] ?? '{}'), true) ?: [];
        $record['mapped'] = $mapped;
        $record['optional'] = is_array($mapped['optional'] ?? null) ? $mapped['optional'] : [];
        $record['plan'] = is_array($mapped['plan'] ?? null) ? $mapped['plan'] : [];
        $record['address_province'] = (string) ($mapped['address_province'] ?? '');

        $record['has_blocking_errors'] = false;
        foreach ($record['validation_errors'] as $issue) {
            if (($issue['level'] ?? '') === 'error') {
                $record['has_blocking_errors'] = true;
                break;
            }
        }

        return $record;
    }

    /** @return array<int, array<string, mixed>> */
    private function batchSiblings(array $record): array
    {
        $rows = db_connect()->table('client_import_records')
            ->select('first_name, middle_name, last_name, date_of_birth, source_index, beneficiaries_json')
            ->where('import_batch_id', (int) $record['import_batch_id'])
            ->where('import_record_id !=', (int) $record['import_record_id'])
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['contact_number'] = '';
        }

        return $rows;
    }

    /** @param array<int, array<string, mixed>> $records @return array<string, mixed> */
    private function summarize(array $records): array
    {
        $counts = ['ready' => 0, 'needs_attention' => 0, 'duplicate' => 0, 'skip' => 0];
        $decided = 0;
        $blocking = 0;

        foreach ($records as $record) {
            $status = (string) ($record['record_status'] ?? 'needs_attention');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if ((string) ($record['admin_decision'] ?? 'pending') !== 'pending') {
                $decided++;
            } else {
                $blocking++;
            }
            if ((string) ($record['admin_decision'] ?? 'pending') !== 'skip' && ! empty($record['has_blocking_errors'])) {
                $blocking++;
            }
        }

        $total = count($records);

        return [
            'total' => $total,
            'ready' => $counts['ready'],
            'needs_attention' => $counts['needs_attention'],
            'duplicate' => $counts['duplicate'],
            'skip' => $counts['skip'],
            'decided' => $decided,
            'unresolved' => $total - $decided,
            'blocking' => $blocking,
            'can_commit' => $total > 0 && $blocking === 0,
        ];
    }

    /** @return array<string, mixed> */
    private function summaryFor(int $batchId): array
    {
        return $this->summarize($this->loadRecords($batchId));
    }

    /** @param array<int, array<string, mixed>> $batches @return array<int, array<string, mixed>> */
    private function decorateBatches(array $batches): array
    {
        $users = [];
        $db = db_connect();

        foreach ($batches as &$batch) {
            $batch['branch_name'] = '-';
            $branchId = (int) ($batch['branch_id'] ?? 0);
            if ($branchId > 0) {
                $row = $db->table('branches')->select('branch_name')->where('branch_id', $branchId)->get()->getRowArray();
                $batch['branch_name'] = $row ? (string) $row['branch_name'] : '-';
            }

            foreach (['uploaded_by' => 'uploader_name', 'committed_by' => 'committer_name'] as $key => $out) {
                $batch[$out] = '-';
                $userId = (int) ($batch[$key] ?? 0);
                if ($userId > 0) {
                    if (! isset($users[$userId])) {
                        $row = $db->table('users')->select('first_name, last_name')->where('user_id', $userId)->get()->getRowArray();
                        $users[$userId] = $row ? trim((string) $row['first_name'] . ' ' . (string) $row['last_name']) : '-';
                    }
                    $batch[$out] = $users[$userId];
                }
            }
        }

        return $batches;
    }

    private function ensureAdminAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 1 && $roleName !== 'admin') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
