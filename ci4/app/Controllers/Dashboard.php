<?php

namespace App\Controllers;

use App\Helpers\QueryHelper;
use App\Services\ActivityLogService;
use App\Services\AnalyticsService;
use App\Services\PaymentService;
use Config\Database;

class Dashboard extends BaseController
{
    public function index()
    {
        $role = (int) session()->get('role_id');

        if ($role === 1) {
            return redirect()->to('/dashboard/admin');
        } elseif ($role === 2) {
            return redirect()->to('/dashboard/branch-admin');
        } elseif ($role === 3) {
            return redirect()->to('/dashboard/staff');
        } elseif ($role === 5) {
            return redirect()->to('/dashboard/collector');
        }

        return redirect()->to('/client/dashboard');
    }

    public function admin(): string
    {
        // Was a static "Coming Soon" placeholder with no data at all.
        // Reuses the exact same query set as Analytics::admin() (moved into
        // the shared AnalyticsService so both routes stay in sync) - found
        // during the 2026-09-09 system scan.
        $analytics = (new AnalyticsService())->getSystemWideAnalytics();
        $recentActivity = (new ActivityLogService())->getAllLogs(8);

        return view('dashboards/admin', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => 'System Admin Dashboard',
            'page_sub' => 'Membership, collections, and approvals across every branch.',
            'breadcrumb' => ['Dashboard'],
            'analytics' => $analytics,
            'recent_activity' => $recentActivity,
        ]);
    }

    public function branchAdmin(): string
    {
        $branchId = $this->getBranchId();
        $branchInfo = QueryHelper::getBranchInfo($branchId);
        $branchName = $branchInfo['branch_name'] ?? 'Branch Operations Center';
        $operatorName = trim((string) session()->get('first_name') . ' ' . (string) session()->get('last_name'));
        $operatorName = $operatorName !== '' ? $operatorName : 'Branch Admin';

        // Every branch figure is loaded ONCE here (members, collections, pending work,
        // service requests by status) and reused below - nothing is queried twice.
        $analytics = (new AnalyticsService())->getBranchAnalytics($branchId);
        $members = $analytics['members'];
        $memberStats = [
            'total_active'     => $members['active'],
            'total_pending'    => $members['pending_members'],
            'total_delinquent' => $members['grace'],
            'total_suspended'  => $members['suspended'],
        ];
        $serviceRequestCounts = $analytics['service_request_counts'];
        $collectionsThisMonth = $analytics['monthly_total'];
        $todayOperations = $this->getTodayServiceCalendar($branchId);
        $upcomingServices = $this->countUpcomingServices($branchId);
        $ongoingServices = (new \App\Services\MetricsService())->ongoingServices($branchId);
        $staffOnDuty = $this->countStaffOnDuty($branchId);
        $pendingApprovals = $analytics['pending_initial_payments'];
        $recentPayments = array_slice((new PaymentService())->getBranchMonitoringPayments($branchId), 0, 8);
        $unassignedServices = $this->countUnassignedServicesToday($branchId);
        $activityLogs = $this->getRecentBranchActivity($branchId, 8);
        $paymentAnalytics = $this->getPaymentAnalytics($branchId, 6, (float) $analytics['monthly_total']);

        return view('dashboards/branch_admin', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => 'Branch Operations Control Center',
            'page_sub' => 'Operational dashboard for branch performance, approvals, payments, and service readiness.',
            'breadcrumb' => ['Dashboard'],
            'branch_name' => $branchName,
            'operator_name' => $operatorName,
            'current_time' => (new \DateTime())->format('l, F j, Y H:i'),
            'member_stats' => $memberStats,
            'pending_approvals' => $pendingApprovals,
            'collections_this_month' => $collectionsThisMonth,
            'service_request_counts' => $serviceRequestCounts,
            'today_operations' => $todayOperations,
            'upcoming_services' => $upcomingServices,
            'ongoing_services' => $ongoingServices,
            'staff_on_duty' => $staffOnDuty,
            'recent_payments' => $recentPayments,
            'analytics' => $analytics,
            'unassigned_services' => $unassignedServices,
            'activity_logs' => $activityLogs,
            'payment_analytics' => $paymentAnalytics,
            'collection_alerts' => $this->collectionAlerts($branchId),
        ]);
    }

    public function staff(): string
    {
        // Was a static "Coming Soon" placeholder with no data at all.
        // Found during the 2026-09-09 system scan.
        $branchId = (int) session('branch_id');
        $analytics = (new AnalyticsService())->getStaffAnalytics($branchId);

        return view('dashboards/staff', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => 'Staff Dashboard',
            'page_sub' => 'Your branch\'s membership, payments, and service activity.',
            'breadcrumb' => ['Dashboard'],
            'analytics' => $analytics,
        ]);
    }

    public function collector(): string
    {
        // Was a static "Coming Soon" placeholder with no data at all.
        // The Collector role has no dedicated collection-entry feature
        // built yet, so this reuses payments.received_by - the same
        // "who actually recorded this payment" column the Reports suite's
        // Collections/Commission reports already rely on - to show what
        // this collector has actually recorded. Found during the
        // 2026-09-09 system scan.
        $userId = (int) session('user_id');
        $summary = (new AnalyticsService())->getCollectorSummary($userId);

        return view('dashboards/collector', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => 'Collector Dashboard',
            'page_sub' => 'What you\'ve collected and what\'s still due.',
            'breadcrumb' => ['Dashboard'],
            'summary' => $summary,
        ]);
    }

    private function resolveLayoutView(): string
    {
        $role = (int) session()->get('role_id');

        if ($role === 1) {
            return 'layouts/admin';
        } elseif ($role === 2) {
            return 'layouts/branch_admin';
        } elseif ($role === 3) {
            return 'layouts/staff';
        } elseif ($role === 5) {
            return 'layouts/collector';
        }

        return 'layouts/plan_holder';
    }

    private function getBranchId(): int
    {
        return (int) session()->get('branch_id');
    }

    /**
     * What the Branch Admin should chase about collectors: money collected but not remitted within the limit, and a
     * month a collector has not closed by the due day.
     *
     * @return array{unremitted:list<array<string,mixed>>, unclosed:list<array<string,mixed>>, max_days:int}
     */
    private function collectionAlerts(int $branchId): array
    {
        $svc = new \App\Services\CollectionMonthService();

        return [
            'unremitted' => $svc->unremittedPastLimit($branchId),
            'unclosed' => $svc->unclosedMonths($branchId),
            'max_days' => (int) config('CareSync')->remittanceMaxDays,
        ];
    }

    private function getTodayServiceCalendar(int $branchId): array
    {
        if ($branchId <= 0 || ! $this->serviceCalendarExists()) {
            return [];
        }

        return db_connect()->table('service_calendar sc')
            ->select('sc.calendar_id, sc.event_type, sc.event_date, sc.event_time, sc.location, sc.status, sc.assigned_staff_ids, ph.unique_identifier, u.first_name, u.last_name, COALESCE(sl.service_name, sc.event_type) AS event_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = sc.plan_holder_id', 'left')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = sc.service_id', 'left')
            ->where('sc.branch_id', $branchId)
            ->where('sc.event_date', date('Y-m-d'))
            ->whereIn('sc.status', ['scheduled', 'in-progress'])
            ->orderBy('sc.event_time', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }

    private function countUpcomingServices(int $branchId): int
    {
        if ($branchId <= 0 || ! $this->serviceCalendarExists()) {
            return 0;
        }

        return (int) db_connect()->table('service_calendar')
            ->where('branch_id', $branchId)
            ->where('event_date >=', date('Y-m-d'))
            ->where('event_date <=', date('Y-m-d', strtotime('+7 days')))
            ->where('status', 'scheduled')
            ->countAllResults();
    }

    private function serviceCalendarExists(): bool
    {
        return db_connect()->tableExists('service_calendar');
    }

    private function staffSchedulesExists(): bool
    {
        return db_connect()->tableExists('staff_schedules');
    }

    private function countStaffOnDuty(int $branchId): int
    {
        if ($branchId <= 0 || ! $this->staffSchedulesExists()) {
            return 0;
        }

        return (int) db_connect()->table('staff_schedules')
            ->where('branch_id', $branchId)
            ->where('schedule_date', date('Y-m-d'))
            ->whereIn('status', ['scheduled', 'assigned'])
            ->countAllResults();
    }

    /** Scheduled service events today that have no crew assigned yet. */
    private function countUnassignedServicesToday(int $branchId): int
    {
        if ($branchId <= 0 || ! $this->serviceCalendarExists()) {
            return 0;
        }

        return (int) db_connect()->table('service_calendar')
            ->where('branch_id', $branchId)
            ->where('event_date', date('Y-m-d'))
            ->where('status', 'scheduled')
            ->groupStart()
                ->where('assigned_staff_ids', null)
                ->orWhere('assigned_staff_ids', '[]')
            ->groupEnd()
            ->countAllResults();
    }
    private function getRecentBranchActivity(int $branchId, int $limit = 8): array
    {
        if ($branchId <= 0) {
            return [];
        }

        return db_connect()->table('activity_logs al')
            ->select('al.*, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = al.user_id', 'left')
            ->where('u.branch_id', $branchId)
            ->orderBy('al.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    private function getPaymentAnalytics(int $branchId, int $months = 6, ?float $monthTotal = null): array
    {
        if ($branchId <= 0) {
            return [
                'months' => [],
                'totals' => [],
                'method_breakdown' => [],
            ];
        }

        $db = db_connect();
        $startDate = (new \DateTime(sprintf('first day of -%d months', $months - 1)))->format('Y-m-01');
        $labels = [];
        $totals = [];

        for ($monthOffset = 0; $monthOffset < $months; $monthOffset++) {
            $date = (new \DateTime($startDate))->modify(sprintf('+%d months', $monthOffset));
            $label = $date->format('Y-m');
            $labels[$label] = $date->format('M Y');
            $totals[$label] = 0.0;
        }

        // Same paid-only, branch-scoped figures as analytics and reports.
        $metrics = new \App\Services\MetricsService();
        foreach ($metrics->collectedByMonth($branchId, $startDate, date('Y-m-t')) as $row) {
            if (array_key_exists($row['month_key'], $totals)) {
                $totals[$row['month_key']] = $row['total_amount'];
            }
        }

        $monthStart = date('Y-m-01');
        $methodRows = [];
        foreach (['cash', 'gcash'] as $method) {
            $amount = $metrics->collected($branchId, $monthStart, date('Y-m-t'), ['method' => $method]);
            if ($amount > 0) {
                $methodRows[] = ['payment_method' => $method, 'total' => $amount];
            }
        }

        $methodBreakdown = [];
        // Share of THIS month's collections (the method rows are this month only).
        $monthlyTotal = $monthTotal ?? $metrics->collected($branchId, $monthStart, date('Y-m-t'));
        foreach ($methodRows as $row) {
            $methodBreakdown[] = [
                'method' => $row['payment_method'] ?: 'Other',
                'total' => (float) $row['total'],
                'share' => $monthlyTotal > 0 ? round(((float) $row['total'] / $monthlyTotal) * 100, 0) : 0,
            ];
        }

        return [
            'months' => $labels,
            'totals' => $totals,
            'method_breakdown' => $methodBreakdown,
        ];
    }
}
