<?php

namespace App\Controllers;

use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\NotificationService;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->has('user_id')) {
            return redirect()->to($this->defaultDashboardForRole((int) session('role_id')));
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $identifier = trim((string) $this->request->getPost('identifier'));
        $password   = (string) $this->request->getPost('password');

        if ($identifier === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Username/email and password are required.');
        }

        $userModel = new UserModel();
        $user      = $userModel
            ->groupStart()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        if (! $user || ! password_verify($password, (string) $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return redirect()->back()->withInput()->with('error', 'Your account is inactive.');
        }

        session()->set([
            'user_id'   => (int) $user['user_id'],
            'role_id'   => (int) $user['role_id'],
            'branch_id' => $user['branch_id'] === null ? null : (int) $user['branch_id'],
            'is_plan_holder' => (int) ($user['is_plan_holder'] ?? 0),
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
        ]);

        $userModel->update((int) $user['user_id'], ['last_login' => date('Y-m-d H:i:s')]);

        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            return redirect()->to('/change-password')->with('success', 'Please change your temporary password.');
        }

        return redirect()->to($this->defaultDashboardForRole((int) $user['role_id']));
    }

    public function register()
    {
        return view('auth/register');
    }

    public function attemptRegister()
    {
        $rules = [
            'username' => 'required|min_length[4]|max_length[50]|is_unique[users.username]',
            'email' => 'required|valid_email|max_length[100]|is_unique[users.email]',
            'first_name' => 'required|max_length[50]',
            'last_name' => 'required|max_length[50]',
            'unique_identifier' => 'permit_empty|max_length[100]',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userModel = new UserModel();
        $planHolderModel = new PlanHolderModel();
        $db = db_connect();
        $db->transBegin();

        try {
            $firstName = trim((string) $this->request->getPost('first_name'));
            $lastName = trim((string) $this->request->getPost('last_name'));
            $uniqueIdentifier = trim((string) $this->request->getPost('unique_identifier'));

            $saved = $userModel->insert([
                'username' => trim((string) $this->request->getPost('username')),
                'email' => trim((string) $this->request->getPost('email')),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'contact_number' => trim((string) $this->request->getPost('contact_number')),
                'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'role_id' => 4,
                'branch_id' => null,
                'status' => 'active',
                'account_status' => 'pending',
                'is_plan_holder' => 0,
                'must_change_password' => 0,
            ]);

            if (! $saved) {
                throw new \RuntimeException('Unable to register right now.');
            }

            $newUserId = (int) $userModel->getInsertID();

            $existingPlanHolderQuery = $db->table('plan_holders ph')
                ->select('ph.plan_holder_id, ph.user_id, ph.unique_identifier')
                ->join('users u', 'u.user_id = ph.user_id', 'left')
                ->where('ph.user_id !=', $newUserId);

            if ($uniqueIdentifier !== '') {
                $existingPlanHolderQuery->groupStart()
                    ->where('ph.unique_identifier', $uniqueIdentifier)
                    ->orGroupStart()
                    ->where('u.first_name', $firstName)
                    ->where('u.last_name', $lastName)
                    ->groupEnd()
                    ->groupEnd();
            } else {
                $existingPlanHolderQuery->where('u.first_name', $firstName)
                    ->where('u.last_name', $lastName);
            }

            $existingPlanHolder = $existingPlanHolderQuery
                ->orderBy('ph.plan_holder_id', 'DESC')
                ->get()
                ->getRowArray();

            $linkedExisting = false;
            if ($existingPlanHolder) {
                $planHolderModel->update((int) $existingPlanHolder['plan_holder_id'], [
                    'user_id' => $newUserId,
                    'is_linked_account' => 1,
                ]);

                $linkedProfile = $planHolderModel->find((int) $existingPlanHolder['plan_holder_id']);
                $userModel->update($newUserId, [
                    'is_plan_holder' => 1,
                    'branch_id' => $linkedProfile['branch_id'] ?? null,
                    'account_status' => 'verified',
                ]);

                $linkedExisting = true;
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Unable to complete account registration.');
            }

            $db->transCommit();

            $notificationService = new NotificationService();
            $activityLogService = new ActivityLogService();

            if ($linkedExisting) {
                $notificationService->notify($newUserId, 'Your account has been successfully linked to your membership.', 'registration_pending');
            } else {
                $notificationService->notify($newUserId, 'Your account was created. Complete plan holder registration to unlock services and payments.', 'registration_pending');
            }

            $activityLogService->log(
                $newUserId,
                'created',
                'user',
                $newUserId,
                'Registered new client account',
                null,
                ['linked_existing_plan_holder' => $linkedExisting]
            );

            return redirect()->to('/login')->with('success', 'Account created successfully. Sign in to continue.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function changePassword()
    {
        if (! session()->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Please sign in first.');
        }

        return view('auth/change_password');
    }

    public function updatePassword()
    {
        if (! session()->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Please sign in first.');
        }

        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[8]',
            'new_password_confirm' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userModel = new UserModel();
        $userId    = (int) session('user_id');
        $user      = $userModel->find($userId);

        if (! $user || ! password_verify((string) $this->request->getPost('current_password'), (string) $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Current password is incorrect.');
        }

        $userModel->update($userId, [
            'password_hash' => password_hash((string) $this->request->getPost('new_password'), PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        session()->set('must_change_password', 0);

        (new NotificationService())->notify($userId, 'Your password was changed successfully.', 'general');
        (new ActivityLogService())->log($userId, 'updated', 'user', $userId, 'Changed account password');

        return redirect()->to($this->defaultDashboardForRole((int) session('role_id')))->with('success', 'Password updated successfully.');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'You have been signed out.');
    }

    private function defaultDashboardForRole(int $roleId): string
    {
        return match ($roleId) {
            1 => '/dashboard/admin',
            2 => '/dashboard/branch-admin',
            3 => '/dashboard/staff',
            4 => '/client/dashboard',
            default => '/login',
        };
    }
}
