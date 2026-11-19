<?php

namespace App\Controllers;

use App\Services\ActivityLogService;
use App\Services\RemittanceService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A collector's side of remittances (Part E): pick the cash collections to hand over, see the gross, the 10% commission
 * and the net to hand over, submit, and follow each remittance until the Branch Admin confirms it.
 *
 * Reachable by role_id 5 and by is_collector-flagged Staff (the 'collector' route filter). Everything is scoped to the
 * signed-in user: there is no request parameter that names another collector.
 */
class CollectorRemittanceController extends BaseController
{
    private RemittanceService $service;

    public function __construct()
    {
        $this->service = new RemittanceService();
    }

    public function index(): ResponseInterface|string
    {
        $uid = (int) session('user_id');
        $branchId = (int) session('branch_id');

        return view('remittances/collector_remit', [
            'role_layout' => $this->layout(),
            'page_title' => 'Remit collections',
            'page_sub' => 'Hand over the cash you collected. You keep your commission once your Branch Admin confirms.',
            'available' => $this->service->available($uid, $branchId),
            'history' => $this->service->list(['collector_id' => $uid]),
            'summary' => $this->service->summary($uid, $branchId),
            'rule' => $this->service->ruleText(),
            'rate' => $this->service->rate(),
            'applies_to' => $this->service->appliesTo(),
        ]);
    }

    public function submit(): ResponseInterface
    {
        $uid = (int) session('user_id');
        $branchId = (int) session('branch_id');
        $ids = $this->request->getPost('payment_ids');
        $result = $this->service->submit($uid, $branchId, is_array($ids) ? $ids : [], (string) $this->request->getPost('note'));

        if ($result['ok']) {
            (new ActivityLogService())->log($uid, 'created', 'remittance', (int) $result['remittance_id'], $result['message']);

            return redirect()->to('/collector/remit/' . (int) $result['remittance_id'])->with('success', $result['message']);
        }

        return redirect()->to('/collector/remit')->withInput()->with('error', $result['message']);
    }

    public function show(int $id): ResponseInterface|string
    {
        $uid = (int) session('user_id');
        $r = $this->service->find($id);
        if ($r === null || (int) $r['collector_user_id'] !== $uid) {
            return redirect()->to('/collector/remit')->with('error', 'Remittance not found.');
        }

        return view('remittances/show', [
            'role_layout' => $this->layout(),
            'page_title' => 'Remittance #' . $id,
            'page_sub' => 'What you collected and what to hand over.',
            'r' => $r,
            'lines' => $this->service->lines($id),
            'mode' => 'collector',
            'back_url' => 'collector/remit',
            'rule' => $this->service->ruleText((float) $r['commission_rate'], (string) $r['commission_applies_to']),
        ]);
    }

    public function cancel(int $id): ResponseInterface
    {
        $result = $this->service->cancel($id, (int) session('user_id'));

        return redirect()->to('/collector/remit')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function layout(): string
    {
        return (int) session('role_id') === 3 ? 'layouts/staff' : 'layouts/collector';
    }
}
