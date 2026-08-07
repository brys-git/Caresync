<?php

namespace App\Services\Import\Normalizers;

/**
 * Normalizes dates found in client documents to Y-m-d.
 *
 * Primary expected format is MM-DD-YYYY (observed in the record-profiling
 * documents). Handles concatenated values like "09-252013" (-> 2013-09-25),
 * month/day swap when the month token exceeds 12, and calendar validation.
 *
 * @return array{value: string|null, ok: bool, warning: string|null, display: string}
 */
class ClientDateNormalizer
{
    /**
     * @return array{value: string|null, ok: bool, warning: string|null, display: string}
     */
    public static function normalizeDate(?string $raw): array
    {
        $raw = trim((string) $raw);
        $empty = ['value' => null, 'ok' => true, 'warning' => null, 'display' => ''];

        if ($raw === '') {
            return $empty;
        }

        // Tokenize on -, /, ., or space.
        $raw = str_replace(['/', '.', ' '], '-', $raw);
        $raw = trim($raw, '-');
        $tokens = array_values(array_filter(explode('-', $raw), static fn ($t) => $t !== ''));

        if (count($tokens) === 0 || count($tokens) > 3) {
            return self::invalid($raw);
        }

        // Concatenated token: "252013" -> "25" + "2013" (day + year)
        $expanded = [];
        $expandedFlag = false;
        foreach ($tokens as $token) {
            if (strlen($token) === 5 || strlen($token) === 6) {
                $year = substr($token, -4);
                $day  = substr($token, 0, strlen($token) - 4);
                $expanded[] = $day;
                $expanded[] = $year;
                $expandedFlag = true;
            } else {
                $expanded[] = $token;
            }
        }
        $tokens = $expanded;

        // After expansion we should have exactly 3 numeric parts.
        $nums = array_map('intval', $tokens);
        $lengths = array_map('strlen', $tokens);
        if (count($nums) !== 3) {
            return self::invalid($raw);
        }

        if ($lengths === [4, 2, 2]) {
            // Already canonical Y-m-d (round-trip from a prior stage) — accept as-is.
            [$year, $month, $day] = $nums;
        } elseif ($lengths === [2, 2, 4]) {
            // Assume MM-DD-YYYY. Recover MM/DD ambiguity when month > 12.
            [$month, $day, $year] = $nums;
            if ($month > 12 && $day <= 12) {
                [$month, $day] = [$day, $month]; // swap -> DD-MM
            }
        } else {
            return self::invalid($raw);
        }

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $year < 1900 || $year > (int) date('Y')) {
            return self::invalid($raw);
        }

        if (! checkdate($month, $day, $year)) {
            return self::invalid($raw);
        }

        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        // Reject future dates.
        if ($date > date('Y-m-d')) {
            return self::invalid($raw);
        }

        return [
            'value' => $date,
            'ok' => true,
            'warning' => $expandedFlag
                ? 'Date "' . $raw . '" was interpreted as ' . $date . ' — please confirm.'
                : null,
            'display' => $date,
        ];
    }

    /**
     * Compute age from a Y-m-d date string (null when missing/invalid).
     */
    public static function ageFrom(string $date): ?int
    {
        if ($date === '') {
            return null;
        }
        $dob = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (! $dob) {
            return null;
        }
        $now = new \DateTimeImmutable('now');

        return $now->diff($dob)->y;
    }

    /**
     * @return array{value: null, ok: false, warning: string, display: string}
     */
    private static function invalid(string $raw): array
    {
        return [
            'value' => null,
            'ok' => false,
            'warning' => 'Invalid date "' . $raw . '" — fix manually on the review screen.',
            'display' => $raw,
        ];
    }
}
