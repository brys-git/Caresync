<?php

namespace App\Controllers;

use App\Services\ActivityLogService;
use App\Services\CollectionMonthService;
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
    private CollectionMonthService $months;

    public function __construct()
    {
        $this->service = new RemittanceService();
        $this->months = new CollectionMonthService();
    }

    public function index(): ResponseInterface|string
    {
        $uid = (int) session('user_id');
        $branchId = (int) session('branch_id');
        $month = CollectionMonthService::monthKey((string) $this->request->getGet('month'));
        $prevMonth = date('Y-m', strtotime($month . '-01 -1 month'));
        $prev = $this->months->progress($uid, $branchId, $prevMonth);

        return view('remittances/collector_remit', [
            'role_layout' => $this->layout(),
            'page_title' => 'Remit collections',
            'page_sub' => 'Hand over what you collected - cash and GCash - as often as you like. You keep your commission once your Branch Admin confirms.',
            'progress' => $this->months->progress($uid, $branchId, $month),
            'prev_month' => $prevMonth,
            'prev_month_open' => $prev['closed'] === null && $prev['counts']['assigned'] > 0,
            'overdue_items' => $this->months->unremittedPastLimit($branchId, $uid),
            'max_days' => (int) config('CareSync')->remittanceMaxDays,
            'reasons' => CollectionMonthService::REASONS,
            'unclosed' => array_values(array_filter($this->months->unclosedMonths($branchId), static fn (array $u): bool => (int) $u['collector_user_id'] === $uid)),
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

    public function markNotCollected(): ResponseInterface
    {
        $uid = (int) session('user_id');
        $month = CollectionMonthService::monthKey((string) $this->request->getPost('month'));
        $result = $this->months->markNotCollected($uid, (int) session('branch_id'), (int) $this->request->getPost('plan_holder_id'), $month, (string) $this->request->getPost('reason_code'), (string) $this->request->getPost('reason_note'));

        return redirect()->to('/collector/remit?month=' . $month . '#month')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function clearNotCollected(): ResponseInterface
    {
        $month = CollectionMonthService::monthKey((string) $this->request->getPost('month'));
        $result = $this->months->clearMark((int) session('user_id'), (int) $this->request->getPost('plan_holder_id'), $month);

        return redirect()->to('/collector/remit?month=' . $month . '#month')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function closeMonth(): ResponseInterface
    {
        $uid = (int) session('user_id');
        $month = CollectionMonthService::monthKey((string) $this->request->getPost('month'));
        $result = $this->months->closeMonth($uid, (int) session('branch_id'), $month, $uid);
        if ($result['ok']) {
            (new ActivityLogService())->log($uid, 'created', 'monthly_collection_summary', (int) $result['summary_id'], $result['message']);
        }

        return redirect()->to('/collector/remit?month=' . $month . '#month')->with($result['ok'] ? 'success' : 'error', $result['message']);
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
