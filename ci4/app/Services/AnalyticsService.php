<?php

namespace App\Services;

/**
 * AnalyticsService
 *
 * Shared analytics/summary queries for Admin, Branch Admin, Staff, and
 * Collector dashboards. Moved out of App\Controllers\Analytics (which used
 * to keep these as private methods) so App\Controllers\Dashboard can reuse
 * the exact same data instead of duplicating the queries - the 2026-09-09
 * system scan found Dashboard::admin()/staff()/collector() were rendering
 * static "Coming Soon" placeholders while this real, working data was only
 * ever wired into the separate /admin/analytics and /staff/analytics pages.
 */
class AnalyticsService
{
    /** Dashboard charts are cached this long (seconds); clearDashboardCache() empties them early. */
    private const CHART_CACHE_SECONDS = 600;

    /** When each cached chart on this page was built (unix time), so the page can say "Updated hh:mm". */
    private array $builtAt = [];

    /** True once something was written to payments / plan holders / branches in this process. */
    private static bool $dirty = false;

    /**
     * THE one place that keeps the dashboard cache honest. Config\Events calls this for
     * every database query; when a query WRITES to payments, plan_holders or branches
     * (a payment verified or recorded, a plan holder approved, a branch renamed - by
     * any controller, service or command, model or query builder), the cache is cleared
     * once the request is finished, so a transaction that is still open can never
     * leave a stale chart behind.
     */
    public static function noteWrite(string $sql): void
    {
        if (self::$dirty) {
            return;
        }

        if (preg_match('/^\s*(?:INSERT|REPLACE|UPDATE|DELETE)\b[^\n]{0,80}?\b(?:payments|plan_holders|branches)\b/i', $sql) === 1) {
            self::$dirty = true;
            register_shutdown_function(static function (): void {
                self::clearDashboardCache();
            });
        }
    }

    /**
     * Empties every cached dashboard figure (all roles and branches at once). It works
     * by changing a version number that is part of every cache key, so no list of keys
     * has to be kept.
     */
    public static function clearDashboardCache(): void
    {
        try {
            cache()->save('dashboard_cache_version', str_replace('.', '', uniqid('', true)), 0);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard cache could not be cleared: ' . $e->getMessage());
        }
    }

    /**
     * Runs $build once per cache window for this chart and scope (e.g. 'admin_0',
     * 'branch_3') and keeps the result for 10 minutes.
     */
    private function cached(string $name, string $scope, callable $build): array
    {
        try {
            $version = cache('dashboard_cache_version') ?: '1';
            $key = 'dash_' . $name . '_' . $scope . '_' . $version;
            $hit = cache($key);
            if (is_array($hit) && isset($hit['data'], $hit['at'])) {
                $this->builtAt[$name] = (int) $hit['at'];

                return $hit['data'];
            }

            $value = $build();
            $this->builtAt[$name] = time();
            cache()->save($key, ['at' => $this->builtAt[$name], 'data' => $value], self::CHART_CACHE_SECONDS);

            return $value;
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard cache unavailable: ' . $e->getMessage());
            $this->builtAt[$name] = time();

            return $build();
        }
    }

    /** The oldest of the charts built or read on this page (unix time), for "Updated hh:mm". */
    private function oldestBuiltAt(): int
    {
        return $this->builtAt === [] ? time() : min($this->builtAt);
    }

    /**
     * System-wide analytics. Used by both the Admin dashboard and the
     * Admin analytics page.
     */
    public function getSystemWideAnalytics(): array
    {
        $db = db_connect();

        $metrics = new MetricsService();
        $members = $metrics->memberCounts(null);

        // Last 12 calendar months of collected (paid) money, zero-filled (cached 10 minutes).
        $monthlyCollections = $this->cached('monthly', 'admin_0', static function () use ($metrics): array {
            return array_map(
                static fn (array $m): array => ['month' => $m['month'], 'label' => $m['label'], 'amount' => $m['amount']],
                $metrics->lastMonths(null, 12)
            );
        });

        $totalCollections = $metrics->collected(null);
        $collectedThisMonth = $metrics->collected(null, date('Y-m-01'), date('Y-m-t'));

        // Plan holders in every branch, biggest first (cached 10 minutes).
        $branches = $this->cached('branches', 'admin_0', static function () use ($db): array {
            return $db->table('branches b')
                ->select('b.branch_id, b.branch_name, COUNT(DISTINCT ph.plan_holder_id) as member_count')
                ->join('plan_holders ph', 'ph.branch_id = b.branch_id', 'left')
                ->groupBy('b.branch_id, b.branch_name')
                ->orderBy('member_count', 'DESC')
                ->orderBy('b.branch_name', 'ASC')
                ->get()
                ->getResultArray();
        });

        $pendingApprovals = $metrics->pendingApprovals(null);
        $pendingPayments = $metrics->pendingPayments(null);
        $pendingServiceRequests = $metrics->pendingServiceRequests(null);
        $openClaims = $metrics->openClaimPlanHolders(null);

        $totalBranches = $db->table('branches')->countAllResults();
        $branchesWithoutManager = $db->table('branches')->where('manager_user_id', null)->where('status', 'active')->countAllResults();
        $totalStaff = $db->table('users')->where('role_id', 3)->countAllResults();
        $totalCollectors = $db->table('users')->where('role_id', 5)->countAllResults();

        return [
            'package' => $metrics->packageFigures(null),
            'awaiting_avails' => $metrics->availsAwaitingDownPayment(null, 5),
            'total_members' => $members['total_members'],
            'active_members' => $members['active'],
            'delinquent_members' => $members['grace'],
            'suspended_members' => $members['suspended'],
            'overdue_accounts' => $members['overdue_accounts'],
            'total_collections' => $totalCollections,
            'collected_this_month' => $collectedThisMonth,
            'monthly_collections' => $monthlyCollections,
            'members_by_branch' => $branches,
            'pending_approvals' => $pendingApprovals,
            'pending_payments' => $pendingPayments,
            'open_claims' => $openClaims,
            'charts_updated_at' => $this->oldestBuiltAt(),
            'pending_service_requests' => $pendingServiceRequests,
            'total_branches' => $totalBranches,
            'branches_without_manager' => $branchesWithoutManager,
            'total_staff' => $totalStaff,
            'total_collectors' => $totalCollectors,
        ];
    }

    /**
     * Branch-specific figures for the Branch Admin dashboard (members, collections,
     * pending work, service requests by status). The daily-collections chart is
     * cached for 10 minutes per branch.
     */
    public function getBranchAnalytics(int $branchId): array
    {
        $db = db_connect();

        $metrics = new MetricsService();
        $members = $metrics->memberCounts($branchId);

        $currentMonth = date('Y-m');
        $monthStart   = $currentMonth . '-01';
        $monthEnd     = date('Y-m-t', strtotime($monthStart));

        $staffCount = $db->table('users')
            ->where('branch_id', $branchId)
            ->where('role_id', 3)
            ->where('account_status', 'active')
            ->countAllResults();

        // Service applications of this branch by status (pending / approved / rejected).
        $requestCounts = [];
        foreach ($db->table('service_applications sa')
            ->select('sa.status, COUNT(sa.application_id) AS total')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->groupBy('sa.status')
            ->get()
            ->getResultArray() as $row) {
            $requestCounts[(string) $row['status']] = (int) $row['total'];
        }

        $daily = $this->cached('daily', 'branch_' . $branchId . '_' . $currentMonth, static fn (): array => $metrics->collectedByDay($branchId, $currentMonth));

        return [
            'package' => $metrics->packageFigures($branchId),
            'awaiting_avails' => $metrics->availsAwaitingDownPayment($branchId, 5),
            'branch_members' => $members['total_members'],
            'active_members' => $members['active'],
            'members' => $members,
            'overdue_accounts' => $members['overdue_accounts'],
            'daily_collections' => $daily,
            'monthly_total' => $metrics->collected($branchId, $monthStart, $monthEnd),
            'pending_initial_payments' => $metrics->pendingApprovals($branchId),
            'pending_payments' => $metrics->pendingPayments($branchId),
            'service_request_counts' => $requestCounts,
            'pending_service_requests' => (int) ($requestCounts['pending'] ?? 0),
            'staff_count' => $staffCount,
            'charts_updated_at' => $this->oldestBuiltAt(),
        ];
    }
    /**
     * Staff-specific analytics. Used by both the Staff dashboard and the
     * Staff analytics page.
     */
    public function getStaffAnalytics(int $branchId): array
    {
        $db = db_connect();

        $metrics = new MetricsService();
        $today   = date('Y-m-d');

        $assignedServices = $db->table('service_applications sa')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->where('sa.status', 'approved')
            ->where('ph.branch_id', $branchId)
            ->countAllResults();

        return [
            'package' => $metrics->packageFigures($branchId),
            'awaiting_avails' => $metrics->availsAwaitingDownPayment($branchId, 5),
            'staff_members' => $metrics->memberCounts($branchId)['total_members'],
            'assigned_services' => $assignedServices,
            'pending_requests' => $metrics->pendingServiceRequests($branchId),
            'pending_payments' => $metrics->pendingPayments($branchId),
            'cash_payments_today' => $metrics->collectedCount($branchId, $today, $today, ['method' => 'cash']),
            'cash_collected_today' => $metrics->collected($branchId, $today, $today, ['method' => 'cash']),
        ];
    }

    /**
     * Collector summary: what this collector role has actually recorded so
     * far, and their estimated commission. The Collector role has no
     * dedicated collection-entry feature built yet (login/routing/layout
     * only) - this reuses `payments.received_by` (already the "who
     * actually recorded this payment" column relied on by the Reports
     * suite's Collections/Commission reports) and
     * ReportService::COMMISSION_RATE so the number shown here always
     * matches what those reports would say about this collector.
     */
    public function getCollectorSummary(int $userId): array
    {
        $db = db_connect();

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $metrics = new MetricsService();
        $mine    = ['received_by' => $userId];

        $recentPayments = $db->table('payments pay')
            ->select('pay.payment_id, pay.amount, pay.payment_method, pay.payment_date, pay.status, u.first_name, u.last_name')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'left')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'left')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->where('pay.received_by', $userId)
            ->orderBy('pay.payment_id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $monthTotal = $metrics->collected(null, $monthStart, $today, $mine);

        // Fix Prompts Task 4: commission is 10% of verified CASH collected
        // by this collector, not every payment method - a client's own
        // GCash payment (received_by often null anyway) or desk GCash/cash
        // someone else recorded never earns this account commission. Delegates
        // to CommissionService so this dashboard card and the admin Commission
        // Report can never quietly disagree on what counts.
        $commissionTotals = (new CommissionService())->totals($userId, date('Y-m-01'), date('Y-m-d'));

        return [
            'total_collected' => $metrics->collected(null, null, null, $mine),
            'month_collected' => $monthTotal,
            'today_collected' => $metrics->collected(null, $today, $today, $mine),
            'transaction_count' => $metrics->collectedCount(null, null, null, $mine),
            'estimated_commission' => $commissionTotals['commission'],
            'recent_payments' => $recentPayments,
        ];
    }
}
