<?php

namespace App\Services;

/**
 * IdVerificationService
 *
 * Server-side government-ID verification helpers for plan-holder registration.
 *
 * Honest-labeling contract: this service only checks that (a) the ID type is a
 * supported government ID and (b) the OCR-extracted text appears consistent with
 * the applicant's declared details. It CANNOT prove an ID is authentic — the
 * result is "appears consistent", never "officially verified". Staff
 * confirmation remains the authority (plan_holders.id_verification_status).
 *
 * The OCR itself runs in the browser (Tesseract.js, bundled locally). The
 * extracted text is re-scored HERE on the server from the submitted ocr_text,
 * so a client cannot flip the result without submitting consistent text.
 */
class IdVerificationService
{
    /** Minimum match score for Level 2 ("appears consistent") */
    public const MATCH_THRESHOLD = 55.0;

    /**
     * Supported government ID types.
     *
     * @return array<string, array{label:string, pattern:string, min:int, max:int}>
     */
    public static function supportedIds(): array
    {
        return [
            'philsys'        => ['label' => 'PhilSys National ID', 'pattern' => '/^[0-9]{4}[\s-]?[0-9]{4}[\s-]?[0-9]{4}$/', 'min' => 10, 'max' => 16],
            'drivers_license' => ['label' => "Driver's License", 'pattern' => '/^[A-Z]{1,3}[0-9]{1,2}-?[0-9]{4,12}$/i', 'min' => 5, 'max' => 20],
            'passport'       => ['label' => 'Passport', 'pattern' => '/^[A-Z0-9]{5,12}$/i', 'min' => 5, 'max' => 12],
            'umid'           => ['label' => 'UMID', 'pattern' => '/^[0-9]{6,12}$/', 'min' => 6, 'max' => 12],
            'prc'            => ['label' => 'PRC ID', 'pattern' => '/^[0-9]{5,10}$/', 'min' => 5, 'max' => 10],
            'sss'            => ['label' => 'SSS ID', 'pattern' => '/^[0-9]{2}-?[0-9]{7}-?[0-9]{1}$/', 'min' => 10, 'max' => 12],
            'tin'            => ['label' => 'TIN ID', 'pattern' => '/^[0-9]{3}-?[0-9]{3}-?[0-9]{3}(-?[0-9]{3})?$/', 'min' => 9, 'max' => 15],
            'postal'         => ['label' => 'Postal ID', 'pattern' => '/^[A-Z0-9]{5,15}$/i', 'min' => 5, 'max' => 15],
            'voters'         => ['label' => "Voter's ID", 'pattern' => '/^[A-Z0-9]{4,15}$/i', 'min' => 4, 'max' => 15],
            'barangay'       => ['label' => 'Barangay Clearance', 'pattern' => '/^[A-Z0-9\/\-]{4,20}$/i', 'min' => 4, 'max' => 20],
            'senior_citizen' => ['label' => 'Senior Citizen ID', 'pattern' => '/^[A-Z0-9\/\-]{4,20}$/i', 'min' => 4, 'max' => 20],
        ];
    }

    /** @return list<string> */
    public static function supportedIdKeys(): array
    {
        return array_keys(self::supportedIds());
    }

    public static function isSupportedIdType(string $type): bool
    {
        return isset(self::supportedIds()[strtolower(trim($type))]);
    }

    public static function idTypeLabel(string $type): string
    {
        $info = self::supportedIds()[strtolower(trim($type))] ?? null;

        return $info['label'] ?? $type;
    }

    /**
     * Loose sanity check that an ID number matches the expected shape for a type.
     */
    public static function validateIdNumber(string $type, string $number): bool
    {
        $info = self::supportedIds()[strtolower(trim($type))] ?? null;
        if (! $info) {
            return false;
        }

        $number = trim($number);
        $length = mb_strlen($number);
        if ($length < $info['min'] || $length > $info['max']) {
            return false;
        }

        $normalized = preg_replace('/[\s\-]/', '', $number) ?? $number;

        return (bool) (preg_match($info['pattern'], $number) || preg_match($info['pattern'], $normalized));
    }

    /**
     * Score how consistent the OCR-extracted text is with the applicant's details.
     *
     * @param array{first_name?:string,middle_name?:string,last_name?:string,date_of_birth?:string,address?:string} $applicant
     */
    public static function verifyMatch(string $ocrText, array $applicant, string $idType = '', string $idNumber = ''): float
    {
        $normalized = self::normalizeText($ocrText);
        if ($normalized === '') {
            return 0.0;
        }

        $lastName = self::normalizeText((string) ($applicant['last_name'] ?? ''));
        $firstName = self::normalizeText((string) ($applicant['first_name'] ?? ''));
        $middleName = self::normalizeText((string) ($applicant['middle_name'] ?? ''));
        $dob = (string) ($applicant['date_of_birth'] ?? '');
        $address = self::normalizeText((string) ($applicant['address'] ?? ''));

        $score = 0.0;

        // Last name is the strongest signal.
        if ($lastName !== '' && self::containsPhrase($normalized, $lastName)) {
            $score += 30;
        }

        // First name: accept the full phrase or at least its first token.
        if ($firstName !== '' && (self::containsPhrase($normalized, $firstName) || self::containsToken($normalized, $firstName))) {
            $score += 25;
        }

        // Middle name or its initial.
        if ($middleName !== '') {
            $initial = self::initialOf($middleName);
            if (self::containsPhrase($normalized, $middleName) || ($initial !== '' && self::containsToken($normalized, $initial))) {
                $score += 5;
            }
        }

        if ($dob !== '' && self::containsDob($normalized, $dob)) {
            $score += 20;
        }

        if ($idNumber !== '') {
            $compactOcr = preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
            $compactId = preg_replace('/[^a-z0-9]/i', '', $idNumber) ?? '';
            if ($compactId !== '' && $compactOcr !== '' && strpos($compactOcr, strtolower($compactId)) !== false) {
                $score += 10;
            }
        }

        if ($address !== '' && self::containsAddressToken($normalized, $address)) {
            $score += 10;
        }

        // Hard rule: without the applicant's last name in the document, the
        // document cannot be considered consistent.
        if ($lastName === '' || ! self::containsPhrase($normalized, $lastName)) {
            $score = min($score, self::MATCH_THRESHOLD - 1);
        }

        return round(min(100.0, max(0.0, $score)), 2);
    }

    /**
     * Lowercase, keep only letters/digits, collapse whitespace.
     */
    public static function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private static function containsPhrase(string $haystack, string $needle): bool
    {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }

    private static function containsToken(string $haystack, string $phrase): bool
    {
        $firstToken = explode(' ', trim($phrase))[0] ?? '';

        return $firstToken !== '' && mb_strlen($firstToken) >= 2 && strpos($haystack, $firstToken) !== false;
    }

    private static function initialOf(string $name): string
    {
        $first = explode(' ', trim($name))[0] ?? '';

        return $first === '' ? '' : mb_substr($first, 0, 1);
    }

    private static function containsDob(string $normalized, string $dob): bool
    {
        $ts = strtotime($dob);
        if ($ts === false) {
            return false;
        }

        $y = date('Y', $ts);
        $mm = str_pad((string) date('n', $ts), 2, '0', STR_PAD_LEFT);
        $dd = str_pad((string) date('j', $ts), 2, '0', STR_PAD_LEFT);

        // normalized text uses spaces where separators were, plus a compact form.
        $candidates = [
            $y . ' ' . $mm . ' ' . $dd,  // YYYY-MM-DD / YYYY/MM/DD
            $mm . ' ' . $dd . ' ' . $y,  // MM/DD/YYYY
            $dd . ' ' . $mm . ' ' . $y,  // DD/MM/YYYY (OCR is noisy)
            $y . $mm . $dd,              // YYYYMMDD
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && strpos($normalized, $candidate) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function containsAddressToken(string $normalized, string $address): bool
    {
        $tokens = preg_split('/\s+/', trim($address)) ?: [];
        foreach ($tokens as $token) {
            // Ignore very short/common words to avoid false positives.
            if (mb_strlen($token) >= 4 && strpos($normalized, $token) !== false) {
                return true;
            }
        }

        return false;
    }
}
