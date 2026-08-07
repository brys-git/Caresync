<?php

namespace App\Services\Import;

use App\Services\Import\Normalizers\ClientAddressNormalizer;
use App\Services\Import\Normalizers\ClientDateNormalizer;
use App\Services\Import\Normalizers\ClientNameKeyGenerator;
use App\Services\Import\Normalizers\ClientNameNormalizer;

/**
 * Applies an admin's inline edit to one staged record and recomputes everything
 * that depends on the edited values: name/date/address normalization, beneficiary
 * validation, duplicate matching, duplicate_key, record_status, and the temporary
 * credentials (regenerated only when the name changes).
 *
 * Pure logic — the caller persists the returned row columns.
 */
class ClientImportReviewService
{
    private ClientMatcherService $matcher;
    private ImportCredentialService $credentials;

    public function __construct()
    {
        $this->matcher = new ClientMatcherService();
        $this->credentials = new ImportCredentialService();
    }

    /**
     * @param array<string, mixed> $record current staging row
     * @param array<string, mixed> $form   submitted editable fields
     * @param array<int, array<string, mixed>> $inBatch other staged records in the batch (for in-batch matching)
     *
     * @return array<string, mixed> refreshed columns to persist
     */
    public function applyEdit(array $record, array $form, array $inBatch = []): array
    {
        $errors = [];
        $warnings = [];

        // ---- Name ----
        $firstName = trim((string) ($form['first_name'] ?? $record['first_name'] ?? ''));
        $middleName = trim((string) ($form['middle_name'] ?? $record['middle_name'] ?? ''));
        $lastName = trim((string) ($form['last_name'] ?? $record['last_name'] ?? ''));
        $nameExtension = trim((string) ($form['name_extension'] ?? $record['name_extension'] ?? ''));

        if ($firstName === '') {
            $errors[] = ['field' => 'first_name', 'level' => 'error', 'message' => 'First name is missing.'];
        }
        if ($lastName === '') {
            $errors[] = ['field' => 'last_name', 'level' => 'error', 'message' => 'Last name is missing (required to create an account).'];
        }

        // ---- Dates ----
        $dob = trim((string) ($form['date_of_birth'] ?? $record['date_of_birth'] ?? ''));
        if ($dob !== '') {
            $dobResult = ClientDateNormalizer::normalizeDate($dob);
            if (! $dobResult['ok']) {
                $errors[] = ['field' => 'date_of_birth', 'level' => 'error', 'message' => $dobResult['warning']];
            } else {
                $dob = (string) $dobResult['value'];
                if ($dobResult['warning'] !== null) {
                    $warnings[] = $dobResult['warning'];
                }
            }
        }

        $applicationDate = trim((string) ($form['application_date'] ?? $record['application_date'] ?? ''));
        if ($applicationDate !== '') {
            $appResult = ClientDateNormalizer::normalizeDate($applicationDate);
            if (! $appResult['ok']) {
                $warnings[] = $appResult['warning'];
            } else {
                $applicationDate = (string) $appResult['value'];
            }
        }

        $coordinator = trim((string) ($form['coordinator'] ?? $record['coordinator'] ?? ''));

        // ---- Address ----
        $addressRaw = trim((string) ($form['address_raw'] ?? $record['address_raw'] ?? ''));
        $rawChanged = $addressRaw !== trim((string) ($record['address_raw'] ?? ''));

        if ($rawChanged) {
            $address = ClientAddressNormalizer::splitAddress($addressRaw);
            if (! $address['parsed']) {
                $warnings[] = 'Address could not be split automatically — please review.';
            }
        } else {
            $address = [
                'address_no' => trim((string) ($form['address_no'] ?? $record['address_no'] ?? '')),
                'address_street' => trim((string) ($form['address_street'] ?? $record['address_street'] ?? '')),
                'address_barangay' => trim((string) ($form['address_barangay'] ?? $record['address_barangay'] ?? '')),
                'address_city' => trim((string) ($form['address_city'] ?? $record['address_city'] ?? '')),
                'address_province' => trim((string) ($form['address_province'] ?? '')),
                'parsed' => true,
            ];
        }

        // ---- Optional / plan fields (stored in mapped_data JSON) ----
        $mapped = json_decode((string) ($record['mapped_data'] ?? '{}'), true);
        if (! is_array($mapped)) {
            $mapped = [];
        }
        $optional = is_array($mapped['optional'] ?? null) ? $mapped['optional'] : [];
        $plan = is_array($mapped['plan'] ?? null) ? $mapped['plan'] : [];

        $optionalKeys = [
            'contact_number', 'email', 'gender', 'civil_status', 'citizenship',
            'place_of_birth', 'senior_citizen_id', 'id_control_no',
            'emergency_contact_name', 'emergency_contact_number', 'emergency_contact_address',
        ];
        foreach ($optionalKeys as $key) {
            if (array_key_exists($key, $form)) {
                $optional[$key] = trim((string) $form[$key]);
            }
        }

        $plan['plan_status'] = (string) ($form['plan_status'] ?? $plan['plan_status'] ?? 'active');
        $plan['monthly_fee'] = (float) ($form['monthly_fee'] ?? $plan['monthly_fee'] ?? 240.0);
        $plan['package_id'] = (int) ($form['package_id'] ?? $plan['package_id'] ?? 1);

        // ---- Beneficiaries ----
        $beneficiaries = $this->normalizeBeneficiaries($form['beneficiaries'] ?? [], $errors, $warnings);
        if ($beneficiaries === []) {
            $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'No beneficiaries were captured — at least one is required.'];
        }

        // ---- Duplicate matching ----
        $match = $this->matcher->matchRecord([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'date_of_birth' => $dob,
            'contact_number' => (string) ($optional['contact_number'] ?? ''),
            'beneficiaries' => $beneficiaries,
        ], $inBatch);

        // ---- Temp credentials — regenerate only when the name changed ----
        $tempUsername = (string) ($record['temp_username'] ?? '');
        $tempEmail = (string) ($record['temp_email'] ?? '');

        $nameChanged = mb_strtolower((string) ($record['first_name'] ?? '')) !== mb_strtolower($firstName)
            || mb_strtolower((string) ($record['last_name'] ?? '')) !== mb_strtolower($lastName);

        if ($nameChanged || $tempUsername === '') {
            $tempUsername = $this->credentials->generateUsername($firstName, $lastName);
            $tempEmail = $this->credentials->generateEmail($tempUsername);
        }

        // ---- Record status precedence ----
        $status = $errors !== [] ? 'needs_attention' : $match['status'];

        $keys = ClientNameKeyGenerator::buildKeys($firstName, $lastName, $dob, $nameExtension);

        $validationIssues = array_merge($errors, array_map(static fn ($w) => [
            'field' => 'general',
            'level' => 'warning',
            'message' => $w,
        ], $warnings));

        return [
            'coordinator' => $coordinator !== '' ? $coordinator : null,
            'application_date' => $applicationDate !== '' ? $applicationDate : null,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'name_extension' => $nameExtension,
            'date_of_birth' => $dob !== '' ? $dob : null,
            'address_raw' => $addressRaw,
            'address_no' => $address['address_no'],
            'address_street' => $address['address_street'],
            'address_barangay' => $address['address_barangay'],
            'address_city' => $address['address_city'],
            'mapped_data' => json_encode([
                'address_province' => $address['address_province'] ?? '',
                'optional' => $optional,
                'plan' => $plan,
            ], JSON_UNESCAPED_UNICODE),
            'beneficiaries_json' => json_encode($beneficiaries, JSON_UNESCAPED_UNICODE),
            'validation_errors_json' => json_encode($validationIssues, JSON_UNESCAPED_UNICODE),
            'match_candidates_json' => json_encode([
                'candidates' => $match['candidates'],
                'status' => $match['status'],
                'informational' => $match['informational'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
            'duplicate_key' => $keys['exact'] ?: null,
            'record_status' => $status,
            'temp_username' => $tempUsername,
            'temp_email' => $tempEmail,
        ];
    }

    /**
     * @param array<int|string, mixed> $rows submitted beneficiary rows
     * @param array<int, array<string, mixed>> $errors
     * @param array<int, string> $warnings
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBeneficiaries(array $rows, array &$errors, array &$warnings): array
    {
        $result = [];
        $rowNumber = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            $name = trim((string) ($row['name'] ?? ''));
            $first = trim((string) ($row['first_name'] ?? ''));
            $last = trim((string) ($row['last_name'] ?? ''));

            if ($first === '' && $last === '' && $name === '') {
                continue; // fully empty row — ignore
            }

            // Prefer the separate fields; fall back to a single "name" text input.
            if ($name !== '' && $first === '' && $last === '') {
                $parts = ClientNameNormalizer::splitFullName($name);
                $first = $parts['first_name'];
                $middle = $parts['middle_name'];
                $last = $parts['last_name'];
                $extension = $parts['name_extension'];
            } else {
                $middle = trim((string) ($row['middle_name'] ?? ''));
                $extension = trim((string) ($row['name_extension'] ?? ''));
            }

            $relationshipRaw = (string) ($row['relationship'] ?? '');
            $relation = ClientNameNormalizer::normalizeRelation($relationshipRaw);

            $fullName = trim($first . ' ' . $last) ?: '-';
            if ($last === '') {
                $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'Beneficiary "' . $fullName . '" is missing a last name.'];
            }
            if ($relation['value'] === '') {
                $errors[] = ['field' => 'beneficiaries', 'level' => 'error', 'message' => 'Beneficiary "' . $fullName . '" is missing a relationship.'];
            }

            $birthdayRaw = (string) ($row['birthday_raw'] ?? $row['date_of_birth'] ?? '');
            $birthday = ClientDateNormalizer::normalizeDate($birthdayRaw);
            if (! $birthday['ok'] && $birthdayRaw !== '') {
                $errors[] = ['field' => 'beneficiaries', 'level' => 'warning', 'message' => 'Beneficiary "' . $fullName . '" — ' . $birthday['warning']];
            }

            $result[] = [
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'name_extension' => $extension,
                'date_of_birth' => $birthday['value'],
                'birthday_raw' => $birthdayRaw,
                'relationship' => $relation['value'],
                'is_primary' => false, // first row set true at commit
            ];
        }

        return $result;
    }
}
