<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\PlanClosureService;

/**
 * "Request to close my plan": the client asks their branch to close the plan (the Branch Admin decides), and may cancel
 * the request while it is waiting. Every rule is in PlanClosureService.
 */
class ClientPlanClosureController extends BaseController
{
    public function request()
    {
        $userId = (int) session('user_id');
        $holder = db_connect()->table('plan_holders')->select('plan_holder_id')->where('user_id', $userId)->orderBy('plan_holder_id', 'DESC')->get(1)->getRowArray();
        if (! $holder) {
            return redirect()->to('/client/dashboard')->with('error', 'No plan found.');
        }
        $r = (new PlanClosureService())->requestClosure((int) $holder['plan_holder_id'], $userId, (string) $this->request->getPost('reason'));

        return redirect()->to('/client/dashboard')->with($r['ok'] ? 'success' : 'error', $r['message']);
    }

    public function cancel(int $requestId)
    {
        $r = (new PlanClosureService())->cancelRequest($requestId, (int) session('user_id'));

        return redirect()->to('/client/dashboard')->with($r['ok'] ? 'success' : 'error', $r['message']);
    }
}
