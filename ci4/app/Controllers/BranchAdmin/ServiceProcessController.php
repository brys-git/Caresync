<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ServiceProcessService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The Branch Admin's side of the service process: the board, the calendar, and the service plan page (schedule, family
 * contact, task checklist, family confirmation). Everything is scoped to the signed-in Branch Admin's own branch - a service
 * of another branch is "not found".
 */
class ServiceProcessController extends BaseController
{
    private ServiceProcessService $svc;

    public function __construct()
    {
        $this->svc = new ServiceProcessService();
    }

    private function branch(): int
    {
        return (int) session('branch_id');
    }

    public function board(): ResponseInterface|string
    {
        $branchId = $this->branch();
        (new \App\Services\ClaimAvailService())->expireDownPayments(null);

        return view('branch_admin/service_process/board', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Service board',
            'page_sub' => 'Every service of this branch in its stage. Open one to plan it and follow its tasks.',
            'columns' => $this->svc->board($branchId),
            'attention' => $this->svc->attention($branchId),
        ]);
    }

    public function calendar(): ResponseInterface|string
    {
        $ym = (string) $this->request->getGet('month');
        $ym = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym) ? $ym : date('Y-m');

        return view('branch_admin/service_process/calendar', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Wakes and burials',
            'page_sub' => 'Pick-ups, wakes and burials of the month.',
            'ym' => $ym,
            'events' => $this->svc->calendar($this->branch(), $ym),
        ]);
    }

    public function plan(int $id): ResponseInterface|string
    {
        $svc = $this->svc->service($id, $this->branch());
        if ($svc === null) {
            return redirect()->to('/branch-admin/service-package/ongoing')->with('error', 'Service not found.');
        }
        // A service approved before the process existed gets its plan row and task list the first time it is opened.
        if (ServiceProcessService::ready() && $this->svc->plan($id) === []) {
            $this->svc->onApproved($id, (int) session('user_id'));
        }
        $this->svc->syncStage($id);
        $svc = $this->svc->service($id, $this->branch());
        $plan = $this->svc->plan($id);
        $tasks = $this->svc->tasks($id);
        $claim = new \App\Services\ClaimAvailService();

        return view('branch_admin/service_process/plan', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Service plan',
            'page_sub' => $svc['what'] . ' - ' . $svc['client_name'],
            's' => $svc,
            'plan' => $plan,
            'tasks' => $tasks,
            'staff' => $this->svc->branchStaff($this->branch()),
            'ready' => $this->svc->readyToComplete($svc),
            'plan_complete' => $this->svc->planComplete($plan),
            'cleared' => $claim->downPaymentCleared($id),
            'gate_message' => $claim->checkServiceStatusChange($id, 'ongoing')['message'],
            'maps_url' => ServiceProcessService::mapsUrl($plan['wake_address'] ?? null),
            'is_open' => in_array($svc['status'], ServiceProcessService::OPEN, true),
        ]);
    }

    public function savePlan(int $id): ResponseInterface
    {
        $r = $this->svc->savePlan($id, $this->branch(), (int) session('user_id'), $this->request->getPost());

        return $this->back($id, $r);
    }

    public function addTask(int $id): ResponseInterface
    {
        return $this->back($id, $this->svc->addTask($id, $this->branch(), (int) session('user_id'), $this->request->getPost()));
    }

    public function updateTask(int $taskId): ResponseInterface
    {
        return $this->backForTask($taskId, $this->svc->updateTask($taskId, $this->branch(), $this->request->getPost()));
    }

    public function removeTask(int $taskId): ResponseInterface
    {
        $serviceId = (int) ($this->svc->task($taskId)['service_id'] ?? 0);

        return $this->back($serviceId, $this->svc->removeTask($taskId, $this->branch()));
    }

    public function moveTask(int $taskId): ResponseInterface
    {
        return $this->backForTask($taskId, $this->svc->moveTask($taskId, $this->branch(), (string) $this->request->getPost('dir') === 'up' ? -1 : 1));
    }

    public function resolveTask(int $taskId): ResponseInterface
    {
        return $this->backForTask($taskId, $this->svc->resolveProblem($taskId, $this->branch()));
    }

    public function complete(int $id): ResponseInterface
    {
        $r = $this->svc->complete($id, $this->branch(), (int) session('user_id'), $this->request->getPost(), $this->request->getFile('confirmed_photo'));

        return $this->back($id, $r);
    }

    public function close(int $id): ResponseInterface
    {
        return $this->back($id, $this->svc->close($id, $this->branch(), (int) session('user_id')));
    }

    public function cancel(int $id): ResponseInterface
    {
        return $this->back($id, $this->svc->cancel($id, $this->branch(), (int) session('user_id')));
    }

    private function backForTask(int $taskId, array $r): ResponseInterface
    {
        return $this->back((int) ($this->svc->task($taskId)['service_id'] ?? 0), $r);
    }

    private function back(int $serviceId, array $r): ResponseInterface
    {
        $to = $serviceId > 0 ? '/branch-admin/service-package/services/' . $serviceId . '/plan' : '/branch-admin/service-package/ongoing';

        return redirect()->to($to)->with($r['ok'] ? 'success' : 'error', $r['message'] !== '' ? $r['message'] : 'Done.');
    }
}
