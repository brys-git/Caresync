<?php

namespace App\Controllers;

use App\Helpers\QueryHelper;
use App\Services\ActivityLogService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Services\NotificationService;
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
        }

        return redirect()->to('/dashboard/plan-holder');
    }

    public function admin(): string
    {
        return view('dashboards/admin', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => null,
            'breadcrumb' => ['Dashboard'],
        ]);
    }

    public function adminData()
    {
        if ((int) session()->get('role_id') !== 1) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized access.',
            ]);
        }

        $this->response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $this->buildAdminDashboardData(),
        ]);
    }

    private function buildAdminDashboardData(): array
    {
        $db = db_connect();
        $settingsService = new SettingsService();

        $totalPlanHolders = $db->tableExists('plan_holders') ? (int) $db->table('plan_holders')->countAllResults() : 0;
        $activeBeneficiaries = $db->tableExists('beneficiaries') ? (int) $db->table('beneficiaries')->countAllResults() : 0;
        $pendingApplications = $db->tableExists('service_applications') ? (int) $db->table('service_applications')->where('status', 'pending')->countAllResults() : 0;

        $monthlyCollections = 0.0;
        if ($db->tableExists('payments')) {
            $row = $db->table('payments')
                ->selectSum('amount', 'total')
                ->where('status', 'approved')
                ->where('DATE(payment_date) >=', date('Y-m-01'))
                ->where('DATE(payment_date) <=', date('Y-m-t'))
                ->get()
                ->getRowArray();
            $monthlyCollections = (float) ($row['total'] ?? 0);
        }

        $activeBranches = $db->tableExists('branches') ? (int) $db->table('branches')->where('status', 'active')->countAllResults() : 0;
        $activeStaff = $db->tableExists('users') ? (int) $db->table('users')->whereIn('role_id', [2, 3])->where('status', 'active')->countAllResults() : 0;
        $unreadNotifications = $db->tableExists('notifications') ? (int) $db->table('notifications')->where('is_read', 0)->countAllResults() : 0;

        $months = [];
        $totals = [];
        for ($i = 0; $i < 12; $i++) {
            $date = new \DateTime(sprintf('first day of +%d months', $i));
            $date->modify('January 1 +' . $i . ' months');
            $label = $date->format('M');
            if (! in_array($label, $months, true)) {
                $months[] = $label;
                $totals[$label] = 0.0;
            }
        }
        if ($db->tableExists('payments')) {
            $result = $db->table('payments')
                ->select("DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total")
                ->where('status', 'approved')
                ->where('YEAR(payment_date)', date('Y'))
                ->groupBy('month')
                ->get()
                ->getResultArray();

            foreach ($result as $row) {
                $monthKey = (new \DateTime($row['month'] . '-01'))->format('M');
                if (isset($totals[$monthKey])) {
                    $totals[$monthKey] = (float) $row['total'];
                }
            }
        }

        // KPI month-over-month trends
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));
        $kpiTrends = [];
        $trendSpecs = [
            'total_plan_holders' => ['table' => 'plan_holders', 'col' => 'created_at'],
            'active_beneficiaries' => ['table' => 'beneficiaries', 'col' => 'created_at'],
            'pending_applications' => ['table' => 'service_applications', 'col' => 'created_at', 'where_col' => 'status', 'where_val' => 'pending'],
            'active_branches' => ['table' => 'branches', 'col' => 'created_at', 'where_col' => 'status', 'where_val' => 'active'],
            'active_staff' => ['table' => 'users', 'col' => 'created_at'],
        ];
        foreach ($trendSpecs as $key => $spec) {
            try {
                if ($db->tableExists($spec['table']) && $db->fieldExists($spec['col'], $spec['table'])) {
                    $kpiTrends[$key] = $this->computeKpiTrend(
                        $db,
                        $spec['table'],
                        $spec['col'],
                        $currentMonth,
                        $previousMonth,
                        $spec['where_col'] ?? null,
                        $spec['where_val'] ?? null
                    );
                }
            } catch (\Throwable $e) {
                log_message('error', "KPI trend error ({$key}): " . $e->getMessage());
            }
        }
        try {
            if ($db->tableExists('payments')) {
                $kpiTrends['monthly_collections'] = $this->computePaymentTrend($db, $currentMonth, $previousMonth);
            }
        } catch (\Throwable $e) {
            log_message('error', 'KPI trend error (monthly_collections): ' . $e->getMessage());
        }

        $appStatusCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'under_review' => 0];
        if ($db->tableExists('service_applications')) {
            $rows = $db->table('service_applications')
                ->select('status, COUNT(application_id) AS total')
                ->groupBy('status')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $key = $row['status'] ?? '';
                if ($key !== '') {
                    $appStatusCounts[$key] = (int) $row['total'];
                }
            }
        }

        $pendingList = [];
        if ($db->tableExists('service_applications')) {
            $pendingList = $db->table('service_applications sa')
                ->select('sa.application_id, sa.plan_holder_id, sa.beneficiary_name, sa.created_at, ph.branch_id, ph.unique_identifier, u.first_name, u.last_name, b.branch_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'left')
                ->join('users u', 'u.user_id = ph.user_id', 'left')
                ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
                ->where('sa.status', 'pending')
                ->orderBy('sa.created_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        }

        $branchPerformance = [];
        if ($db->tableExists('branches')) {
            $branches = $db->table('branches')->orderBy('branch_name', 'ASC')->get()->getResultArray();
            foreach ($branches as $branch) {
                $branchId = (int) ($branch['branch_id'] ?? 0);
                $planCount = $db->tableExists('plan_holders') ? (int) $db->table('plan_holders')->where('branch_id', $branchId)->countAllResults() : 0;
                $paymentsTotal = 0.0;
                if ($db->tableExists('payments')) {
                    $row = $db->table('payments pay')
                        ->selectSum('pay.amount', 'total')
                        ->join('plans p', 'p.plan_id = pay.plan_id', 'left')
                        ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'left')
                        ->where('ph.branch_id', $branchId)
                        ->where('pay.status', 'approved')
                        ->get()
                        ->getRowArray();
                    $paymentsTotal = (float) ($row['total'] ?? 0);
                }
                $applications = $db->tableExists('service_applications')
                    ? (int) $db->table('service_applications sa')
                        ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'left')
                        ->where('ph.branch_id', $branchId)
                        ->countAllResults()
                    : 0;

                $branchPerformance[] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branch['branch_name'] ?? ($branch['branch'] ?? 'Branch'),
                    'plan_holders' => $planCount,
                    'payments' => $paymentsTotal,
                    'applications' => $applications,
                ];
            }
        }

        $recentActivity = [];
        if ($db->tableExists('activity_logs')) {
            $recentActivity = $db->table('activity_logs al')
                ->select('al.*, u.first_name, u.last_name')
                ->join('users u', 'u.user_id = al.user_id', 'left')
                ->orderBy('al.created_at', 'DESC')
                ->limit(12)
                ->get()
                ->getResultArray();
        }

        $topBranch = 'N/A';
        $lowestCollectionBranch = 'N/A';
        $mostPendingBranch = 'N/A';
        if (! empty($branchPerformance)) {
            $sortedByPayments = $branchPerformance;
            usort($sortedByPayments, static fn ($left, $right) => ($right['payments'] ?? 0) <=> ($left['payments'] ?? 0));
            $topBranch = $sortedByPayments[0]['branch_name'] ?? 'N/A';
            $lowestCollectionBranch = $sortedByPayments[count($sortedByPayments) - 1]['branch_name'] ?? 'N/A';

            $sortedByApplications = $branchPerformance;
            usort($sortedByApplications, static fn ($left, $right) => ($right['applications'] ?? 0) <=> ($left['applications'] ?? 0));
            $mostPendingBranch = $sortedByApplications[0]['branch_name'] ?? 'N/A';
        }

        $lastBackup = $settingsService->get('last_backup', null);
        $storageUsage = $settingsService->get('storage_usage', null);
        $maintenanceMode = (int) $settingsService->get('enable_maintenance_mode', 0);

        return [
            'kpis' => [
                'total_plan_holders' => $totalPlanHolders,
                'active_beneficiaries' => $activeBeneficiaries,
                'pending_applications' => $pendingApplications,
                'monthly_collections' => $monthlyCollections,
                'active_branches' => $activeBranches,
                'active_staff' => $activeStaff,
                'unread_notifications' => $unreadNotifications,
            ],
            'payment_trend' => [
                'months' => $months,
                'totals' => array_values($totals),
            ],
            'kpi_trends' => $kpiTrends,
            'app_status_counts' => $appStatusCounts,
            'pending_list' => $pendingList,
            'branch_performance' => $branchPerformance,
            'recent_activity' => $recentActivity,
            'system_health' => [
                'database_status' => $db->connID ? 'Online' : 'Offline',
                'last_backup' => $lastBackup,
                'storage_usage' => $storageUsage,
                'server_status' => $maintenanceMode === 1 ? 'Maintenance' : 'Healthy',
            ],
            'highlights' => [
                'top_branch' => $topBranch,
                'lowest_collection_branch' => $lowestCollectionBranch,
                'most_pending_branch' => $mostPendingBranch,
            ],
        ];
    }

    public function branchAdmin(): string
    {
        $branchId = $this->getBranchId();
        $branchInfo = QueryHelper::getBranchInfo($branchId);
        $branchName = $branchInfo['branch_name'] ?? 'Branch Operations Center';
        $operatorName = trim((string) session()->get('first_name') . ' ' . (string) session()->get('last_name'));
        $operatorName = $operatorName !== '' ? $operatorName : 'Branch Admin';

        $memberStats = QueryHelper::getMemberStats($branchId);
        $serviceRequestCounts = $this->getServiceRequestCounts($branchId);
        $collectionsThisMonth = QueryHelper::getTotalCollections($branchId, date('Y-m-01'), date('Y-m-t'));
        $todayOperations = $this->getTodayServiceCalendar($branchId);
        $upcomingServices = $this->countUpcomingServices($branchId);
        $ongoingServices = $this->countServiceCalendarEvents($branchId, ['scheduled', 'in-progress']);
        $staffOnDuty = $this->countStaffOnDuty($branchId);
        $pendingApprovals = QueryHelper::countPendingApprovals($branchId);
        $recentPayments = array_slice((new PaymentService())->getBranchMonitoringPayments($branchId), 0, 8);
        $paymentAlerts = $this->getPaymentAlerts($branchId, $memberStats, $serviceRequestCounts);
        $activityLogs = $this->getRecentBranchActivity($branchId, 8);
        $paymentAnalytics = $this->getPaymentAnalytics($branchId, 6);

        return view('dashboards/branch_admin', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => null,
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
            'payment_alerts' => $paymentAlerts,
            'activity_logs' => $activityLogs,
            'payment_analytics' => $paymentAnalytics,
        ]);
    }

    public function staff(): string
    {
        $branchId = (int) session('branch_id');
        $db = db_connect();

        // Client stats
        $totalClients = 0;
        $activeClients = 0;
        if ($db->tableExists('plan_holders')) {
            $totalClients = (int) $db->table('plan_holders')->where('branch_id', $branchId)->countAllResults();
            $activeClients = (int) $db->table('plan_holders')->where('branch_id', $branchId)->where('status', 'active')->countAllResults();
        }

        // Payment stats
        $totalCollections = 0.0;
        $pendingPayments = 0;
        if ($db->tableExists('payments')) {
            $row = $db->table('payments')
                ->selectSum('amount', 'total')
                ->where('status', 'paid')
                ->where('branch_id', $branchId)
                ->where('DATE(payment_date) >=', date('Y-m-01'))
                ->get()
                ->getRowArray();
            $totalCollections = (float) ($row['total'] ?? 0);

            $pendingPayments = (int) $db->table('payments')
                ->where('branch_id', $branchId)
                ->where('status', 'pending')
                ->countAllResults();
        }

        // Service requests
        $serviceRequests = 0;
        $ongoingServices = 0;
        if ($db->tableExists('service_applications')) {
            $serviceRequests = (int) $db->table('service_applications sa')
                ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'left')
                ->where('ph.branch_id', $branchId)
                ->where('sa.status', 'pending')
                ->countAllResults();
        }
        if ($db->tableExists('service_calendar')) {
            $ongoingServices = (int) $db->table('service_calendar')
                ->where('branch_id', $branchId)
                ->whereIn('status', ['scheduled', 'in-progress'])
                ->countAllResults();
        }

        // Recent activity
        $recentActivity = [];
        if ($db->tableExists('activity_logs')) {
            $recentActivity = $db->table('activity_logs al')
                ->select('al.*, u.first_name, u.last_name')
                ->join('users u', 'u.user_id = al.user_id', 'left')
                ->where('u.branch_id', $branchId)
                ->orderBy('al.created_at', 'DESC')
                ->limit(8)
                ->get()
                ->getResultArray();
        }

        // Today's tasks
        $todayTasks = [];
        if ($db->tableExists('service_calendar')) {
            $todayTasks = $db->table('service_calendar sc')
                ->select('sc.*, u.first_name, u.last_name, sl.service_name')
                ->join('plan_holders ph', 'ph.plan_holder_id = sc.plan_holder_id', 'left')
                ->join('users u', 'u.user_id = ph.user_id', 'left')
                ->join('service_list sl', 'sl.service_list_id = sc.service_id', 'left')
                ->where('sc.branch_id', $branchId)
                ->where('sc.event_date', date('Y-m-d'))
                ->orderBy('sc.event_time', 'ASC')
                ->limit(5)
                ->get()
                ->getResultArray();
        }

        return view('dashboards/staff', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => null,
            'total_clients' => $totalClients,
            'active_clients' => $activeClients,
            'total_collections' => $totalCollections,
            'pending_payments' => $pendingPayments,
            'service_requests' => $serviceRequests,
            'ongoing_services' => $ongoingServices,
            'recent_activity' => $recentActivity,
            'today_tasks' => $todayTasks,
        ]);
    }

    public function planHolder(): string
    {
        $userId = (int) session('user_id');
        $db = db_connect();

        // Check if user is a plan holder
        $holder = null;
        if ($db->tableExists('plan_holders')) {
            $holder = $db->table('plan_holders ph')
                ->select('ph.*, b.branch_name, u.first_name, u.last_name')
                ->join('users u', 'u.user_id = ph.user_id', 'left')
                ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
                ->where('ph.user_id', $userId)
                ->orderBy('ph.plan_holder_id', 'DESC')
                ->get()
                ->getRowArray();
        }

        $isPlanHolder = $holder !== null && strtolower((string) ($holder['status'] ?? '')) === 'active';
        $pendingRegistration = null;

        if (! $isPlanHolder && $holder) {
            $pendingRegistration = $holder;
        } elseif (! $holder) {
            // Check for any pending registration
            if ($db->tableExists('plan_holders')) {
                $pendingRegistration = $db->table('plan_holders')
                    ->where('user_id', $userId)
                    ->orderBy('plan_holder_id', 'DESC')
                    ->get()
                    ->getRowArray();
            }
        }

        // Membership info
        $membership = [];
        $paymentHistory = [];
        $serviceRequests = [];
        $packages = [];

        if ($isPlanHolder && $holder) {
            $planHolderId = (int) $holder['plan_holder_id'];

            // Get plan
            $plan = $db->table('plans p')
                ->select('p.*, pkg.package_name, pkg.base_price')
                ->join('packages pkg', 'pkg.package_id = p.package_id', 'left')
                ->where('p.plan_holder_id', $planHolderId)
                ->orderBy('p.plan_id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $membership = [
                'unique_identifier' => (string) ($holder['unique_identifier'] ?? '-'),
                'branch_name' => (string) ($holder['branch_name'] ?? '-'),
                'membership_status' => (string) ($holder['status'] ?? 'inactive'),
                'plan_status' => (string) ($plan['status'] ?? 'inactive'),
                'package_name' => (string) ($plan['package_name'] ?? '-'),
                'locked_price' => (float) ($plan['monthly_fee'] ?? ($plan['base_price'] ?? 0)),
                'effective_date' => (string) ($plan['start_date'] ?? '-'),
                'remaining_balance' => 0.0,
            ];

            if ($plan && $db->tableExists('payments')) {
                $paidRow = $db->table('payments')
                    ->selectSum('amount', 'total_paid')
                    ->where('plan_id', (int) $plan['plan_id'])
                    ->where('status', 'paid')
                    ->get()
                    ->getRowArray();
                $paidAmount = (float) ($paidRow['total_paid'] ?? 0);
                $totalPlan = (float) ($plan['monthly_fee'] ?? 240) * 12;
                $membership['remaining_balance'] = max(0, $totalPlan - $paidAmount);
            }

            // Payment history
            if ($plan && $db->tableExists('payments')) {
                $paymentHistory = $db->table('payments')
                    ->where('plan_id', (int) $plan['plan_id'])
                    ->orderBy('payment_date', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResultArray();
            }

            // Service requests
            if ($db->tableExists('service_applications')) {
                $serviceRequests = $db->table('service_applications')
                    ->select('sa.*, pkg.package_name')
                    ->join('plans p', 'p.plan_id = sa.plan_id', 'left')
                    ->join('packages pkg', 'pkg.package_id = p.package_id', 'left')
                    ->where('sa.plan_holder_id', $planHolderId)
                    ->orderBy('sa.created_at', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();
            }

            // Packages
            if ($db->tableExists('packages')) {
                $packages = $db->table('packages')
                    ->where('is_available', 1)
                    ->orderBy('package_name', 'ASC')
                    ->get()
                    ->getResultArray();
            }
        }

        // Registration URL
        $registrationUrl = site_url('plan-holder-registration');

        return view('dashboards/plan_holder', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => null,
            'is_plan_holder' => $isPlanHolder,
            'holder' => $holder,
            'pending_registration' => $pendingRegistration,
            'membership' => $membership,
            'payment_history' => $paymentHistory,
            'service_requests' => $serviceRequests,
            'packages' => $packages,
            'registration_url' => $registrationUrl,
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
        }

        return 'layouts/plan_holder';
    }

    private function computeKpiTrend(\CodeIgniter\Database\ConnectionInterface $db, string $table, string $dateCol, string $currentMonth, string $previousMonth, ?string $whereCol = null, ?string $whereVal = null): array
    {
        $currentStart = $currentMonth . '-01';
        $currentEnd = date('Y-m-t', strtotime($currentStart));
        $prevStart = $previousMonth . '-01';
        $prevEnd = date('Y-m-t', strtotime($prevStart));

        $currentBuilder = $db->table($table)
            ->where("{$dateCol} >=", $currentStart)
            ->where("{$dateCol} <=", $currentEnd);
        if ($whereCol !== null && $whereVal !== null) {
            $currentBuilder->where($whereCol, $whereVal);
        }
        $currentCount = (int) $currentBuilder->countAllResults();

        $prevBuilder = $db->table($table)
            ->where("{$dateCol} >=", $prevStart)
            ->where("{$dateCol} <=", $prevEnd);
        if ($whereCol !== null && $whereVal !== null) {
            $prevBuilder->where($whereCol, $whereVal);
        }
        $prevCount = (int) $prevBuilder->countAllResults();

        if ($prevCount > 0) {
            $change = round((($currentCount - $prevCount) / $prevCount) * 100);
        } elseif ($currentCount > 0) {
            $change = 100;
        } else {
            $change = 0;
        }

        return [
            'current' => $currentCount,
            'previous' => $prevCount,
            'change' => $change,
        ];
    }

    private function computePaymentTrend(\CodeIgniter\Database\ConnectionInterface $db, string $currentMonth, string $previousMonth): array
    {
        $currentStart = $currentMonth . '-01';
        $currentEnd = date('Y-m-t', strtotime($currentStart));
        $prevStart = $previousMonth . '-01';
        $prevEnd = date('Y-m-t', strtotime($prevStart));

        $currentRow = $db->table('payments')
            ->selectSum('amount', 'total')
            ->where('status', 'approved')
            ->where('payment_date >=', $currentStart)
            ->where('payment_date <=', $currentEnd)
            ->get()
            ->getRowArray();
        $currentTotal = (float) ($currentRow['total'] ?? 0);

        $prevRow = $db->table('payments')
            ->selectSum('amount', 'total')
            ->where('status', 'approved')
            ->where('payment_date >=', $prevStart)
            ->where('payment_date <=', $prevEnd)
            ->get()
            ->getRowArray();
        $prevTotal = (float) ($prevRow['total'] ?? 0);

        if ($prevTotal > 0) {
            $change = round((($currentTotal - $prevTotal) / $prevTotal) * 100);
        } elseif ($currentTotal > 0) {
            $change = 100;
        } else {
            $change = 0;
        }

        return [
            'current' => $currentTotal,
            'previous' => $prevTotal,
            'change' => $change,
        ];
    }

    private function getBranchId(): int
    {
        return (int) session()->get('branch_id');
    }

    private function getServiceRequestCounts(int $branchId): array
    {
        if ($branchId <= 0) {
            return [];
        }

        $rows = db_connect()->table('service_applications sa')
            ->select('sa.status, COUNT(sa.application_id) AS total')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->groupBy('sa.status')
            ->get()
            ->getResultArray();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
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

    private function countServiceCalendarEvents(int $branchId, array $statuses): int
    {
        if ($branchId <= 0 || empty($statuses) || ! $this->serviceCalendarExists()) {
            return 0;
        }

        return (int) db_connect()->table('service_calendar')
            ->where('branch_id', $branchId)
            ->whereIn('status', $statuses)
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

    private function getPaymentAlerts(int $branchId, array $memberStats, array $serviceRequestCounts): array
    {
        if ($branchId <= 0) {
            return [];
        }

        $db = db_connect();
        $pendingPayments = (int) $db->table('payments pay')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('pay.status', 'pending')
            ->countAllResults();

        $unassignedServices = 0;
        if ($this->serviceCalendarExists()) {
            $unassignedServices = (int) $db->table('service_calendar')
                ->where('branch_id', $branchId)
                ->where('event_date', date('Y-m-d'))
                ->where('status', 'scheduled')
                ->groupStart()
                    ->where('assigned_staff_ids', null)
                    ->orWhere('assigned_staff_ids', '[]')
                ->groupEnd()
                ->countAllResults();
        }

        $alerts = [];

        if ($pendingPayments > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Payments Awaiting Verification',
                'detail' => sprintf('%d cash / digital payments are pending branch verification.', $pendingPayments),
            ];
        }

        if (($serviceRequestCounts['pending'] ?? 0) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Service Application Reviews',
                'detail' => sprintf('%d service applications are pending review.', $serviceRequestCounts['pending']),
            ];
        }

        if ($unassignedServices > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Unassigned Service Events',
                'detail' => sprintf('%d scheduled events are missing crew assignments.', $unassignedServices),
            ];
        }

        if (($memberStats['total_delinquent'] ?? 0) > 0) {
            $alerts[] = [
                'type' => 'secondary',
                'title' => 'Delinquent Accounts',
                'detail' => sprintf('%d members are delinquent and require collection follow-up.', $memberStats['total_delinquent']),
            ];
        }

        return $alerts;
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

    private function getPaymentAnalytics(int $branchId, int $months = 6): array
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

        $result = $db->table('payments pay')
            ->select("DATE_FORMAT(pay.payment_date, '%Y-%m') AS month, SUM(pay.amount) AS total")
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('pay.status', 'approved')
            ->where('pay.payment_date >=', $startDate)
            ->groupBy('month')
            ->get()
            ->getResultArray();

        foreach ($result as $row) {
            if (array_key_exists($row['month'], $totals)) {
                $totals[$row['month']] = (float) $row['total'];
            }
        }

        $methodRows = $db->table('payments pay')
            ->select('pay.payment_method, SUM(pay.amount) AS total')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('pay.status', 'approved')
            ->where('DATE_FORMAT(pay.payment_date, "%Y-%m")', date('Y-m'))
            ->groupBy('pay.payment_method')
            ->get()
            ->getResultArray();

        $methodBreakdown = [];
        $monthlyTotal = array_sum($totals);
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
