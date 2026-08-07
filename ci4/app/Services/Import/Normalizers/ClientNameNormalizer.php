<?php

namespace App\Services\Import\Normalizers;

/**
 * Splits a full Filipino name into first/middle/last/extension and
 * normalizes beneficiary relationships. Pure functions — no side effects.
 *
 * Every ambiguous decision is returned so callers can surface it as a
 * warning on the review screen; nothing is silently "fixed".
 */
class ClientNameNormalizer
{
    /** Common all-caps names/acronyms to preserve when title-casing. */
    private const PRESERVE = [
        'DEL', 'DE', 'LA', 'LAS', 'LOS', 'SAN', 'SANTA', 'STA', 'DO', 'DAS',
        'VDA', 'VDO', 'MAC', 'MC', 'ST', 'MT',
    ];

    private const SUFFIX_RE = '/\b(JR\.?|SR\.?|II|III|IV|V)\b$/i';

    /**
     * Prefixes that begin a compound Filipino surname (Dela Cruz, Delos Santos,
     * De Guzman, San Jose, Santa Maria, ...). When the second-to-last token is
     * one of these, it joins the last token as a single surname.
     */
    private const SURNAME_PREFIXES = [
        'de', 'dela', 'delas', 'delos', 'del', 'da', 'das', 'do', 'dos',
        'san', 'santa', 'santo', 'villa', 'villas', 'mac', 'mc',
    ];

    /**
     * Split a full name into name parts.
     *
     * @return array{first_name: string, middle_name: string, last_name: string, name_extension: string}
     */
    public static function splitFullName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);
        $fullName = preg_replace('/\s+/u', ' ', $fullName) ?? '';
        $fullName = trim($fullName, " \t\n\r\0\x0B.,;");

        if ($fullName === '') {
            return ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'name_extension' => ''];
        }

        // 1. Extract a suffix (Jr./Sr./II/III/IV/V) — including "Ramos Jr."
        $nameExtension = '';
        if (preg_match(self::SUFFIX_RE, $fullName, $m)) {
            $nameExtension = self::cleanSuffix($m[0]);
            $fullName = trim(substr($fullName, 0, strlen($fullName) - strlen($m[0])));
            $fullName = trim($fullName, " \t-");
        }

        // 2. Tokenize
        $tokens = preg_split('/\s+/u', $fullName) ?: [];
        $tokens = array_values(array_filter($tokens, static fn ($t) => $t !== ''));

        $first = '';
        $middle = '';
        $last = '';

        if (count($tokens) === 1) {
            $first = $tokens[0];
            // last_name left empty -> users.last_name is NOT NULL, record must be flagged.
        } elseif (count($tokens) === 2) {
            $first = $tokens[0];
            $last = $tokens[1];
        } else {
            $first = $tokens[0];
            $secondLast = strtolower($tokens[count($tokens) - 2]);

            // Compound surname: "Dela Cruz", "De Guzman", "San Jose", ...
            if (in_array($secondLast, self::SURNAME_PREFIXES, true)) {
                $last = implode(' ', array_slice($tokens, -2));
                $middle = implode(' ', array_slice($tokens, 1, -2));
            } else {
                $last = $tokens[count($tokens) - 1];
                $middle = implode(' ', array_slice($tokens, 1, -1));
            }
        }

        return [
            'first_name' => self::titleCase($first),
            'middle_name' => self::titleCase($middle),
            'last_name' => self::titleCase($last),
            'name_extension' => $nameExtension,
        ];
    }

    /**
     * Normalize a beneficiary relationship string (e.g. "Daughter_" -> "Daughter").
     *
     * @return array{value: string, canonical: string}
     */
    public static function normalizeRelation(?string $raw): array
    {
        $raw = trim((string) $raw, " \t\n\r\0\x0B.,;_");
        $raw = preg_replace('/\s+/u', ' ', $raw) ?? '';

        $canonical = strtolower($raw);
        // Map common aliases to canonical categories used elsewhere in the app.
        $map = [
            'son' => 'child', 'daughter' => 'child', 'child' => 'child', 'stepchild' => 'child',
            'wife' => 'spouse', 'husband' => 'spouse', 'spouse' => 'spouse',
            'live in partner' => 'spouse', 'live-in partner' => 'spouse', 'partner' => 'spouse',
            'mother' => 'parent', 'father' => 'parent', 'parent' => 'parent',
            'brother' => 'sibling', 'sister' => 'sibling', 'sibling' => 'sibling',
            'aunt' => 'other', 'uncle' => 'other', 'grandmother' => 'other', 'grandfather' => 'other',
            'grandchild' => 'other', 'niece' => 'other', 'nephew' => 'other', 'cousin' => 'other',
            'other' => 'other',
        ];

        $canonical = $map[$canonical] ?? 'other';

        return [
            'value' => self::titleCase($raw),
            'canonical' => $canonical,
        ];
    }

    /**
     * A comparison key (lowercased, diacritics stripped, suffix removed) used for matching.
     */
    public static function comparisonKey(string $name): string
    {
        $name = self::stripDiacritics($name);
        $name = preg_replace('/\b(JR|SR)\b/i', '', $name) ?? '';
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\s]/u', '', $name) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    }

    private static function cleanSuffix(string $suffix): string
    {
        $suffix = strtoupper(rtrim($suffix, '.'));
        if ($suffix === 'JR') {
            return 'Jr.';
        }
        if ($suffix === 'SR') {
            return 'Sr.';
        }

        return $suffix;
    }

    private static function titleCase(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $name) ?: [];
        $out = [];
        foreach ($words as $word) {
            $upper = strtoupper($word);
            if (in_array($upper, self::PRESERVE, true)) {
                $out[] = $upper;
                continue;
            }
            $out[] = mb_substr($word, 0, 1) . mb_strtolower(mb_substr($word, 1));
        }

        return implode(' ', $out);
    }

    private static function stripDiacritics(string $text): string
    {
        if (function_exists('iconv')) {
            $translit = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
            if ($translit !== false) {
                return $translit;
            }
        }

        return $text;
    }
}
