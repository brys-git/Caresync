<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\ClaimAvailService;

/**
 * "My claims": every claim, avail and service request of the signed-in plan holder, with its status,
 * rejection reason, down payment and deadline, waiver note, who filed it, and what is still owed.
 * The expiry of unpaid down payments runs when this page is read (ClaimAvailService::myRequests).
 */
class ClientClaimsController extends BaseController
{
    /** The client rates a completed service (once). */
    public function rate(int $serviceId)
    {
        $r = (new \App\Services\ServiceProcessService())->rate($serviceId, (int) session('user_id'), (int) $this->request->getPost('rating'), (string) $this->request->getPost('feedback'));

        return redirect()->to('/client/claims')->with($r['ok'] ? 'success' : 'error', $r['message']);
    }

    public function index()
    {
        $userId = (int) session('user_id');
        $holder = db_connect()->table('plan_holders')->select('plan_holder_id')->where('user_id', $userId)->orderBy('plan_holder_id', 'DESC')->get(1)->getRowArray();
        $planHolderId = (int) ($holder['plan_holder_id'] ?? 0);

        $service  = new ClaimAvailService();
        $requests = $planHolderId > 0 ? $service->myRequests($planHolderId) : [];
        $plan     = $planHolderId > 0 ? $service->planFor($planHolderId) : null;

        // Where the service is in its process (schedule once planned; the rating form once completed).
        $process = new \App\Services\ServiceProcessService();
        foreach ($requests as &$req) {
            $req['process'] = null;
            $sid = (int) ($req['service_id'] ?? 0);
            if ($sid > 0 && \App\Services\ServiceProcessService::ready()) {
                $p = $process->plan($sid);
                $req['process'] = ['service_id' => $sid, 'status' => (string) ($req['service_status'] ?? ''), 'plan' => $p, 'rated' => ! empty($p['rated_at']), 'can_rate' => in_array((string) ($req['service_status'] ?? ''), ['completed', 'closed'], true) && empty($p['rated_at'])];
            }
        }
        unset($req);

        return view('client/claims', [
            'role_layout' => 'layouts/plan_holder',
            'page_title'  => 'My claims',
            'page_sub'    => 'Your claims, packages and services, and what you still owe on them.',
            'requests'    => $requests,
            'balances'    => $plan ? $service->balances((int) $plan['plan_id']) : null,
            'plan_id'     => $plan ? (int) $plan['plan_id'] : 0,
        ]);
    }
}
