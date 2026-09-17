<?php

/**
 * CareSync formatting helpers.
 *
 * Load globally in app/Config/Autoload.php:  public $helpers = ['caresync'];
 *
 * These exist because money and status were being formatted ad hoc in 133 views,
 * which is how you end up with "P1,234.00" on one page and "₱1,234" on the next.
 */

if (! function_exists('cs_money')) {
    /**
     * Render a peso figure with tabular numerals so columns align.
     *
     * @param float|int|string|null $amount
     * @param bool                  $sign   false renders the digits with no ₱ prefix
     */
    function cs_money($amount, bool $sign = true, string $class = ''): string
    {
        $value = (float) ($amount ?? 0);
        $cls   = 'cs-money' . ($sign ? '' : ' cs-money--bare')
               . ($value < 0 ? ' cs-money--neg' : '')
               . ($class !== '' ? ' ' . $class : '');

        return '<span class="' . esc($cls, 'attr') . '">'
             . esc(number_format(abs($value), 2))
             . '</span>';
    }
}

if (! function_exists('cs_status')) {
    /**
     * One status vocabulary for the whole system.
     *
     * Every state in CareSync maps to exactly five visual tones, so a badge
     * means the same thing on the ledger as it does on the member record.
     */
    function cs_status(?string $status, ?string $label = null): string
    {
        $key = strtolower(trim((string) $status));

        $tones = [
            // settled / good
            'active' => 'ok', 'approved' => 'ok', 'completed' => 'ok', 'paid' => 'ok',
            'verified' => 'ok', 'available' => 'ok', 'fully_paid' => 'ok', 'remitted' => 'ok',
            'not_due' => 'ok', 'no_balance_due' => 'ok',

            // waiting on someone
            'pending' => 'pending', 'review' => 'pending', 'processing' => 'pending',
            'submitted' => 'pending', 'partial' => 'pending', 'for_approval' => 'pending',
            'awaiting_verification' => 'pending',

            // needs intervention
            'overdue' => 'stop', 'delinquent' => 'stop', 'rejected' => 'stop',
            'cancelled' => 'stop', 'canceled' => 'stop', 'suspended' => 'stop',
            'expired' => 'stop', 'failed' => 'stop', 'lapsed' => 'stop',

            // informational
            'new' => 'info', 'draft' => 'info', 'ongoing' => 'info', 'in_progress' => 'info',
            'due' => 'info',

            // dormant
            'inactive' => 'idle', 'closed' => 'idle', 'archived' => 'idle',
        ];

        $tone = $tones[$key] ?? 'idle';
        $text = $label ?? ucwords(str_replace('_', ' ', $key !== '' ? $key : 'unknown'));

        return '<span class="cs-status cs-status--' . $tone . '">' . esc($text) . '</span>';
    }
}

if (! function_exists('cs_date')) {
    /** Dates read the same everywhere: 14 Sep 2026. */
    function cs_date($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '—';
        }

        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if ($ts === false) {
            return esc((string) $value);
        }

        return esc(date($withTime ? 'j M Y, g:ia' : 'j M Y', $ts));
    }
}

if (! function_exists('cs_coverage_period')) {
    /**
     * "Months Covered" read as an actual period - "September 2026" for one
     * month, "September-October 2026" for two, "December 2026 - January
     * 2027" across a year boundary - instead of a bare count. Derived from
     * the payment's own coverage_start plus months_covered (not
     * coverage_end): coverage_end is an exact day-offset (e.g. coverage
     * starting Sep 14 for 2 months ends Oct 14), so reading its calendar
     * month back out would already be one day into a third month for some
     * start dates. Walking months_covered whole months forward from
     * coverage_start's own month avoids that.
     *
     * Falls back to "N month(s)" for rows predating coverage tracking
     * (coverage_start NULL).
     */
    function cs_coverage_period($coverageStart, int $monthsCovered): string
    {
        $monthsCovered = max(1, $monthsCovered);

        if (empty($coverageStart)) {
            return $monthsCovered . ' month' . ($monthsCovered === 1 ? '' : 's');
        }

        $startTs = is_numeric($coverageStart) ? (int) $coverageStart : strtotime((string) $coverageStart);
        if ($startTs === false) {
            return $monthsCovered . ' month' . ($monthsCovered === 1 ? '' : 's');
        }

        $endTs = strtotime('+' . ($monthsCovered - 1) . ' months', $startTs);

        $startLabel = date('F Y', $startTs);
        $endLabel = date('F Y', $endTs);

        if ($startLabel === $endLabel) {
            return esc($startLabel);
        }

        if (date('Y', $startTs) === date('Y', $endTs)) {
            return esc(date('F', $startTs) . '-' . $endLabel);
        }

        return esc($startLabel . ' - ' . $endLabel);
    }
}

if (! function_exists('can_collect')) {
    /**
     * The single authorization check for every collection-entry route,
     * controller method, and view. Collector is a capability, not a
     * separate account type for Staff: role_id 5 (a dedicated Collector
     * account) always qualifies; role_id 3 (Staff) qualifies only when
     * users.is_collector is set. Never compare role_id/is_collector
     * directly anywhere else - that's how permissions drift.
     *
     * Memoized per user_id for the life of the request: this gets called
     * from route filters and views on the same request, and a Staff
     * lookup shouldn't hit the DB twice for one page load.
     */
    function can_collect(?int $userId = null): bool
    {
        static $cache = [];

        $userId ??= (int) session('user_id');
        $roleId = (int) session('role_id');

        // A caller checking someone other than session's own user can't
        // rely on session('role_id') for that other user - look it up too.
        if ($userId !== (int) session('user_id')) {
            $roleId = (int) (db_connect()->table('users')->select('role_id')->where('user_id', $userId)->get()->getRowArray()['role_id'] ?? 0);
        }

        if ($roleId === 5) {
            return true;
        }

        if ($roleId !== 3 || $userId <= 0) {
            return false;
        }

        if (! array_key_exists($userId, $cache)) {
            $row = db_connect()->table('users')->select('is_collector')->where('user_id', $userId)->get()->getRowArray();
            $cache[$userId] = (bool) ($row['is_collector'] ?? false);
        }

        return $cache[$userId];
    }
}

if (! function_exists('cs_age_from_dob')) {
    /**
     * Age is always derived from date of birth, never typed/stored as its
     * own trusted value (see ClientRegistrationService::
     * validateAndCalculateAge(), the original single-flow version of this
     * same rule - this is the system-wide helper every registration/edit
     * controller and display view uses instead).
     *
     * Whole years between $dob and today, going by DateTime::diff() (already
     * accounts for whether this year's birthday has occurred yet). Returns
     * null for an empty, unparsable, or future date - never a negative or
     * guessed age.
     */
    function cs_age_from_dob(?string $dob): ?int
    {
        $dob = trim((string) $dob);
        if ($dob === '') {
            return null;
        }

        try {
            $birthDate = new \DateTime($dob);
        } catch (\Throwable $e) {
            return null;
        }

        $today = new \DateTime('today');
        if ($birthDate > $today) {
            return null;
        }

        return (int) $birthDate->diff($today)->y;
    }
}

if (! function_exists('cs_initials')) {
    function cs_initials(string $name): string
    {
        $out = '';
        foreach (preg_split('/\s+/', trim($name)) as $part) {
            if ($part !== '' && ctype_alpha($part[0])) {
                $out .= strtoupper($part[0]);
            }
            if (strlen($out) === 2) {
                break;
            }
        }

        return $out !== '' ? $out : '?';
    }
}
