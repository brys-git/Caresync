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
    public function index()
    {
        $userId = (int) session('user_id');
        $holder = db_connect()->table('plan_holders')->select('plan_holder_id')->where('user_id', $userId)->orderBy('plan_holder_id', 'DESC')->get(1)->getRowArray();
        $planHolderId = (int) ($holder['plan_holder_id'] ?? 0);

        $service  = new ClaimAvailService();
        $requests = $planHolderId > 0 ? $service->myRequests($planHolderId) : [];
        $plan     = $planHolderId > 0 ? $service->planFor($planHolderId) : null;

        return view('client/claims', [
            'role_layout' => 'layouts/plan_holder',
            'page_title'  => 'My claims',
            'page_sub'    => 'Your claims, packages and services, and what you still owe on them.',
            'requests'    => $requests,
            'balances'    => $plan ? $service->balances((int) $plan['plan_id']) : null,
        ]);
    }
}
