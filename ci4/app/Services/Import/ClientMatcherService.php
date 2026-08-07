<?php

namespace App\Services\Import;

use App\Services\Import\Normalizers\ClientNameKeyGenerator;
use App\Services\Import\Normalizers\ClientNameNormalizer;

/**
 * Detects whether a staged record already exists — either in the live
 * plan_holders/users tables OR elsewhere in the same import batch.
 *
 * Scoring (0..1):
 *   DOB 0.40 | last name 0.30 | first name 0.30 | middle 0.10 | contact +0.05
 * Thresholds: >=0.85 duplicate | 0.65–0.85 needs_attention | <0.65 ready
 */
class ClientMatcherService
{
    private const SCORE_DUPLICATE = 0.85;
    private const SCORE_NEEDS_ATTENTION = 0.65;

    /**
     * @param array<string, mixed> $mapped normalized record (first_name, last_name, middle_name, date_of_birth, contact_number)
     * @param array<int, array<string, mixed>> $inBatch previously staged records in this batch (same shape)
     *
     * @return array{candidates: array<int, array<string, mixed>>, status: string, keys: array<string, string>}
     */
    public function matchRecord(array $mapped, array $inBatch = []): array
    {
        $firstName = (string) ($mapped['first_name'] ?? '');
        $lastName = (string) ($mapped['last_name'] ?? '');
        $middleName = (string) ($mapped['middle_name'] ?? '');
        $dob = (string) ($mapped['date_of_birth'] ?? '');
        $contact = (string) ($mapped['contact_number'] ?? '');

        $keys = ClientNameKeyGenerator::buildKeys($firstName, $lastName, $dob ?: null);

        $candidates = [];

        // --- In-batch duplicates (same person staged twice as a plan holder) ---
        foreach ($inBatch as $index => $staged) {
            $score = $this->scorePerson(
                $firstName,
                $lastName,
                $middleName,
                $dob,
                $contact,
                (string) ($staged['first_name'] ?? ''),
                (string) ($staged['last_name'] ?? ''),
                (string) ($staged['middle_name'] ?? ''),
                (string) ($staged['date_of_birth'] ?? ''),
                (string) ($staged['contact_number'] ?? '')
            );
            if ($score >= 0.55) {
                $candidates[] = $this->candidate(
                    'batch',
                    (int) ($staged['source_index'] ?? $index),
                    $this->fullName($staged),
                    (string) ($staged['date_of_birth'] ?? ''),
                    $score,
                    'A similar record appears earlier in this same document.'
                );
            }
        }

        // --- Existing clients in the live database ---
        foreach ($this->searchExisting($lastName, $dob) as $existing) {
            $score = $this->scorePerson(
                $firstName,
                $lastName,
                $middleName,
                $dob,
                $contact,
                (string) ($existing['first_name'] ?? ''),
                (string) ($existing['last_name'] ?? ''),
                (string) ($existing['middle_name'] ?? ''),
                (string) ($existing['date_of_birth'] ?? ''),
                (string) ($existing['contact_number'] ?? '')
            );
            if ($score >= 0.55) {
                $candidates[] = $this->candidate(
                    'existing',
                    (int) ($existing['plan_holder_id'] ?? 0),
                    $this->fullName($existing),
                    (string) ($existing['date_of_birth'] ?? ''),
                    $score,
                    'This person appears to already be a registered client.'
                );
            }
        }

        // Rank best first.
        usort($candidates, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $candidates = array_slice($candidates, 0, 5);

        // Record-level status.
        $best = $candidates[0]['score'] ?? 0.0;
        if ($best >= self::SCORE_DUPLICATE) {
            $status = 'duplicate';
        } elseif ($best >= self::SCORE_NEEDS_ATTENTION) {
            $status = 'needs_attention';
        } else {
            $status = 'ready';
        }

        // --- Informational: plan holder / beneficiary cross-references (advisory only) ---
        $informational = [];

        // (1) This person also appears as a beneficiary of another record in the batch.
        foreach ($inBatch as $index => $staged) {
            foreach ($this->beneficiaryList($staged) as $beneficiary) {
                if ($this->samePersonName(
                    ['first_name' => $firstName, 'last_name' => $lastName],
                    $beneficiary
                )) {
                    $sourceIndex = (int) ($staged['source_index'] ?? $index);
                    $informational[] = $this->note(
                        'plan_holder_is_beneficiary',
                        $sourceIndex,
                        'This person also appears as a beneficiary of record #' . $sourceIndex
                            . ' (' . $this->fullName($staged) . ').',
                        $this->fullName($beneficiary)
                    );
                }
            }
        }

        // (2) A beneficiary of this record is also a plan holder in the batch.
        foreach (($mapped['beneficiaries'] ?? []) as $beneficiary) {
            foreach ($inBatch as $index => $staged) {
                if ($this->samePersonName($staged, $beneficiary)) {
                    $sourceIndex = (int) ($staged['source_index'] ?? $index);
                    $informational[] = $this->note(
                        'beneficiary_is_plan_holder',
                        $sourceIndex,
                        'Beneficiary "' . $this->fullName($beneficiary)
                            . '" is also a plan holder in this document (record #' . $sourceIndex
                            . ', ' . $this->fullName($staged) . ').',
                        $this->fullName($beneficiary)
                    );
                }
            }
        }

        $informational = $this->uniqueNotes($informational);

        return [
            'candidates' => $candidates,
            'status' => $status,
            'keys' => $keys,
            'informational' => $informational,
        ];
    }

    /**
     * Post-parse pass: recompute the plan-holder / beneficiary cross-reference
     * notes across the WHOLE batch, independent of staging order. The incremental
     * matchRecord() above can only see previously staged records, so the two
     * directions (X is a beneficiary of Y, and X has a beneficiary that is plan
     * holder Y) are fully resolved here.
     *
     * @param array<int, array<string, mixed>> $records all staged rows (must include import_record_id)
     *
     * @return array<int, array<int, array<string, mixed>>> keyed by import_record_id
     */
    public function annotateInformational(array $records): array
    {
        $notes = [];

        foreach ($records as $record) {
            $recordId = (int) ($record['import_record_id'] ?? 0);
            if ($recordId <= 0) {
                continue;
            }

            $own = [];

            foreach ($records as $other) {
                if ((int) ($other['import_record_id'] ?? 0) === $recordId) {
                    continue;
                }
                $sourceIndex = (int) ($other['source_index'] ?? 0);

                // The plan holder X also appears as a beneficiary of record Y.
                foreach ($this->beneficiaryList($other) as $beneficiary) {
                    if ($this->samePersonName($record, $beneficiary)) {
                        $own[] = $this->note(
                            'plan_holder_is_beneficiary',
                            $sourceIndex,
                            'This person also appears as a beneficiary of record #' . $sourceIndex
                                . ' (' . $this->fullName($other) . ').',
                            $this->fullName($beneficiary)
                        );
                    }
                }

                // A beneficiary of record X is the plan holder Y.
                foreach ($this->beneficiaryList($record) as $beneficiary) {
                    if ($this->samePersonName($other, $beneficiary)) {
                        $own[] = $this->note(
                            'beneficiary_is_plan_holder',
                            $sourceIndex,
                            'Beneficiary "' . $this->fullName($beneficiary)
                                . '" is also a plan holder in this document (record #' . $sourceIndex
                                . ', ' . $this->fullName($other) . ').',
                            $this->fullName($beneficiary)
                        );
                    }
                }
            }

            $own = $this->uniqueNotes($own);
            if ($own !== []) {
                $notes[$recordId] = $own;
            }
        }

        return $notes;
    }

    /**
     * Query existing plan_holders/users. When a DOB is present, restrict to
     * same birth year +/- 2 years to bound the scan; the score still compares
     * full DOB when available.
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchExisting(string $lastName, string $dob): array
    {
        $db = db_connect();

        $builder = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.date_of_birth, u.first_name, u.middle_name, u.last_name, u.name_extension, u.contact_number AS user_contact')
            ->join('users u', 'u.user_id = ph.user_id', 'inner');

        if ($lastName !== '') {
            $builder->like('u.last_name', $lastName);
        }

        if ($dob !== '') {
            $year = (int) substr($dob, 0, 4);
            if ($year > 1900) {
                $builder->groupStart()
                    ->where('ph.date_of_birth IS NOT NULL')
                    ->where('YEAR(ph.date_of_birth) >=', $year - 2, false)
                    ->where('YEAR(ph.date_of_birth) <=', $year + 2, false)
                    ->groupEnd();
            }
        }

        $rows = $builder->limit(30)->get()->getResultArray();

        // Normalize the contact_number (plan_holders has none — users does).
        foreach ($rows as &$row) {
            $row['contact_number'] = (string) ($row['user_contact'] ?? '');
            unset($row['user_contact']);
        }

        return $rows;
    }

    /**
     * @return float 0..1
     */
    private function scorePerson(
        string $fn, string $ln, string $mn, string $dob, string $contact,
        string $cFn, string $cLn, string $cMn, string $cDob, string $cContact
    ): float {
        $score = 0.0;
        $matchedOn = [];

        // DOB (0.40)
        if ($dob !== '' && $cDob !== '') {
            if ($dob === $cDob) {
                $score += 0.40;
                $matchedOn[] = 'dob';
            } else {
                $dY = (int) substr($dob, 0, 4);
                $dM = (int) substr($dob, 5, 2);
                $cY = (int) substr($cDob, 0, 4);
                $cM = (int) substr($cDob, 5, 2);
                if ($dY === $cY && $dM === $cM) {
                    $score += 0.25;
                    $matchedOn[] = 'dob_year_month';
                } elseif ($dY === $cY) {
                    $score += 0.15;
                    $matchedOn[] = 'dob_year';
                }
            }
        } else {
            $score += 0.10; // one side missing DOB — uncertain
        }

        // Last name (0.30)
        $score += $this->nameComponentScore($ln, $cLn, 0.30, true, $matchedOn, 'last_name');
        // First name (0.30)
        $score += $this->nameComponentScore($fn, $cFn, 0.30, false, $matchedOn, 'first_name');
        // Middle name (0.10)
        if ($mn !== '' && $cMn !== '') {
            if (mb_strtolower($mn) === mb_strtolower($cMn)) {
                $score += 0.10;
            } elseif ($this->similarity($mn, $cMn) >= 0.6) {
                $score += 0.05;
            }
        }

        // Contact (+0.05)
        if ($contact !== '' && $cContact !== '' && $this->normalizePhone($contact) === $this->normalizePhone($cContact)) {
            $score += 0.05;
            $matchedOn[] = 'contact';
        }

        return round(min(1.0, $score), 3);
    }

    private function nameComponentScore(
        string $a, string $b, float $weight, bool $isLast, array &$matchedOn, string $label
    ): float {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        $ka = ClientNameNormalizer::comparisonKey($a);
        $kb = ClientNameNormalizer::comparisonKey($b);

        if ($ka === $kb) {
            $matchedOn[] = $label;

            return $weight;
        }

        $sim = $this->similarity($ka, $kb);
        if ($sim >= 0.8) {
            $matchedOn[] = $label . '_similar';

            return $weight * 0.666;
        }
        if ($sim >= 0.6) {
            $matchedOn[] = $label . '_fuzzy';

            return $weight * 0.333;
        }

        if (metaphone($ka) !== '' && metaphone($ka) === metaphone($kb)) {
            $matchedOn[] = $label . '_phonetic';

            return $weight * ($isLast ? 0.333 : 0.166);
        }

        return 0.0;
    }

    private function similarity(string $a, string $b): float
    {
        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    private function candidate(string $source, int $id, string $fullName, string $dob, float $score, string $reason): array
    {
        return [
            'source' => $source,
            'id' => $id,
            'full_name' => $fullName,
            'date_of_birth' => $dob,
            'score' => $score,
            'reason' => $reason,
        ];
    }

    /**
     * @param array<string, mixed> $person
     */
    private function fullName(array $person): string
    {
        $name = trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['middle_name'] ?? ''));
        $last = (string) ($person['last_name'] ?? '');
        $ext = (string) ($person['name_extension'] ?? '');

        $full = trim($name . ' ' . $last);
        if ($ext !== '') {
            $full = trim($full . ' ' . $ext);
        }

        return $full ?: '-';
    }

    /**
     * @return array<string, mixed>
     */
    private function note(string $type, int $sourceIndex, string $text, string $name): array
    {
        return [
            'type' => $type,
            'source_index' => $sourceIndex,
            'name' => $name,
            'text' => $text,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $notes
     *
     * @return array<int, array<string, mixed>>
     */
    private function uniqueNotes(array $notes): array
    {
        $seen = [];
        $out = [];
        foreach ($notes as $note) {
            $key = (string) ($note['text'] ?? '');
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $note;
        }

        return $out;
    }

    /**
     * Decode a staged record's beneficiaries_json into a list of rows.
     *
     * @param array<string, mixed> $record
     *
     * @return array<int, array<string, mixed>>
     */
    private function beneficiaryList(array $record): array
    {
        $list = json_decode((string) ($record['beneficiaries_json'] ?? '[]'), true);

        return is_array($list) ? $list : [];
    }

    /**
     * True when two people share the same first + last name (middle names and
     * suffixes ignored — beneficiaries are usually recorded without them).
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private function samePersonName(array $a, array $b): bool
    {
        $aKey = $this->personKey($a);
        $bKey = $this->personKey($b);

        return $aKey !== '' && $aKey === $bKey;
    }

    /**
     * @param array<string, mixed> $person
     */
    private function personKey(array $person): string
    {
        $first = (string) ($person['first_name'] ?? '');
        $last = (string) ($person['last_name'] ?? '');
        if ($first === '' || $last === '') {
            return '';
        }

        return ClientNameNormalizer::comparisonKey($first . ' ' . $last);
    }
}
