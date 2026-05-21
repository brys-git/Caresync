<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\PlanHolderModel;
use App\Models\PlanModel;
use App\Models\UserModel;
use App\Services\MembershipService;

/**
 * Analytics Controller
 * 
 * Provides analytics and reporting data for dashboards
 * Centralizes analytics logic for Admin, Branch Admin, and Staff roles
 */
class Analytics extends BaseController
{
    /**
     * Admin Analytics
     * System-wide analytics for super admin dashboard
     */
    public function admin(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 1) {
            return redirect()->to('/unauthorized');
        }

        $analytics = $this->getSystemWideAnalytics();

        return view('dashboards/admin', [
            'role_layout' => 'layouts/admin',
            'analytics' => $analytics,
        ]);
    }

    /**
     * Branch Admin Analytics
     * Branch-specific analytics for branch admin dashboard
     */
    public function branchAdmin(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 2) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $analytics = $this->getBranchAnalytics($branchId);

        return view('dashboards/branch_admin', [
            'role_layout' => 'layouts/branch_admin',
            'analytics' => $analytics,
        ]);
    }

    /**
     * Staff Analytics
     * Staff-specific analytics for staff dashboard
     */
    public function staff(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 3) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $analytics = $this->getStaffAnalytics($branchId);

        return view('dashboards/staff', [
            'role_layout' => 'layouts/staff',
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get system-wide analytics
     * Used by System Admin dashboard
     */
    private function getSystemWideAnalytics(): array
    {
        $db = db_connect();

        // Total members across system
        $totalMembers = $db->table('plan_holders')
            ->countAllResults();

        // Active members (with active membership state)
        $activeMembers = $db->table('plan_holders ph')
            ->join('plans p', 'p.plan_holder_id = ph.plan_holder_id', 'inner')
            ->where('p.status', 'active')
            ->where('ph.status', 'active')
            ->countAllResults();

        // Delinquent members
        $delinquentMembers = $db->table('plans')
            ->where('status', 'active')
            ->where('membership_state', 'delinquent')
            ->countAllResults();

        // Suspended members
        $suspendedMembers = $db->table('plans')
            ->where('membership_state', 'suspended')
            ->countAllResults();

        // Monthly collection data (last 12 months)
        $monthlyCollections = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months"));
            $nextDate = date('Y-m-01', strtotime("-$i months +1 month"));
            $amount = $db->table('payments')
                ->selectSum('amount', 'total')
                ->where('payment_date >=', $date)
                ->where('payment_date <', $nextDate)
                ->where('status', 'paid')
                ->get()
                ->getRowArray();
            $monthlyCollections[] = [
                'month' => date('M', strtotime($date)),
                'amount' => (float) ($amount['total'] ?? 0),
            ];
        }

        // Total collections
        $totalCollections = $db->table('payments')
            ->selectSum('amount', 'total')
            ->where('status', 'paid')
            ->get()
            ->getRowArray();

        // Branches with stats
        $branches = $db->table('branches b')
            ->select('b.branch_id, b.branch_name, COUNT(DISTINCT ph.plan_holder_id) as member_count')
            ->join('plan_holders ph', 'ph.branch_id = b.branch_id', 'left')
            ->groupBy('b.branch_id')
            ->orderBy('member_count', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // Pending approvals
        $pendingApprovals = $db->table('payments pay')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('pay.status', 'pending')
            ->where('pay.payment_method', 'gcash')
            ->where('ph.status', 'inactive')
            ->countAllResults();

        // Service requests pending approval
        $pendingServiceRequests = $db->table('service_applications')
            ->where('status', 'pending')
            ->countAllResults();

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'delinquent_members' => $delinquentMembers,
            'suspended_members' => $suspendedMembers,
            'total_collections' => (float) ($totalCollections['total'] ?? 0),
            'monthly_collections' => $monthlyCollections,
            'top_branches' => $branches,
            'pending_approvals' => $pendingApprovals,
            'pending_service_requests' => $pendingServiceRequests,
        ];
    }

    /**
     * Get branch-specific analytics
     * Used by Branch Admin dashboard
     */
    private function getBranchAnalytics(int $branchId): array
    {
        $db = db_connect();

        // Branch members
        $branchMembers = $db->table('plan_holders')
            ->where('branch_id', $branchId)
            ->countAllResults();

        // Active members in branch
        $activeBranchMembers = $db->table('plan_holders ph')
            ->join('plans p', 'p.plan_holder_id = ph.plan_holder_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('p.status', 'active')
            ->where('ph.status', 'active')
            ->countAllResults();

        // Overdue accounts in branch
        $overdueAccounts = $db->table('plans')
            ->where('membership_state', 'delinquent')
            ->where('status', 'active')
            ->countAllResults();

        // Daily collections for the month
        $currentMonth = date('Y-m');
        $dailyCollections = $db->table('payments')
            ->select('DATE(payment_date) as date, COUNT(*) as count, SUM(amount) as total')
            ->where('branch_id', $branchId)
            ->where('status', 'paid')
            ->where("DATE_FORMAT(payment_date, '%Y-%m') =", $currentMonth)
            ->groupBy('DATE(payment_date)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Monthly branch collection
        $monthlyBranchTotal = $db->table('payments')
            ->selectSum('amount', 'total')
            ->where('branch_id', $branchId)
            ->where('status', 'paid')
            ->where("DATE_FORMAT(payment_date, '%Y-%m') =", $currentMonth)
            ->get()
            ->getRowArray();

        // Pending initial payments in branch
        $pendingInitialPayments = $db->table('payments pay')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('pay.status', 'pending')
            ->where('pay.branch_id', $branchId)
            ->where('ph.status', 'inactive')
            ->countAllResults();

        // Pending service applications in branch
        $pendingServiceApps = $db->table('service_applications sa')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->where('sa.status', 'pending')
            ->where('ph.branch_id', $branchId)
            ->countAllResults();

        // Ongoing services
        $ongoingServices = $db->table('service_schedules')
            ->where('branch_id', $branchId)
            ->where('status', 'in-progress')
            ->countAllResults();

        // Staff count in branch
        $staffCount = $db->table('users')
            ->where('branch_id', $branchId)
            ->where('role_id', 3)
            ->where('account_status', 'active')
            ->countAllResults();

        return [
            'branch_members' => $branchMembers,
            'active_members' => $activeBranchMembers,
            'overdue_accounts' => $overdueAccounts,
            'daily_collections' => $dailyCollections,
            'monthly_total' => (float) ($monthlyBranchTotal['total'] ?? 0),
            'pending_initial_payments' => $pendingInitialPayments,
            'pending_service_requests' => $pendingServiceApps,
            'ongoing_services' => $ongoingServices,
            'staff_count' => $staffCount,
        ];
    }

    /**
     * Get staff-specific analytics
     * Used by Staff dashboard
     */
    private function getStaffAnalytics(int $branchId): array
    {
        $db = db_connect();

        // Staff member stats
        $staffMembers = $db->table('plan_holders')
            ->where('branch_id', $branchId)
            ->countAllResults();

        // Service applications assigned to staff
        $assignedServices = $db->table('service_applications')
            ->where('status', 'approved')
            ->countAllResults();

        // Pending service requests to process
        $pendingRequests = $db->table('service_applications')
            ->where('status', 'pending')
            ->countAllResults();

        // Cash payments recorded by staff (today)
        $todayPayments = $db->table('payments')
            ->where('payment_method', 'cash')
            ->where('DATE(payment_date)', date('Y-m-d'))
            ->where('branch_id', $branchId)
            ->countAllResults();

        // Total cash collected today
        $todayTotal = $db->table('payments')
            ->selectSum('amount', 'total')
            ->where('payment_method', 'cash')
            ->where('DATE(payment_date)', date('Y-m-d'))
            ->where('branch_id', $branchId)
            ->where('status', 'pending')
            ->get()
            ->getRowArray();

        return [
            'staff_members' => $staffMembers,
            'assigned_services' => $assignedServices,
            'pending_requests' => $pendingRequests,
            'cash_payments_today' => $todayPayments,
            'cash_collected_today' => (float) ($todayTotal['total'] ?? 0),
        ];
    }

    /**
     * Get analytics data as JSON (for AJAX requests)
     */
    public function getJson(string $type = 'system'): string
    {
        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');

        $analytics = [];

        if ($type === 'system' && $roleId === 1) {
            $analytics = $this->getSystemWideAnalytics();
        } elseif ($type === 'branch' && in_array($roleId, [2, 3], true)) {
            $analytics = $this->getBranchAnalytics($branchId);
        } elseif ($type === 'staff' && $roleId === 3) {
            $analytics = $this->getStaffAnalytics($branchId);
        } else {
            return $this->response->setJSON(['error' => 'Unauthorized'], 403);
        }

        return $this->response->setJSON($analytics);
    }
}
