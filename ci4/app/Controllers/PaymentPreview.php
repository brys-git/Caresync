<?php

namespace App\Controllers;

use App\Services\ClaimAvailService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * "Where will this payment go?" - the preview shown on the recording screens (staff, branch
 * admin, collector, client GCash) for a plan that has a package instalment:
 *   "₱3,156.67 -> Plan Oct 2026 ₱240.00 + Instalment 1 ₱2,916.67".
 * Read only. The server recomputes the same split when the payment is saved; this is only a
 * convenience. Each caller may only look at plans they could record a payment for.
 */
class PaymentPreview extends BaseController
{
    public function allocation(): ResponseInterface
    {
        $planId = (int) $this->request->getGet('plan_id');
        $plan   = $planId > 0 ? db_connect()->table('plans p')->select('p.plan_id, ph.user_id, ph.branch_id')->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')->where('p.plan_id', $planId)->get(1)->getRowArray() : null;

        if ($plan === null || ! $this->mayUse($plan)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Plan not found.']);
        }

        $service = new ClaimAvailService();
        $entry   = $service->recordingEntry($planId);
        if (! $entry['amount_mode']) {
            return $this->response->setJSON(['ok' => true, 'amount_mode' => false, 'text' => '']);
        }

        $amount = (float) $this->request->getGet('amount');
        $date   = (string) $this->request->getGet('date');
        $date   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
        $kind   = (string) $this->request->getGet('kind') === 'down_payment' ? 'down_payment' : 'regular';

        $res = $service->prepareAmountPayment($planId, $amount, $date, $kind);

        return $this->response->setJSON([
            'ok'          => $res['ok'],
            'amount_mode' => true,
            'text'        => $res['text'] ?? '',
            'error'       => $res['error'] ?? null,
            'down_left'   => $entry['down_left'],
            'has_down'    => $entry['down_avail'] !== null,
        ]);
    }

    /** The client themself, or staff of the plan holder's own branch (System Admin: any). */
    private function mayUse(array $plan): bool
    {
        $role = (int) session('role_id');

        return match (true) {
            $role === 1 => true,
            $role === 4 => (int) $plan['user_id'] === (int) session('user_id'),
            in_array($role, [2, 3, 5], true) => (int) $plan['branch_id'] === (int) session('branch_id'),
            default => false,
        };
    }
}
