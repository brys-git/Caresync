<?php

namespace App\Services\Import;

use App\Models\ClientImportBatchModel;
use App\Models\ClientImportRecordModel;
use App\Services\ActivityLogService;
use App\Services\Import\Normalizers\ClientAddressNormalizer;
use App\Services\Import\Normalizers\ClientDateNormalizer;
use App\Services\Import\Normalizers\ClientNameKeyGenerator;
use App\Services\Import\Normalizers\ClientNameNormalizer;
use App\Services\Import\Parsers\DocumentParserFactory;

/**
 * Runs the whole staging pipeline for one uploaded document:
 *
 *   parse (raw records) -> normalize/map -> generate temp username/email
 *   -> match duplicates (live DB + same batch) -> insert client_import_records.
 *
 * Nothing is written to the live users/plan_holders/beneficiaries/plans tables.
 */
class ClientImportParseService
{
    private ClientImportBatchModel $batchModel;
    private ClientImportRecordModel $recordModel;
    private ClientMatcherService $matcher;
    private ImportCredentialService $credentials;

    public function __construct()
    {
        $this->batchModel = new ClientImportBatchModel();
        $this->recordModel = new ClientImportRecordModel();
        $this->matcher = new ClientMatcherService();
        $this->credentials = new ImportCredentialService();
    }

    /**
     * @param array{original_name: string, mime_type: string, file_size: int, format: string} $fileMeta
     * @param string|null $storedPath path relative to WRITEPATH to persist for later download
     *                                (defaults to the parse path when omitted)
     *
     * @return array<string, mixed> the batch row
     *
     * @throws ParseException
     */
    public function parseAndStage(string $filePath, array $fileMeta, int $branchId, int $uploadedBy, ?string $storedPath = null): array
    {
        $batch = $this->batchModel->insert([
            'branch_id' => $branchId,
            'uploaded_by' => $uploadedBy,
            'filename' => basename($filePath),
            'original_name' => $fileMeta['original_name'] ?? basename($filePath),
            'mime_type' => $fileMeta['mime_type'] ?? null,
            'file_path' => str_replace('\\', '/', $storedPath ?? $filePath),
            'file_size' => $fileMeta['file_size'] ?? null,
            'format' => $fileMeta['format'] ?? 'docx',
            'parse_status' => 'processing',
            'status' => 'staged',
        ]);

        try {
            $parser = DocumentParserFactory::create($fileMeta['format'] ?? 'docx');
            $parsed = $parser->parse($filePath);
            $records = $parsed['records'];
            $warnings = $parsed['warnings'];

            $stagedSoFar = [];
            $takenUsernames = [];
            $takenEmails = [];

            foreach ($records as $raw) {
                $staged = $this->mapRecord($raw, $takenUsernames, $takenEmails, $stagedSoFar);
                $staged['import_batch_id'] = $batch;
                $this->recordModel->insert($staged);
                $staged['import_record_id'] = (int) $this->recordModel->insertID();
                $stagedSoFar[] = $staged;
            }

            // Cross-reference pass: "plan holder is also a beneficiary of another
            // record" notes across the whole batch, independent of staging order.
            foreach ($this->matcher->annotateInformational($stagedSoFar) as $recordId => $notes) {
                $row = $this->recordModel->find($recordId);
                $match = json_decode((string) ($row['match_candidates_json'] ?? '{}'), true);
                if (! is_array($match)) {
                    $match = ['candidates' => [], 'status' => 'ready'];
                }
                $match['informational'] = $notes;
                $this->recordModel->update($recordId, [
                    'match_candidates_json' => json_encode($match, JSON_UNESCAPED_UNICODE),
                ]);
            }

            $counts = $this->countStatuses($records, $stagedSoFar);

            $this->batchModel->update($batch, [
                'parse_status' => 'parsed',
                'total_records' => $counts['total'],
                'ready_count' => $counts['ready'],
                'needs_attention_count' => $counts['needs_attention'],
                'duplicate_count' => $counts['duplicate'],
                'skipped_count' => $counts['skipped'],
                'raw_text' => $this->collectRawText($records),
                'summary_json' => json_encode([
                    'warnings' => $warnings,
                    'generated_at' => date('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_UNICODE),
            ]);

            (new ActivityLogService())->log(
                $uploadedBy,
                'uploaded',
                'client_import',
                (int) $batch,
                'Uploaded and parsed "' . ($fileMeta['original_name'] ?? basename($filePath)) . '" — '
                    . $counts['total'] . ' records staged for review.'
            );

            return $this->batchModel->find($batch);
        } catch (\Throwable $e) {
            $this->batchModel->update($batch, [
                'parse_status' => 'failed',
                'parse_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Turn one raw record into the persisted client_import_records row.
     *
     * @param array<int|string, mixed> $raw
     * @param array<int, string> $takenUsernames
     * @param array<int, string> $takenEmails
     * @param array<int, array<string, mixed>> $stagedSoFar
     *
     * @return array<string, mixed>
     */
    private function mapRecord(array $raw, array &$takenUsernames, array &$takenEmails, array $stagedSoFar): array
    {
        $errors = [];   // blocking -> record needs_attention
        $warnings = []; // advisory

        // ---- Name ----
        $nameParts = ClientNameNormalizer::splitFullName((string) ($raw['plan_holder_name'] ?? ''));
        if ($nameParts['first_name'] === '') {
            $errors[] = ['field' => 'first_name', 'level' => 'error', 'message' => 'First name is missing.'];
        }
        if ($nameParts['last_name'] === '') {
            $errors[] = ['field' => 'last_name', 'level' => 'error', 'message' => 'Last name is missing (required to create an account).'];
        }

        // ---- Dates ----
        $dobResult = ClientDateNormalizer::normalizeDate((string) ($raw['date_of_birth_raw'] ?? ''));
        if (! $dobResult['ok'] && ($raw['date_of_birth_raw'] ?? '') !== '') {
            $errors[] = ['field' => 'date_of_birth', 'level' => 'error', 'message' => $dobResult['warning']];
        } elseif ($dobResult['warning'] !== null) {
            $warnings[] = $dobResult['warning'];
        }

        $appResult = ClientDateNormalizer::normalizeDate((string) ($raw['application_date_raw'] ?? ''));
        if (! $appResult['ok'] && ($raw['application_date_raw'] ?? '') !== '') {
            $warnings[] = $appResult['warning'];
        }

        // ---- Address ----
        $addressRaw = (string) ($raw['address_raw'] ?? '');
        $address = ClientAddressNormalizer::splitAddress($addressRaw);
        if (! $address['parsed']) {
            $warnings[] = 'Address could not be split automatically — please review.';
        }

        // ---- Beneficiaries ----
        $beneficiaries = [];
        foreach (($raw['beneficiaries'] ?? []) as $beneficiary) {
            $ben = $this->mapBeneficiary($beneficiary, $errors);
            if ($ben !== null) {
                $beneficiaries[] = $ben;
            }
        }
        if ($beneficiaries === []) {
            $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'No beneficiaries were captured — at least one is required.'];
        }

        // ---- Optional / manual fields (not present in the source document) ----
        $mappedData = [
            'address_province' => $address['address_province'],
            'optional' => [
                'contact_number' => '',
                'email' => '',
                'gender' => '',
                'civil_status' => '',
                'citizenship' => '',
                'place_of_birth' => '',
                'senior_citizen_id' => '',
                'id_control_no' => '',
                'emergency_contact_name' => '',
                'emergency_contact_number' => '',
                'emergency_contact_address' => '',
            ],
            'plan' => [
                'plan_status' => 'active',
                'monthly_fee' => 240.0,
                'package_id' => 1,
            ],
        ];

        // ---- Temp credentials (username + email pre-generated at staging) ----
        $tempUsername = $this->credentials->generateUsername($nameParts['first_name'], $nameParts['last_name'], $takenUsernames);
        $takenUsernames[] = $tempUsername;
        $tempEmail = $this->credentials->generateEmail($tempUsername, $takenEmails);
        $takenEmails[] = $tempEmail;

        // ---- Duplicate matching ----
        $match = $this->matcher->matchRecord([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'middle_name' => $nameParts['middle_name'],
            'date_of_birth' => $dobResult['value'] ?? '',
            'contact_number' => '',
            'beneficiaries' => $beneficiaries,
        ], $stagedSoFar);

        // ---- Record status precedence ----
        if ($errors !== []) {
            $status = 'needs_attention';
        } else {
            $status = $match['status'];
        }

        $keys = ClientNameKeyGenerator::buildKeys(
            $nameParts['first_name'],
            $nameParts['last_name'],
            $dobResult['value'],
            $nameParts['name_extension']
        );

        $validationIssues = array_merge($errors, array_map(static fn ($w) => [
            'field' => 'general',
            'level' => 'warning',
            'message' => $w,
        ], $warnings));

        return [
            'source_index' => (int) ($raw['source_index'] ?? 0),
            'coordinator' => (string) ($raw['coordinator'] ?? '') ?: null,
            'application_date' => $appResult['value'],
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'name_extension' => $nameParts['name_extension'],
            'date_of_birth' => $dobResult['value'],
            'address_raw' => $addressRaw,
            'address_no' => $address['address_no'],
            'address_street' => $address['address_street'],
            'address_barangay' => $address['address_barangay'],
            'address_city' => $address['address_city'],
            'mapped_data' => json_encode($mappedData, JSON_UNESCAPED_UNICODE),
            'beneficiaries_json' => json_encode($beneficiaries, JSON_UNESCAPED_UNICODE),
            'extracted_text' => (string) ($raw['extracted_text'] ?? ''),
            'validation_errors_json' => json_encode($validationIssues, JSON_UNESCAPED_UNICODE),
            'match_candidates_json' => json_encode([
                'candidates' => $match['candidates'],
                'status' => $match['status'],
                'informational' => $match['informational'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
            'duplicate_key' => $keys['exact'] ?: null,
            'record_status' => $status,
            'admin_decision' => 'pending',
            'temp_username' => $tempUsername,
            'temp_email' => $tempEmail,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapBeneficiary(array $beneficiary, array &$errors): ?array
    {
        $name = (string) ($beneficiary['name'] ?? '');
        $parts = ClientNameNormalizer::splitFullName($name);

        if ($name === '') {
            $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'A beneficiary has no name.'];

            return null;
        }
        if ($parts['last_name'] === '') {
            $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'Beneficiary "' . $name . '" is missing a last name.'];
        }

        $relation = ClientNameNormalizer::normalizeRelation((string) ($beneficiary['relation'] ?? ''));
        if ($relation['value'] === '') {
            $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'Beneficiary "' . $name . '" is missing a relationship.'];
        }

        $birthday = ClientDateNormalizer::normalizeDate((string) ($beneficiary['birthday_raw'] ?? ''));
        if (! $birthday['ok'] && ($beneficiary['birthday_raw'] ?? '') !== '') {
            $errors[] = ['field' => 'beneficiaries', 'level' => 'warning', 'message' => 'Beneficiary "' . $name . '" — ' . $birthday['warning']];
        }

        return [
            'first_name' => $parts['first_name'],
            'middle_name' => $parts['middle_name'],
            'last_name' => $parts['last_name'],
            'name_extension' => $parts['name_extension'],
            'date_of_birth' => $birthday['value'],
            'birthday_raw' => (string) ($beneficiary['birthday_raw'] ?? ''),
            'relationship' => $relation['value'],
            'is_primary' => false, // first row set true at commit
        ];
    }

    /**
     * @return array{total: int, ready: int, needs_attention: int, duplicate: int, skipped: int}
     */
    private function countStatuses(array $rawRecords, array $stagedSoFar): array
    {
        $counts = ['ready' => 0, 'needs_attention' => 0, 'duplicate' => 0, 'skipped' => 0];

        foreach ($stagedSoFar as $staged) {
            $status = $staged['record_status'] ?? 'needs_attention';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return [
            'total' => count($rawRecords),
            'ready' => $counts['ready'],
            'needs_attention' => $counts['needs_attention'],
            'duplicate' => $counts['duplicate'],
            'skipped' => $counts['skipped'],
        ];
    }

    private function collectRawText(array $records): string
    {
        $chunks = [];
        foreach ($records as $record) {
            $chunks[] = (string) ($record['extracted_text'] ?? '');
        }

        return implode("\n\n---\n\n", $chunks);
    }
}
