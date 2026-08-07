<?php

namespace App\Services\Import\Normalizers;

/**
 * Splits a free-text address into address_no / street / barangay / city.
 *
 * Observed format: "Sitio Bilolo, Managpi, Calapan City, Oriental Mindoro"
 *   -> street="Sitio Bilolo", barangay="Managpi", city="Calapan City", province="Oriental Mindoro"
 * plan_holders has NO province column, so province is returned separately and
 * stored only in the mapped JSON.
 *
 * @return array{address_no: string, address_street: string, address_barangay: string, address_city: string, address_province: string, parsed: bool}
 */
class ClientAddressNormalizer
{
    /** Words that commonly appear in Philippine city/municipality names. */
    private const CITY_SUFFIXES = ['City', 'Lungsod'];

    /**
     * @return array{address_no: string, address_street: string, address_barangay: string, address_city: string, address_province: string, parsed: bool}
     */
    public static function splitAddress(?string $raw): array
    {
        $raw = trim((string) $raw);

        $empty = [
            'address_no' => '',
            'address_street' => '',
            'address_barangay' => '',
            'address_city' => '',
            'address_province' => '',
            'parsed' => true,
        ];

        if ($raw === '') {
            return $empty;
        }

        $segments = preg_split('/\s*,\s*/u', $raw) ?: [];
        $segments = array_values(array_filter($segments, static fn ($s) => trim((string) $s) !== ''));

        if (count($segments) === 0) {
            return $empty;
        }

        $out = [
            'address_no' => '',
            'address_street' => '',
            'address_barangay' => '',
            'address_city' => '',
            'address_province' => '',
            'parsed' => true,
        ];

        // Province = last segment.
        $out['address_province'] = trim($segments[count($segments) - 1]);
        array_pop($segments);

        if (count($segments) === 0) {
            return self::fallback($raw);
        }

        // City = last remaining segment (usually ends in "City").
        $last = $segments[count($segments) - 1];
        if (self::looksLikeCity($last)) {
            $out['address_city'] = trim($last);
            array_pop($segments);
        } else {
            // No city marker — treat the whole tail as city and let the review fix it.
            $out['address_city'] = trim($last);
            array_pop($segments);
        }

        if (count($segments) === 0) {
            return $out;
        }

        // Barangay = last remaining segment (usually has a Barangay/Sitio/Purok marker or is the locality).
        $out['address_barangay'] = trim($segments[count($segments) - 1]);
        array_pop($segments);

        // Any remaining segments above the barangay become the street/area line.
        if (count($segments) > 0) {
            $out['address_street'] = trim(implode(', ', $segments));
        }

        // Extract an explicit house number / "No." prefix into address_no.
        self::extractAddressNo($out);

        return $out;
    }

    private static function looksLikeCity(string $segment): bool
    {
        foreach (self::CITY_SUFFIXES as $suffix) {
            if (preg_match('/\b' . preg_quote($suffix, '/') . '\s*$/iu', $segment)) {
                return true;
            }
        }

        // Common known cities not ending in "City" (additive, best-effort).
        $known = ['Calapan', 'Manila', 'Quezon City', 'Mandaluyong', 'Marikina', 'Pasig', 'Makati'];

        return in_array(trim($segment), $known, true);
    }

    private static function extractAddressNo(array &$out): void
    {
        $street = $out['address_street'];
        if ($street === '') {
            return;
        }
        if (preg_match('/^(No\.?\s*|#)?\s*(\d+)\b(.*)$/u', $street, $m)) {
            $out['address_no'] = trim($m[1] . ' ' . $m[2]);
            $rest = trim($m[3]);
            $out['address_street'] = trim($rest, " \t,");
        }
    }

    /**
     * Last-resort: could not split confidently, keep the whole string as street
     * and mark parsed=false so the review screen highlights it.
     */
    private static function fallback(string $raw): array
    {
        return [
            'address_no' => '',
            'address_street' => $raw,
            'address_barangay' => '',
            'address_city' => '',
            'address_province' => '',
            'parsed' => false,
        ];
    }
}
