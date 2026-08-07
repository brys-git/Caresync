<?php

namespace App\Services\Import\Normalizers;

/**
 * Builds deterministic keys used for duplicate detection:
 *  - duplicate_key : exact-ish key (last|first|dob) for in-batch dedupe + lookup.
 *  - phonetic key  : metaphone-based key for fuzzy matching in ClientMatcherService.
 */
class ClientNameKeyGenerator
{
    /**
     * @return array{exact: string, phonetic: string, last: string, first: string}
     */
    public static function buildKeys(
        string $firstName,
        string $lastName,
        ?string $dateOfBirth = null,
        string $nameExtension = ''
    ): array {
        $last = ClientNameNormalizer::comparisonKey($lastName);
        $first = ClientNameNormalizer::comparisonKey($firstName);
        $ext = ClientNameNormalizer::comparisonKey($nameExtension);
        $dob = $dateOfBirth !== null && $dateOfBirth !== '' ? substr($dateOfBirth, 0, 10) : '';

        $exact = trim(implode('|', array_filter([$last, $first, $ext, $dob], static fn ($v) => $v !== '')));

        $phonetic = trim(implode('|', array_filter([
            $last !== '' ? metaphone($last) : '',
            $first !== '' ? metaphone($first) : '',
            $ext !== '' ? metaphone($ext) : '',
        ], static fn ($v) => $v !== '')));

        return [
            'exact' => $exact,
            'phonetic' => $phonetic,
            'last' => $last,
            'first' => $first,
        ];
    }
}
