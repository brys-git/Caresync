<?php

namespace App\Services;

/**
 * The ID Control No.: KKM-<BRANCH 3 letters>-<YEAR>-<5 digit running number>, e.g. KKM-CAL-2026-00001.
 *
 * It is assigned ONCE, when a registration is approved (or a plan holder is created already active), and never
 * changes. Nobody types it: PlanHolderModel does not accept it, and the only writer is assign() below.
 *
 *  - one counter per (branch letters, year), in id_control_sequences; the number is handed out inside a
 *    transaction that holds a row lock on that counter (SELECT ... FOR UPDATE), so two approvals at the same
 *    moment can never get the same number;
 *  - plan_holders.id_control_no has a unique index as the last line of defence;
 *  - a plan holder that already has a number keeps it (assign() is idempotent);
 *  - the year is the year of the approval (backfill uses each holder's own approval date).
 */
class IdControlService
{
    public const PLACEHOLDER = 'Assigned after approval';

    /** Three letters for a branch: from a branch code like KKM-CAL-001, else the first letters of its name. */
    public static function branchLetters(array $branch): string
    {
        $code = strtoupper(trim((string) ($branch['branch_code'] ?? '')));
        if (preg_match('/^KKM-([A-Z]{3})(?:-|$)/', $code, $m)) {
            return $m[1];
        }
        $letters = strtoupper((string) preg_replace('/[^A-Za-z]/', '', (string) ($branch['branch_name'] ?? '')));

        return str_pad(substr($letters, 0, 3), 3, 'X');
    }

    public static function format(string $letters, int $year, int $seq): string
    {
        return sprintf('KKM-%s-%04d-%05d', $letters, $year, $seq);
    }

    /** What to print where a number is shown: the number, or "Assigned after approval". */
    public static function label(?string $value): string
    {
        $v = trim((string) $value);

        return $v !== '' ? $v : self::PLACEHOLDER;
    }

    /**
     * Gives the plan holder their ID Control No. if they have none, and returns it. Safe to call again and
     * again; safe inside a caller's transaction (it joins it) or on its own (it opens one).
     *
     * @param ?string $approvedAt date of the approval (decides the year); null = today
     */
    public function assign(int $planHolderId, ?string $approvedAt = null): ?string
    {
        if ($planHolderId <= 0) {
            return null;
        }
        $db = db_connect();
        if (! $db->tableExists('id_control_sequences')) {
            return null;   // the migration has not run yet: nothing to assign
        }

        $db->transBegin();
        try {
            $ph = $db->query('SELECT plan_holder_id, branch_id, id_control_no FROM plan_holders WHERE plan_holder_id = ? FOR UPDATE', [$planHolderId])->getRowArray();
            if (! $ph) {
                $db->transRollback();

                return null;
            }
            if (trim((string) ($ph['id_control_no'] ?? '')) !== '') {
                $db->transCommit();

                return (string) $ph['id_control_no'];
            }

            $branch  = $db->table('branches')->where('branch_id', (int) $ph['branch_id'])->get(1)->getRowArray() ?: [];
            $letters = self::branchLetters($branch);
            $year    = (int) date('Y', strtotime($approvedAt !== null && $approvedAt !== '' ? $approvedAt : 'now'));

            $db->query('INSERT INTO id_control_sequences (branch_code, year, last_seq, updated_at) VALUES (?, ?, 0, NOW()) ON DUPLICATE KEY UPDATE branch_code = branch_code', [$letters, $year]);
            $seq = (int) ($db->query('SELECT last_seq FROM id_control_sequences WHERE branch_code = ? AND year = ? FOR UPDATE', [$letters, $year])->getRowArray()['last_seq'] ?? 0);

            do {
                $seq++;
                $candidate = self::format($letters, $year, $seq);
                $taken = $db->query('SELECT 1 FROM plan_holders WHERE id_control_no = ? LIMIT 1', [$candidate])->getRowArray();
            } while ($taken);

            $db->query('UPDATE id_control_sequences SET last_seq = ?, updated_at = NOW() WHERE branch_code = ? AND year = ?', [$seq, $letters, $year]);
            $db->query('UPDATE plan_holders SET id_control_no = ? WHERE plan_holder_id = ? AND (id_control_no IS NULL OR TRIM(id_control_no) = \'\')', [$candidate, $planHolderId]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Could not assign the ID Control No.');
            }
            $db->transCommit();

            return $candidate;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'IdControlService::assign failed for plan holder ' . $planHolderId . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Active plan holders that have no ID Control No. yet, with the date each was approved and the number they
     * WOULD get, oldest approval first (the backfill order). Nothing is written.
     *
     * @return list<array{plan_holder_id:int, name:string, branch:string, approved_at:string, source:string, proposed:string}>
     */
    public function backfillPlan(): array
    {
        $db = db_connect();
        $rows = $db->query("SELECT ph.plan_holder_id, ph.branch_id, ph.created_at, u.first_name, u.last_name, b.branch_name, b.branch_code,
                (SELECT MIN(pay.verified_at) FROM payments pay JOIN plans pl ON pl.plan_id = pay.plan_id WHERE pl.plan_holder_id = ph.plan_holder_id AND pay.status = 'paid' AND pay.verified_at IS NOT NULL) AS first_verified
            FROM plan_holders ph
            JOIN users u ON u.user_id = ph.user_id
            LEFT JOIN branches b ON b.branch_id = ph.branch_id
            WHERE ph.status = 'active' AND (ph.id_control_no IS NULL OR TRIM(ph.id_control_no) = '')")->getResultArray();

        foreach ($rows as &$r) {
            $r['when']   = $r['first_verified'] ?: $r['created_at'];
            $r['source'] = $r['first_verified'] ? 'first verified payment' : 'registration date';
        }
        unset($r);
        usort($rows, static fn (array $a, array $b): int => [strtotime((string) $a['when']), (int) $a['plan_holder_id']] <=> [strtotime((string) $b['when']), (int) $b['plan_holder_id']]);

        $counters = [];
        $out = [];
        foreach ($rows as $r) {
            $letters = self::branchLetters($r);
            $year = (int) date('Y', strtotime((string) $r['when']));
            $key = $letters . '|' . $year;
            if (! isset($counters[$key])) {
                $row = $db->tableExists('id_control_sequences') ? $db->table('id_control_sequences')->where('branch_code', $letters)->where('year', $year)->get(1)->getRowArray() : null;
                $counters[$key] = (int) ($row['last_seq'] ?? 0);
            }
            do {
                $counters[$key]++;
                $cand = self::format($letters, $year, $counters[$key]);
                $taken = $db->table('plan_holders')->where('id_control_no', $cand)->countAllResults() > 0;
            } while ($taken);
            $out[] = [
                'plan_holder_id' => (int) $r['plan_holder_id'],
                'name' => trim((string) $r['first_name'] . ' ' . (string) $r['last_name']),
                'branch' => (string) ($r['branch_name'] ?? ''),
                'approved_at' => (string) $r['when'],
                'source' => (string) $r['source'],
                'proposed' => $cand,
            ];
        }

        return $out;
    }

    /**
     * Numbers every plan holder in backfillPlan(), in that order, through assign() (so counters and the unique
     * index are respected). Returns the numbers given.
     *
     * @return list<array{plan_holder_id:int, id_control_no:string}>
     */
    public function backfill(): array
    {
        $given = [];
        foreach ($this->backfillPlan() as $p) {
            $no = $this->assign($p['plan_holder_id'], $p['approved_at']);
            if ($no !== null) {
                $given[] = ['plan_holder_id' => $p['plan_holder_id'], 'id_control_no' => $no];
            }
        }

        return $given;
    }
}
