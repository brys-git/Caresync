<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Services\ServiceProcessService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * "My tasks" for Staff: the tasks the Branch Admin gave them. A staff member can only see and change their OWN tasks of
 * THEIR branch (the service layer checks both on every action; there is no request parameter that names another staff member).
 */
class MyTasksController extends BaseController
{
    private ServiceProcessService $svc;

    public function __construct()
    {
        $this->svc = new ServiceProcessService();
    }

    public function index(): ResponseInterface|string
    {
        $uid = (int) session('user_id');
        $branchId = (int) session('branch_id');
        $tab = (string) $this->request->getGet('tab');
        $tab = in_array($tab, ['today', 'upcoming', 'done'], true) ? $tab : 'today';

        return view('staff/my_tasks', [
            'role_layout' => 'layouts/staff',
            'page_title' => 'My tasks',
            'page_sub' => 'What the Branch Admin asked you to do.',
            'tab' => $tab,
            'groups' => $this->svc->myTasks($uid, $branchId),
        ]);
    }

    public function start(int $id): ResponseInterface
    {
        return $this->back($this->svc->startTask($id, (int) session('user_id'), (int) session('branch_id')));
    }

    public function done(int $id): ResponseInterface
    {
        return $this->back($this->svc->doneTask($id, (int) session('user_id'), (int) session('branch_id'), (string) $this->request->getPost('note'), $this->request->getFile('document')));
    }

    public function problem(int $id): ResponseInterface
    {
        return $this->back($this->svc->reportProblem($id, (int) session('user_id'), (int) session('branch_id'), (string) $this->request->getPost('reason')));
    }

    private function back(array $r): ResponseInterface
    {
        return redirect()->to('/staff/my-tasks')->with($r['ok'] ? 'success' : 'error', $r['message']);
    }
}
