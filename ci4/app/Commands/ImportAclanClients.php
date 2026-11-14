<?php

namespace App\Commands;

use App\Services\ActivityLogService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Imports Faustino V. Aclan's Record Profiling 2022-2025 clients and
 * beneficiaries from writable/imports/*.csv.
 *
 *   php spark import:aclan-clients --dry-run     validate everything, save nothing
 *   php spark import:aclan-clients               import
 *   php spark import:aclan-clients --admin=5     attribute the import to this System Admin user id
 *
 * Everything runs in one transaction (dry-run runs the same code and rolls back,
 * so unique-key problems show up too). Re-running skips what already exists.
 */
class ImportAclanClients extends BaseCommand
{
    protected $group       = 'CareSync';
    protected $name        = 'import:aclan-clients';
    protected $description = 'Import Faustino V. Aclan\'s plan holders and beneficiaries from writable/imports CSV files.';
    protected $usage       = 'import:aclan-clients [--dry-run] [--admin=<user_id>]';
    protected $options     = [
        '--dry-run' => 'Validate and print the summary without saving anything.',
        '--admin'   => 'user_id of the System Admin running the import (default: the first active System Admin).',
    ];

    private const CLIENTS_FILE       = 'faustino_aclan_clients.csv';
    private const BENEFICIARIES_FILE = 'faustino_aclan_beneficiaries.csv';
    private const COORDINATOR        = 'Faustino V. Aclan';
    private const CLIENT_ROLE_ID     = 4;
    private const STAFF_ROLE_ID      = 3;
    // No 0/O, 1/l/I look-alikes.
    private const PASSWORD_ALPHABET  = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    /** @var array<string,bool> passwords handed out in this run, so no two accounts share one */
    private array $usedPasswords = [];
    /** @var array<int,array<string,string>> */
    private array $credentials = [];
    /** @var array<string,int|string> */
    private array $summary = [
        'collector' => '', 'clients_imported' => 0, 'clients_skipped' => 0, 'beneficiaries_imported' => 0,
        'beneficiaries_skipped' => 0, 'accounts_created' => 0, 'deceased' => 0, 'needs_review' => 0,
    ];
    /** @var string[] */
    private array $notes = [];

    public function run(array $params)
    {
        $dryRun = CLI::getOption('dry-run') !== null;
        $dir    = WRITEPATH . 'imports' . DIRECTORY_SEPARATOR;

        try {
            $clients = $this->readCsv($dir . self::CLIENTS_FILE, ['client_ref', 'first_name', 'last_name', 'date_of_birth', 'date_of_application']);
            $bens    = $this->readCsv($dir . self::BENEFICIARIES_FILE, ['client_ref', 'beneficiary_name']);
        } catch (\RuntimeException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        $db = db_connect();
        CLI::write(($dryRun ? '[DRY RUN] ' : '') . count($clients) . ' clients and ' . count($bens) . ' beneficiaries read.', 'yellow');

        $adminId = (int) CLI::getOption('admin');
        $admin = $adminId > 0
            ? $db->table('users')->where('user_id', $adminId)->where('role_id', 1)->get()->getRowArray()
            : $db->table('users')->where('role_id', 1)->where('status', 'active')->orderBy('user_id', 'ASC')->get()->getRowArray();
        if (! $admin) {
            CLI::error('No active System Admin found to attribute the import to. Pass --admin=<user_id>.');

            return EXIT_ERROR;
        }
        $adminId = (int) $admin['user_id'];

        $branch = $db->table('branches')->where('LOWER(branch_name)', 'calapan branch')->get()->getRowArray();
        if (! $branch) {
            CLI::error('Calapan Branch does not exist. Create it first (Branch Management); the import will not create a branch.');

            return EXIT_ERROR;
        }
        $branchId = (int) $branch['branch_id'];

        // Fail early if the migration has not been run.
        foreach (['legacy_reference', 'needs_review', 'review_notes', 'assigned_collector_id'] as $column) {
            if (! $db->fieldExists($column, 'plan_holders')) {
                CLI::error('Run "php spark migrate" first (PrepareBulkClientImport adds plan_holders.' . $column . ').');

                return EXIT_ERROR;
            }
        }

        $db->transBegin();
        $credentialsFile = null;

        try {
            $collectorId = $this->resolveCollector($db, $branchId);
            $refToHolder = $this->importClients($db, $clients, $branchId, $adminId, $collectorId);
            $this->importBeneficiaries($db, $bens, $refToHolder, $adminId);

            if ($dryRun) {
                $db->transRollback();
            } else {
                if ($this->credentials !== []) {
                    $credentialsFile = $this->writeCredentials($dir);
                }
                (new ActivityLogService())->log($adminId, 'imported', 'bulk_import', $collectorId, 'Bulk import – Record Profiling 2022–2025', null, $this->summary);
                if (! $db->transCommit()) {
                    throw new \RuntimeException('The database commit failed.');
                }
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            if ($credentialsFile && is_file($credentialsFile)) {
                @unlink($credentialsFile);
            }
            CLI::error('Import failed and was rolled back: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $this->printSummary($dryRun, $credentialsFile);

        return EXIT_SUCCESS;
    }

    // ------------------------------------------------------------------

    private function resolveCollector($db, int $branchId): int
    {
        $existing = $db->table('users')
            ->where('LOWER(first_name)', 'faustino')
            ->where('LOWER(last_name)', 'aclan')
            ->groupStart()->whereIn('role_id', [2, 3, 5])->orWhere('is_collector', 1)->groupEnd()
            ->orderBy('user_id', 'ASC')
            ->get()->getRowArray();

        if ($existing) {
            $this->summary['collector'] = 'existing account used (user_id ' . $existing['user_id'] . ', ' . $existing['username'] . ')';

            return (int) $existing['user_id'];
        }

        $username = $this->uniqueUsername($db, 'faustino.aclan', '-');
        $password = $this->makePassword(10);
        $db->table('users')->insert([
            'username'             => $username,
            'password_hash'        => password_hash($password, PASSWORD_DEFAULT),
            'email'                => null,
            'first_name'           => 'Faustino',
            'middle_name'          => 'V.',
            'last_name'            => 'Aclan',
            'role_id'              => self::STAFF_ROLE_ID,
            'branch_id'            => $branchId,
            'status'               => 'active',
            'account_status'       => 'verified',
            'is_plan_holder'       => 0,
            'is_collector'         => 1,
            'must_change_password' => 1,
        ]);
        $id = (int) $db->insertID();
        $this->credentials[] = ['client_ref' => 'COLLECTOR', 'full_name' => 'Faustino V. Aclan (collector account)', 'address' => '', 'username' => $username, 'password' => $password];
        $this->summary['collector'] = 'created (user_id ' . $id . ', ' . $username . ')';

        return $id;
    }

    /** @return array<string,int> client_ref => plan_holder_id for clients that belong to this import */
    private function importClients($db, array $rows, int $branchId, int $adminId, int $collectorId): array
    {
        $map = [];
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $n => $r) {
            $line = $n + 2;
            $ref  = trim($r['client_ref']);
            $first = $this->clean($r['first_name']);
            $last  = $this->clean($r['last_name']);
            $mid   = $this->clean($r['middle_name'] ?? '') ?: null;
            $dob   = $this->date($r['date_of_birth'] ?? '');
            $applied = $this->date($r['date_of_application'] ?? '');

            if ($ref === '' || $first === '' || $last === '' || ! $dob) {
                throw new \RuntimeException("clients row {$line}: client_ref, first_name, last_name and a valid date_of_birth are required.");
            }

            // Already imported by an earlier run?
            $mine = $db->table('plan_holders')->select('plan_holder_id')->like('legacy_reference', ', ' . $ref, 'before')->get()->getRowArray();
            if ($mine) {
                $map[$ref] = (int) $mine['plan_holder_id'];
                $this->summary['clients_skipped']++;
                $this->notes[] = "{$ref} {$first} {$last}: already imported, skipped.";
                continue;
            }

            // Same person already in the system? Never overwrite.
            $dupe = $db->query(
                "SELECT ph.plan_holder_id FROM plan_holders ph JOIN users u ON u.user_id = ph.user_id
                 WHERE LOWER(u.first_name) = ? AND LOWER(u.last_name) = ? AND LOWER(COALESCE(u.middle_name, '')) = ? AND ph.date_of_birth = ? LIMIT 1",
                [mb_strtolower($first), mb_strtolower($last), mb_strtolower($mid ?? ''), $dob]
            )->getRowArray();
            if ($dupe) {
                $this->summary['clients_skipped']++;
                $this->notes[] = "{$ref} {$first} {$last} ({$dob}): matches existing plan holder #{$dupe['plan_holder_id']}, skipped.";
                continue;
            }

            $deceased = strcasecmp(trim($r['status_note'] ?? ''), 'Deceased') === 0;
            $reviewNote = trim($r['review_notes'] ?? '');
            $year = trim($r['source_file_year'] ?? '');

            $username = $this->uniqueUsername($db, $this->baseUsername($first, $last, $dob), '-');
            $password = null;
            if ($deceased) {
                // plan_holders.user_id is required, so a record exists, but it cannot sign in:
                // inactive, with a random password nobody is given.
                $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            } else {
                $password = $this->makePassword(8);
                $hash = password_hash($password, PASSWORD_DEFAULT);
            }

            $db->table('users')->insert([
                'username'             => $username,
                'password_hash'        => $hash,
                'email'                => null,
                'first_name'           => $first,
                'middle_name'          => $mid,
                'last_name'            => $last,
                'name_extension'       => $this->clean($r['suffix'] ?? '') ?: null,
                'role_id'              => self::CLIENT_ROLE_ID,
                'branch_id'            => $branchId,
                'status'               => $deceased ? 'inactive' : 'active',
                'account_status'       => 'verified',
                'is_plan_holder'       => 1,
                'must_change_password' => $deceased ? 0 : 1,
            ]);
            $userId = (int) $db->insertID();

            [$street, $barangay, $city] = $this->parseAddress($r['address'] ?? '');
            $db->table('plan_holders')->insert([
                'user_id'               => $userId,
                'registered_by'         => $adminId,
                'address_street'        => $street,
                'address_barangay'      => $barangay,
                'address_city'          => $city,
                'date_of_birth'         => $dob,
                'age'                   => (int) (new \DateTime($dob))->diff(new \DateTime('today'))->y,
                'branch_id'             => $branchId,
                'status'                => $deceased ? 'deceased' : 'active',
                'unique_identifier'     => $this->uniquePlanNumber($db, $branchId),
                'coordinator'           => self::COORDINATOR,
                'application_date'      => $applied,
                'legacy_reference'      => 'Imported from Record Profiling ' . $year . ', ' . $ref,
                'needs_review'          => $reviewNote !== '' ? 1 : 0,
                'review_notes'          => $reviewNote !== '' ? $reviewNote : null,
                'assigned_collector_id' => $collectorId,
                'created_at'            => $now,
            ]);
            $map[$ref] = (int) $db->insertID();

            $this->summary['clients_imported']++;
            if ($deceased) {
                $this->summary['deceased']++;
            } else {
                $this->summary['accounts_created']++;
                $this->credentials[] = [
                    'client_ref' => $ref,
                    'full_name'  => trim($r['plan_holder_full_name'] ?? '') ?: trim("{$first} {$mid} {$last}"),
                    'address'    => trim($r['address'] ?? ''),
                    'username'   => $username,
                    'password'   => $password,
                ];
            }
            if ($reviewNote !== '') {
                $this->summary['needs_review']++;
            }
        }

        return $map;
    }

    private function importBeneficiaries($db, array $rows, array $refToHolder, int $adminId): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $n => $r) {
            $line = $n + 2;
            $ref = trim($r['client_ref']);
            $name = $this->clean($r['beneficiary_name']);
            if ($name === '') {
                throw new \RuntimeException("beneficiaries row {$line}: beneficiary_name is empty.");
            }
            if (! isset($refToHolder[$ref])) {
                // Client was skipped as an existing duplicate (or is unknown): leave their data alone.
                $this->summary['beneficiaries_skipped']++;
                continue;
            }

            [$first, $middle, $last, $ext] = $this->splitName($name);
            $dob = $this->date($r['birthday'] ?? '');
            $relation = $this->clean($r['relation'] ?? '') ?: null;
            $holderId = $refToHolder[$ref];

            $exists = $db->query(
                "SELECT 1 FROM beneficiaries WHERE plan_holder_id = ? AND LOWER(first_name) = ? AND LOWER(last_name) = ? AND LOWER(COALESCE(middle_name, '')) = ? LIMIT 1",
                [$holderId, mb_strtolower($first), mb_strtolower($last), mb_strtolower($middle ?? '')]
            )->getRowArray() !== null;
            if ($exists) {
                $this->summary['beneficiaries_skipped']++;
                continue;
            }

            $db->table('beneficiaries')->insert([
                'plan_holder_id'      => $holderId,
                'first_name'          => $first,
                'middle_name'         => $middle,
                'last_name'           => $last,
                'name_extension'      => $ext,
                'date_of_birth'       => $dob,
                'relationship'        => $relation,
                'is_primary'          => 0,
                'verification_status' => 'verified',
                'source'              => 'legacy_manual',
                'encoded_by'          => $adminId,
                'encoded_at'          => $now,
            ]);
            $this->summary['beneficiaries_imported']++;
        }
    }

    // ------------------------------------------------------------------

    /** @return array<int,array<string,string>> */
    private function readCsv(string $path, array $required): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException('Missing file: ' . $path);
        }
        $h = fopen($path, 'rb');
        $header = fgetcsv($h);
        if (! $header) {
            throw new \RuntimeException('Empty file: ' . $path);
        }
        $header = array_map(static fn ($c) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c)), $header);
        $missing = array_diff($required, $header);
        if ($missing !== []) {
            throw new \RuntimeException(basename($path) . ' is missing column(s): ' . implode(', ', $missing));
        }

        $rows = [];
        while (($cells = fgetcsv($h)) !== false) {
            if ($cells === [null] || implode('', $cells) === '') {
                continue;
            }
            $cells = array_pad($cells, count($header), '');
            $rows[] = array_combine($header, array_slice($cells, 0, count($header)));
        }
        fclose($h);

        return $rows;
    }

    private function clean(string $v): string
    {
        return trim(preg_replace('/\s+/u', ' ', $v));
    }

    private function date(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $v);

        return ($d && $d->format('Y-m-d') === $v) ? $v : null;
    }

    /** "Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro" => [street, barangay, city] */
    private function parseAddress(string $address): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn ($p) => $p !== ''));
        if ($parts !== [] && stripos(end($parts), 'mindoro') !== false) {
            array_pop($parts); // province: plan_holders has no province column
        }
        if ($parts === []) {
            return [null, null, null];
        }
        $city = array_pop($parts);
        $barangay = $parts !== [] ? array_pop($parts) : null;
        $street = $parts !== [] ? implode(', ', $parts) : null;

        return [$street, $barangay, $city];
    }

    /** Beneficiary names arrive as one string: "Pauline F. Rolloque", "Geb", "Mary Ann De la Cruz". */
    private function splitName(string $name): array
    {
        $tokens = explode(' ', $name);
        $ext = null;
        if (count($tokens) > 2 && preg_match('/^(jr\.?|sr\.?|ii|iii|iv)$/i', end($tokens))) {
            $ext = array_pop($tokens);
        }
        if (count($tokens) === 1) {
            return [$tokens[0], null, '', $ext];
        }

        $first = array_shift($tokens);
        $last = array_pop($tokens);
        // Surname particles stay with the surname.
        while ($tokens !== [] && in_array(mb_strtolower(end($tokens)), ['de', 'del', 'dela', 'delos', 'dela', 'la', 'los', 'san', 'santa', 'van', 'von'], true)) {
            $last = array_pop($tokens) . ' ' . $last;
        }

        return [$first, $tokens !== [] ? implode(' ', $tokens) : null, $last, $ext];
    }

    private function baseUsername(string $first, string $last, string $dob): string
    {
        $ascii = static function (string $s): string {
            if (function_exists('transliterator_transliterate')) {
                $s = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
            }

            return preg_replace('/[^a-z0-9]/', '', strtolower($s));
        };

        return $ascii(mb_substr($first, 0, 1)) . $ascii($last) . substr($dob, 0, 4);
    }

    private function uniqueUsername($db, string $base, string $joiner): string
    {
        $candidate = $base;
        $i = 2;
        while ($db->table('users')->where('username', $candidate)->countAllResults() > 0) {
            $candidate = $base . $joiner . $i++;
        }

        return $candidate;
    }

    private function uniquePlanNumber($db, int $branchId): string
    {
        do {
            $code = 'PH-' . $branchId . '-' . date('YmdHi') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($db->table('plan_holders')->where('unique_identifier', $code)->countAllResults() > 0);

        return $code;
    }

    private function makePassword(int $length): string
    {
        $max = strlen(self::PASSWORD_ALPHABET) - 1;
        do {
            $p = '';
            for ($i = 0; $i < $length; $i++) {
                $p .= self::PASSWORD_ALPHABET[random_int(0, $max)];
            }
            $ok = preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p) && ! isset($this->usedPasswords[$p]);
        } while (! $ok);
        $this->usedPasswords[$p] = true;

        return $p;
    }

    private function writeCredentials(string $dir): string
    {
        $path = $dir . 'faustino_aclan_credentials_' . date('Y-m-d_His') . '.csv';
        $h = @fopen($path, 'wb');
        if (! $h) {
            throw new \RuntimeException('Could not write the credentials file in ' . $dir);
        }
        fputcsv($h, ['client_ref', 'full name', 'address', 'username', 'temporary password']);
        foreach ($this->credentials as $c) {
            fputcsv($h, [$c['client_ref'], $c['full_name'], $c['address'], $c['username'], $c['password']]);
        }
        fclose($h);

        return $path;
    }

    private function printSummary(bool $dryRun, ?string $credentialsFile): void
    {
        $s = $this->summary;
        CLI::newLine();
        CLI::write($dryRun ? 'DRY RUN - nothing was saved.' : 'Import complete.', $dryRun ? 'yellow' : 'green');
        CLI::write('  Collector account       : ' . $s['collector']);
        CLI::write('  Clients imported        : ' . $s['clients_imported']);
        CLI::write('  Clients skipped         : ' . $s['clients_skipped']);
        CLI::write('  Beneficiaries imported  : ' . $s['beneficiaries_imported'] . ' (skipped ' . $s['beneficiaries_skipped'] . ')');
        CLI::write('  Client accounts created : ' . $s['accounts_created']);
        CLI::write('  Deceased (no login)     : ' . $s['deceased']);
        CLI::write('  Flagged for review      : ' . $s['needs_review']);
        foreach ($this->notes as $note) {
            CLI::write('  - ' . $note, 'light_gray');
        }
        if ($credentialsFile) {
            CLI::newLine();
            CLI::write('Credentials file: ' . $credentialsFile, 'yellow');
            CLI::write('It holds every plain temporary password. Open it as System Admin at /admin/imports/credentials, hand the logins out, then download and delete it.', 'yellow');
        } elseif ($dryRun && $this->credentials !== []) {
            CLI::write(count($this->credentials) . ' login(s) would be written to a credentials file.', 'light_gray');
        }
    }
}
