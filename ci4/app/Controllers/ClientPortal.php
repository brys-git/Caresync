<?php

namespace App\Controllers;

/**
 * ClientPortal
 *
 * Legacy client-portal controller. Only the registration-approvals queue is
 * still routed here; the plan-holder dashboard moved to
 * Client\ClientDashboardController and all other client pages live in the
 * refactored App\Controllers\Client\* controllers.
 */
class ClientPortal extends BaseController
{
    public function registrationApprovals()
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');

        $builder = db_connect()->table('payments pay')
            ->select('pay.payment_id, pay.plan_id, pay.amount, pay.payment_date, pay.payment_method, pay.reference_number, pay.status, pay.branch_id, p.plan_holder_id, ph.status AS holder_status, u.user_id, u.first_name, u.last_name, u.email, b.branch_name')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = pay.branch_id', 'left')
            ->where('pay.status', 'pending')
            ->where('pay.payment_method', 'gcash')
            ->where('ph.status', 'inactive')
            ->orderBy('pay.payment_id', 'DESC');

        if ($roleId === 2) {
            $builder->where('pay.branch_id', $branchId);
        }

        $rows = $builder->get()->getResultArray();

        return view('approvals/registration_queue', [
            'role_layout' => $roleId === 1 ? 'layouts/admin' : 'layouts/branch_admin',
            'rows' => $rows,
            'can_verify' => $roleId === 2,
        ]);
    }
}
