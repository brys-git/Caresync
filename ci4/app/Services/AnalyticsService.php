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
    /**
     * System-wide analytics. Used by both the Admin dashboard and the
     * Admin analytics page.
     */
    public function getSystemWideAnalytics(): array
    {
        $db = db_connect();

        $metrics = new MetricsService();
        $members = $metrics->memberCounts(null);

        // Last 12 calendar months of collected (paid) money, zero-filled.
        $monthlyCollections = array_map(
            static fn (array $m): array => ['month' => $m['month'], 'amount' => $m['amount']],
            $metrics->lastMonths(null, 12)
        );

        $totalCollections = $metrics->collected(null);

        $branches = $db->table('branches b')
            ->select('b.branch_id, b.branch_name, COUNT(DISTINCT ph.plan_holder_id) as member_count')
            ->join('plan_holders ph', 'ph.branch_id = b.branch_id', 'left')
            ->groupBy('b.branch_id')
            ->orderBy('member_count', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $pendingApprovals = $metrics->pendingApprovals(null);
        $pendingServiceRequests = $metrics->pendingServiceRequests(null);

        $totalBranches = $db->table('branches')->countAllResults();
        $branchesWithoutManager = $db->table('branches')->where('manager_user_id', null)->where('status', 'active')->countAllResults();
        $totalStaff = $db->table('users')->where('role_id', 3)->countAllResults();
        $totalCollectors = $db->table('users')->where('role_id', 5)->countAllResults();

        return [
            'total_members' => $members['total_members'],
            'active_members' => $members['active'],
            'delinquent_members' => $members['grace'],
            'suspended_members' => $members['suspended'],
            'overdue_accounts' => $members['overdue_accounts'],
            'total_collections' => $totalCollections,
            'monthly_collections' => $monthlyCollections,
            'top_branches' => $branches,
            'pending_approvals' => $pendingApprovals,
            'pending_service_requests' => $pendingServiceRequests,
            'total_branches' => $totalBranches,
            'branches_without_manager' => $branchesWithoutManager,
            'total_staff' => $totalStaff,
            'total_collectors' => $totalCollectors,
        ];
    }

    /**
     * Branch-specific analytics. Used by Branch Admin's analytics page
     * (Branch Admin's dashboard has its own, richer, separately-built
     * operations-center query set in Dashboard::branchAdmin()).
     */
    public function getBranchAnalytics(int $branchId): array
    {
        $db = db_connect();

        $metrics = new MetricsService();
        $members = $metrics->memberCounts($branchId);

        $currentMonth = date('Y-m');
        $monthStart   = $currentMonth . '-01';
        $monthEnd     = date('Y-m-t', strtotime($monthStart));

        $ongoingServices = $db->table('service_schedules')
            ->where('branch_id', $branchId)
            ->where('status', 'in-progress')
            ->countAllResults();

        $staffCount = $db->table('users')
            ->where('branch_id', $branchId)
            ->where('role_id', 3)
            ->where('account_status', 'active')
            ->countAllResults();

        return [
            'branch_members' => $members['total_members'],
            'active_members' => $members['active'],
            'overdue_accounts' => $members['overdue_accounts'],
            'daily_collections' => $metrics->collectedByDay($branchId, $currentMonth),
            'monthly_total' => $metrics->collected($branchId, $monthStart, $monthEnd),
            'pending_initial_payments' => $metrics->pendingApprovals($branchId),
            'pending_service_requests' => $metrics->pendingServiceRequests($branchId),
            'ongoing_services' => $ongoingServices,
            'staff_count' => $staffCount,
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
            'staff_members' => $metrics->memberCounts($branchId)['total_members'],
            'assigned_services' => $assignedServices,
            'pending_requests' => $metrics->pendingServiceRequests($branchId),
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
