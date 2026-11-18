<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AccountRegistrationService;
use App\Services\ActivityLogService;
use App\Services\GovernmentIdVerificationService;
use App\Services\LoginDetailsService;
use App\Services\NotificationService;

/**
 * System Admin > Users: the one list of every account, with activate /
 * deactivate and password reset. (Add user and View / Edit arrive in later steps.)
 *
 * Route filter role:1 protects every route; each action re-checks it here.
 */
class UsersController extends BaseController
{
    private const PER_PAGE = 25;

    private const ROLE_ADMIN = 1;

    public function index()
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $db = db_connect();

        $search = trim((string) $this->request->getGet('search'));
        $role   = (int) $this->request->getGet('role');
        $branch = (int) $this->request->getGet('branch_id');
        $status = (string) $this->request->getGet('status');
        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }
        $page = max(1, (int) $this->request->getGet('page'));

        $apply = function ($builder) use ($db, $search, $role, $branch, $status) {
            $builder->where('u.deleted_at', null);

            if ($search !== '') {
                $builder->groupStart()
                    ->like('u.first_name', $search)
                    ->orLike('u.last_name', $search)
                    ->orWhere("CONCAT(u.first_name, ' ', u.last_name) LIKE '%" . $db->escapeLikeString($search) . "%' ESCAPE '!'", null, false)
                    ->orLike('u.username', $search)
                    ->orLike('u.email', $search)
                    ->groupEnd();
            }
            if ($role > 0) {
                $builder->where('u.role_id', $role);
            }
            if ($branch > 0) {
                $builder->where('u.branch_id', $branch);
            }
            if ($status !== '') {
                $builder->where('u.status', $status);
            }

            return $builder;
        };

        $total = (int) $apply($db->table('users u'))->countAllResults();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $users = $apply(
            $db->table('users u')
                ->select('u.user_id, u.username, u.email, u.first_name, u.last_name, u.role_id, u.branch_id, u.status, u.account_status, u.is_collector, u.last_login, u.created_at, r.role_name, b.branch_name')
                ->join('roles r', 'r.role_id = u.role_id', 'left')
                ->join('branches b', 'b.branch_id = u.branch_id', 'left')
        )
            ->orderBy('u.created_at', 'DESC')
            ->orderBy('u.user_id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()
            ->getResultArray();

        return view('admin/users/index', [
            'role_layout' => 'layouts/admin',
            'active_nav'  => 'users',
            'page_title'  => 'Users',
            'page_sub'    => 'Every account in the system.',
            'breadcrumb'  => ['Users'],
            'users'       => $users,
            'total'       => $total,
            'page'        => $page,
            'pages'       => $pages,
            'per_page'    => self::PER_PAGE,
            'roles'       => $db->table('roles')->select('role_id, role_name')->orderBy('role_id', 'ASC')->get()->getResultArray(),
            'branches'    => $db->table('branches')->select('branch_id, branch_name')->orderBy('branch_name', 'ASC')->get()->getResultArray(),
            'filters'     => ['search' => $search, 'role' => $role, 'branch_id' => $branch, 'status' => $status],
            'self_id'     => (int) session('user_id'),
        ]);
    }

    /** GET: the one "Add user" form. */
    public function create()
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        // After a server-side error the form comes back with what was typed. A
        // successful ID check is kept - but only while its token still exists and
        // the typed name still matches it; otherwise the ID is asked for again.
        $keepToken  = '';
        $keepStatus = null;
        $keepIdType = '';
        $oldToken   = trim((string) old('government_id_pending_token'));
        if ($oldToken !== '') {
            $idService = new GovernmentIdVerificationService();
            $pending   = $idService->peekPending($oldToken);
            $identity  = [
                'first_name'  => trim((string) old('first_name')),
                'middle_name' => trim((string) old('middle_name')),
                'last_name'   => trim((string) old('last_name')),
            ];
            if ($pending !== null && $idService->isValidForSubmission($pending, $identity)) {
                $keepToken  = $oldToken;
                $keepStatus = (string) $pending['verification_status'];
                $keepIdType = (string) ($pending['id_type'] ?? '');
            }
        }

        return view('admin/users/create', [
            'keep_token'   => $keepToken,
            'keep_status'  => $keepStatus,
            'keep_id_type' => $keepIdType,
            'role_layout'  => 'layouts/admin',
            'active_nav'   => 'users',
            'page_title'   => 'Add user',
            'page_sub'     => 'Complete each step, then review before creating the account.',
            'breadcrumb'   => ['Users', 'Add user'],
            'roles'        => AccountRegistrationService::rolesCreatableBy(AccountRegistrationService::ROLE_ADMIN),
            'id_required'  => AccountRegistrationService::ID_REQUIRED_ROLES,
            'branch_roles' => AccountRegistrationService::BRANCH_ROLES,
            'branches'     => db_connect()->table('branches')->select('branch_id, branch_name')->where('status', 'active')->orderBy('branch_name', 'ASC')->get()->getResultArray(),
            'id_types'     => (new GovernmentIdVerificationService())->idTypes(),
        ]);
    }

    /** POST: create the account. Every rule is enforced here and in AccountRegistrationService, never only in the browser. */
    public function store()
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $service   = new AccountRegistrationService();
        $roles     = AccountRegistrationService::rolesCreatableBy(AccountRegistrationService::ROLE_ADMIN);
        $manual    = (string) $this->request->getPost('password_mode') === 'manual';
        $temporary = ! $manual;

        $rules = $service->rules($temporary) + [
            'role_id' => 'required|in_list[' . implode(',', array_keys($roles)) . ']',
        ];

        if (! $this->validate($rules, ['role_id' => ['in_list' => 'Please choose a role.']])) {
            return $this->backWithInput(implode(' ', $this->validator->getErrors()));
        }

        $roleId   = (int) $this->request->getPost('role_id');
        $isClient = $roleId === AccountRegistrationService::ROLE_CLIENT;

        // Branch: required for Branch Admin / Staff / Collector, optional for a Client,
        // never for another System Admin.
        $branchId = (int) $this->request->getPost('branch_id');
        if (in_array($roleId, AccountRegistrationService::BRANCH_ROLES, true) && $branchId <= 0) {
            return $this->backWithInput('A branch is required for ' . $roles[$roleId] . ' accounts.');
        }
        if ($roleId === AccountRegistrationService::ROLE_ADMIN) {
            $branchId = 0;
        }
        if ($branchId > 0 && db_connect()->table('branches')->where('branch_id', $branchId)->where('status', 'active')->countAllResults() !== 1) {
            return $this->backWithInput('Please select an active branch.');
        }

        // Another System Admin: the admin must prove it is really them.
        $adminConfirmed = false;
        if ($roleId === AccountRegistrationService::ROLE_ADMIN) {
            $me = db_connect()->table('users')->select('password_hash')->where('user_id', (int) session('user_id'))->get()->getRowArray();
            if (! $me || ! password_verify((string) $this->request->getPost('admin_password'), (string) $me['password_hash'])) {
                return $this->backWithInput('Your own password is incorrect. Enter your password to create another System Admin.');
            }
            $adminConfirmed = true;
        }

        // Government ID: required for staff-side roles, optional for a Client.
        $idService  = new GovernmentIdVerificationService();
        $token      = trim((string) $this->request->getPost('government_id_pending_token'));
        $idRequired = in_array($roleId, AccountRegistrationService::ID_REQUIRED_ROLES, true);
        $pending    = null;

        if ($token === '' && $idRequired) {
            return $this->backWithInput('Please complete the Government ID verification step for ' . $roles[$roleId] . ' accounts.');
        }
        if ($token !== '') {
            $pending = $idService->peekPending($token);
            if ($pending === null) {
                return $this->backWithInput('The Government ID verification could not be found. Please verify the ID again.');
            }
            $identity = [
                'first_name'  => trim((string) $this->request->getPost('first_name')),
                'middle_name' => trim((string) $this->request->getPost('middle_name')),
                'last_name'   => trim((string) $this->request->getPost('last_name')),
            ];
            if (! $idService->isValidForSubmission($pending, $identity)) {
                return $this->backWithInput(
                    (string) ($pending['verification_status'] ?? '') === 'failed'
                        ? 'The Government ID does not match the details entered. Please correct the details or upload the correct ID.'
                        : 'The name was changed after verifying the Government ID. Please verify the ID again.'
                );
            }
        }

        $wantsCollector = (string) $this->request->getPost('is_collector') === '1';

        try {
            $result = $service->createByAdmin([
                'username'          => $this->request->getPost('username'),
                'email'             => $this->request->getPost('email'),
                'first_name'        => $this->request->getPost('first_name'),
                'middle_name'       => $this->request->getPost('middle_name'),
                'last_name'         => $this->request->getPost('last_name'),
                'contact_number'    => $this->request->getPost('contact_number'),
                // Only a Client can be linked to an existing membership.
                'unique_identifier' => $isClient ? $this->request->getPost('unique_identifier') : '',
                'password'          => $this->request->getPost('password'),
            ], $roleId, $branchId > 0 ? $branchId : null, $roles, [
                'clientGetsBranch'       => true,
                'temporaryPassword'      => $temporary,
                'mustChangePassword'     => $manual && (string) $this->request->getPost('must_change_password') === '1',
                'isCollector'            => $wantsCollector,
                'adminPasswordConfirmed' => $adminConfirmed,
            ]);
        } catch (\Throwable $e) {
            return $this->backWithInput($e->getMessage());
        }

        $newId = (int) $result['user_id'];
        if ($pending !== null) {
            $idService->commitPending($token, $newId);
        }

        $first       = trim((string) $this->request->getPost('first_name'));
        $name        = trim($first . ' ' . (string) $this->request->getPost('last_name'));
        $roleLabel   = preg_replace('/ \(.*\)$/', '', $roles[$roleId]);
        $isCollector = $roleId === AccountRegistrationService::ROLE_STAFF && $wantsCollector;

        (new NotificationService())->notify(
            $newId,
            'Your CareSync account was created. ' . ($temporary
                ? 'Sign in with the temporary password you received and choose a new one.'
                : 'Sign in with the password your administrator gave you.'),
            'general'
        );

        (new ActivityLogService())->log(
            (int) session('user_id'),
            'created',
            'user',
            $newId,
            "Created {$roleLabel} account for {$name}" . ($isCollector ? ' (can collect payments)' : '') . ($temporary ? ' with a temporary password' : ' with a set password'),
            null,
            ['role_id' => $roleId, 'branch_id' => $branchId > 0 ? $branchId : null, 'is_collector' => $isCollector, 'temporary_password' => $temporary, 'id_verified' => $pending !== null]
        );

        $success = "{$roleLabel} account for {$name} has been created."
            . (! empty($result['linked_existing']) ? ' It was linked to the existing membership.' : '');

        if ($manual) {
            return redirect()->to(site_url('admin/users'))->with('success', $success);
        }

        // Temporary password: shown once, and emailed when email works. If the
        // email fails the account is kept and the screen says so.
        $branchRow = $branchId > 0 ? db_connect()->table('branches')->select('branch_name')->where('branch_id', $branchId)->get()->getRowArray() : null;
        $login     = new LoginDetailsService();
        $details   = $login->details([
            'user_id'     => $newId,
            'username'    => trim((string) $this->request->getPost('username')),
            'email'       => trim((string) $this->request->getPost('email')),
            'first_name'  => $first,
            'last_name'   => trim((string) $this->request->getPost('last_name')),
            'role_name'   => $roleLabel,
            'branch_name' => (string) ($branchRow['branch_name'] ?? ''),
        ], $result['temp_password'], $result['temp_expires_at']);
        $details['emailed']    = $login->email($details);
        $details['back_url']   = site_url('admin/users');
        $details['back_label'] = 'Back to users';

        return redirect()->to(site_url('account-login-details'))
            ->with('success', $success)
            ->with('login_details', $details);
    }

    /** GET: one account's details (never any password data). */
    public function show(int $userId)
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $user = $this->loadFull($userId);
        if ($user === null) {
            return $this->backToList('error', 'That account was not found.');
        }

        $db = db_connect();

        return view('admin/users/show', [
            'role_layout' => 'layouts/admin',
            'active_nav'  => 'users',
            'page_title'  => trim($user['first_name'] . ' ' . $user['last_name']),
            'page_sub'    => 'Account details',
            'breadcrumb'  => ['Users', 'Details'],
            'u'           => $user,
            'is_self'     => $userId === (int) session('user_id'),
            'manages'     => $this->managedBranch($userId),
            'has_plan_holder' => $this->hasPlanHolder($userId),
            'id_check'    => $db->table('government_id_verifications')
                ->select('id_type, verification_status, verified_at, created_at')
                ->where('user_id', $userId)->orderBy('verification_id', 'DESC')->limit(1)->get()->getRowArray(),
            'history'     => $db->table('activity_logs al')
                ->select('al.action, al.description, al.created_at, u.first_name, u.last_name')
                ->join('users u', 'u.user_id = al.user_id', 'left')
                ->where('al.module', 'user')->where('al.target_id', $userId)
                ->orderBy('al.created_at', 'DESC')->orderBy('al.log_id', 'DESC')->limit(8)->get()->getResultArray(),
        ]);
    }

    /** GET: edit form. */
    public function edit(int $userId)
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $user = $this->loadFull($userId);
        if ($user === null) {
            return $this->backToList('error', 'That account was not found.');
        }

        return view('admin/users/edit', [
            'role_layout'  => 'layouts/admin',
            'active_nav'   => 'users',
            'page_title'   => 'Edit ' . trim($user['first_name'] . ' ' . $user['last_name']),
            'page_sub'     => 'Change account details, role, branch or status.',
            'breadcrumb'   => ['Users', 'Edit'],
            'u'            => $user,
            'is_self'      => $userId === (int) session('user_id'),
            'roles'        => AccountRegistrationService::rolesCreatableBy(AccountRegistrationService::ROLE_ADMIN),
            'branch_roles' => AccountRegistrationService::BRANCH_ROLES,
            'branches'     => db_connect()->table('branches')->select('branch_id, branch_name, status')->orderBy('branch_name', 'ASC')->get()->getResultArray(),
            'manages'      => $this->managedBranch($userId),
            'has_plan_holder' => $this->hasPlanHolder($userId),
        ]);
    }

    /** POST: save the edit. All rules are checked here, not only in the form. */
    public function update(int $userId)
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $user = $this->loadFull($userId);
        if ($user === null) {
            return $this->backToList('error', 'That account was not found.');
        }

        $db    = db_connect();
        $roles = AccountRegistrationService::rolesCreatableBy(AccountRegistrationService::ROLE_ADMIN);
        $self  = $userId === (int) session('user_id');

        $rules = [
            'first_name'     => 'required|max_length[50]',
            'middle_name'    => 'permit_empty|max_length[50]',
            'last_name'      => 'required|max_length[50]',
            'contact_number' => 'permit_empty|max_length[20]',
            'email'          => 'required|valid_email|max_length[100]|is_unique[users.email,user_id,' . $userId . ']',
            'role_id'        => 'required|in_list[' . implode(',', array_keys($roles)) . ']',
            'status'         => 'required|in_list[active,inactive]',
        ];
        if (! $this->validate($rules, ['role_id' => ['in_list' => 'Please choose a role.']])) {
            return $this->failEdit($userId, implode(' ', $this->validator->getErrors()));
        }

        $oldRole  = (int) $user['role_id'];
        $newRole  = (int) $this->request->getPost('role_id');
        $newState = (string) $this->request->getPost('status');
        $roleChanged   = $newRole !== $oldRole;
        $statusChanged = $newState !== $user['status'];

        // Your own account: you may fix your details, but not lock or demote yourself out.
        if ($self && ($roleChanged || $newState !== 'active')) {
            return $this->failEdit($userId, 'You cannot change your own role or deactivate your own account. Ask another System Admin.');
        }

        // Never leave the system without an active System Admin.
        $losesAdmin = $oldRole === self::ROLE_ADMIN && $user['status'] === 'active'
            && ($newRole !== self::ROLE_ADMIN || $newState !== 'active');
        if ($losesAdmin && $this->activeAdminCount() <= 1) {
            return $this->failEdit($userId, 'This is the last active System Admin. Another System Admin must be active first.');
        }

        // A plan holder's record is tied to a Client account.
        if ($roleChanged && $oldRole === AccountRegistrationService::ROLE_CLIENT && $this->hasPlanHolder($userId)) {
            return $this->failEdit($userId, 'This user has a plan holder record, so the role cannot be changed from Client.');
        }

        // Promoting to System Admin needs the admin's own password, like creating one.
        if ($newRole === self::ROLE_ADMIN && $oldRole !== self::ROLE_ADMIN) {
            $me = $db->table('users')->select('password_hash')->where('user_id', (int) session('user_id'))->get()->getRowArray();
            if (! $me || ! password_verify((string) $this->request->getPost('admin_password'), (string) $me['password_hash'])) {
                return $this->failEdit($userId, 'Your own password is incorrect. Enter your password to make someone a System Admin.');
            }
        }

        // Branch: required for Branch Admin / Staff / Collector, optional for Client, none for System Admin.
        $branchId = (int) $this->request->getPost('branch_id');
        if ($newRole === self::ROLE_ADMIN) {
            $branchId = 0;
        } elseif (in_array($newRole, AccountRegistrationService::BRANCH_ROLES, true) && $branchId <= 0) {
            return $this->failEdit($userId, 'A branch is required for ' . $roles[$newRole] . ' accounts.');
        }
        if ($branchId > 0) {
            $branch = $db->table('branches')->select('status')->where('branch_id', $branchId)->get()->getRowArray();
            if (! $branch) {
                return $this->failEdit($userId, 'That branch does not exist.');
            }
            if ($branchId !== (int) $user['branch_id'] && $branch['status'] !== 'active') {
                return $this->failEdit($userId, 'Please select an active branch.');
            }
        }
        $newBranch = $branchId > 0 ? $branchId : null;

        // A branch manager who is moved or re-roled: the admin must say what happens to the manager post.
        $managed       = $this->managedBranch($userId);
        $managerAction = (string) $this->request->getPost('manager_action');
        // Only an active Branch Admin or Staff member can stay a manager. Becoming a
        // Collector, Client or System Admin, or being deactivated, always clears the post.
        $mustClear     = $managed !== null && (! in_array($newRole, [2, 3], true) || $newState !== 'active');
        $leavesPost    = $managed !== null && ($mustClear || $newBranch !== (int) $managed['branch_id']);
        if ($mustClear) {
            $managerAction = 'clear';
        } elseif ($leavesPost && ! in_array($managerAction, ['keep', 'clear'], true)) {
            return $this->failEdit($userId, 'This user is the manager of ' . $managed['branch_name'] . '. Choose whether to keep or clear them as manager.');
        }

        $isCollector = $newRole === AccountRegistrationService::ROLE_STAFF && (string) $this->request->getPost('is_collector') === '1' ? 1 : 0;

        $new = [
            'first_name'     => trim((string) $this->request->getPost('first_name')),
            'middle_name'    => trim((string) $this->request->getPost('middle_name')) ?: null,
            'last_name'      => trim((string) $this->request->getPost('last_name')),
            'contact_number' => trim((string) $this->request->getPost('contact_number')),
            'email'          => trim((string) $this->request->getPost('email')),
            'role_id'        => $newRole,
            'branch_id'      => $newBranch,
            'status'         => $newState,
            'is_collector'   => $isCollector,
        ];
        $old = array_intersect_key($user, $new);

        $db->transStart();
        $db->table('users')->where('user_id', $userId)->update($new);
        if ($leavesPost && $managerAction === 'clear') {
            $this->clearManager((int) $managed['branch_id']);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failEdit($userId, 'Unable to save the changes. Please try again.');
        }

        $changed = array_keys(array_filter($new, static fn ($v, $k) => (string) ($old[$k] ?? '') !== (string) ($v ?? ''), ARRAY_FILTER_USE_BOTH));
        $name    = trim($new['first_name'] . ' ' . $new['last_name']);
        (new ActivityLogService())->log(
            (int) session('user_id'),
            'updated',
            'user',
            $userId,
            "Updated the account of {$name} ({$user['username']})" . ($changed ? ': ' . implode(', ', $changed) : ' (no changes)')
                . ($leavesPost ? '; manager of ' . $managed['branch_name'] . ' ' . ($managerAction === 'clear' ? 'cleared' : 'kept') : ''),
            $old,
            $new
        );

        if ($roleChanged || $statusChanged) {
            (new NotificationService())->notify($userId, 'Your CareSync account was updated by an administrator.', 'general');
        }

        return redirect()->to(site_url('admin/users/' . $userId))->with('success', "{$name}'s account was updated.");
    }

    /** POST: activate or deactivate an account. */
    public function toggleStatus(int $userId)
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $user = $this->findUser($userId);
        if ($user === null) {
            return $this->backToList('error', 'That account was not found.');
        }

        $name       = trim($user['first_name'] . ' ' . $user['last_name']);
        $deactivate = $user['status'] === 'active';

        if ($deactivate) {
            if ($userId === (int) session('user_id')) {
                return $this->backToList('error', 'You cannot deactivate your own account.');
            }

            if ((int) $user['role_id'] === self::ROLE_ADMIN && $this->activeAdminCount() <= 1) {
                return $this->backToList('error', 'This is the last active System Admin. Another System Admin must be active first.');
            }
        }

        $new = $deactivate ? 'inactive' : 'active';
        $db  = db_connect();

        // A deactivated user cannot stay a branch manager: clear the post and close the history row.
        $managed = $deactivate ? $this->managedBranch($userId) : null;

        $db->transStart();
        $db->table('users')->where('user_id', $userId)->update(['status' => $new]);
        if ($managed !== null) {
            $this->clearManager((int) $managed['branch_id']);
        }
        $db->transComplete();

        (new ActivityLogService())->log(
            (int) session('user_id'),
            $deactivate ? 'deactivated' : 'activated',
            'user',
            $userId,
            ($deactivate ? 'Deactivated' : 'Activated') . " the account of {$name} ({$user['username']})"
                . ($managed !== null ? '; cleared as manager of ' . $managed['branch_name'] : ''),
            ['status' => $user['status']],
            ['status' => $new]
        );

        return $this->backToList('success', "{$name}'s account is now {$new}."
            . ($managed !== null ? ' They were also cleared as manager of ' . $managed['branch_name'] . ', which now has no manager.' : ''));
    }

    /**
     * POST: issue a new temporary password (same rules as every other
     * temporary password: 7 days, must be changed at next sign-in).
     */
    public function resetPassword(int $userId)
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $user = $this->findUser($userId);
        if ($user === null) {
            return $this->backToList('error', 'That account was not found.');
        }

        if ($userId === (int) session('user_id')) {
            return $this->backToList('error', 'Use "Change password" to change your own password.');
        }

        $service = new LoginDetailsService();
        $details = $service->reissue($userId);
        if ($details === null) {
            return $this->backToList('error', 'Unable to reset that password.');
        }

        $details['emailed']    = $service->email($details);
        $details['back_url']   = site_url('admin/users');
        $details['back_label'] = 'Back to users';

        $name = trim($user['first_name'] . ' ' . $user['last_name']);
        (new ActivityLogService())->log(
            (int) session('user_id'),
            'password_reset',
            'user',
            $userId,
            "Reset the password of {$name} ({$user['username']}); a temporary password was issued",
            null,
            ['must_change_password' => 1, 'emailed' => $details['emailed']]
        );

        return redirect()->to(site_url('account-login-details'))
            ->with('success', "A temporary password was issued for {$name}.")
            ->with('login_details', $details);
    }

    /** The account row without any password data. @return array<string,mixed>|null */
    private function loadFull(int $userId): ?array
    {
        return db_connect()->table('users u')
            ->select('u.user_id, u.username, u.email, u.first_name, u.middle_name, u.last_name, u.contact_number, u.role_id, u.branch_id, u.status, u.account_status, u.is_collector, u.is_plan_holder, u.must_change_password, u.temp_password_expires_at, u.last_login, u.created_at, r.role_name, b.branch_name')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->join('branches b', 'b.branch_id = u.branch_id', 'left')
            ->where('u.user_id', $userId)
            ->where('u.deleted_at', null)
            ->get()
            ->getRowArray() ?: null;
    }

    /** The branch this user is the assigned manager of, if any. @return array<string,mixed>|null */
    private function managedBranch(int $userId): ?array
    {
        return db_connect()->table('branches')->select('branch_id, branch_name')->where('manager_user_id', $userId)->get()->getRowArray() ?: null;
    }

    /** Remove the manager from a branch and close the open manager-history row (the row is kept). */
    private function clearManager(int $branchId): void
    {
        $db = db_connect();
        $db->table('branches')->where('branch_id', $branchId)->update([
            'manager_user_id' => null, 'manager_first_name' => null, 'manager_middle_name' => null,
            'manager_last_name' => null, 'manager_extension' => null,
        ]);
        $db->table('branch_manager_history')->where('branch_id', $branchId)->where('ended_at', null)->update(['ended_at' => date('Y-m-d H:i:s')]);
    }

    private function hasPlanHolder(int $userId): bool
    {
        return db_connect()->table('plan_holders')->where('user_id', $userId)->countAllResults() > 0;
    }

    private function failEdit(int $userId, string $message)
    {
        return redirect()->to(site_url('admin/users/' . $userId . '/edit'))->withInput()->with('error', $message);
    }

    /** Back to the form with what was typed (passwords are never sent back). */
    private function backWithInput(string $message)
    {
        return redirect()->back()->withInput()->with('error', $message);
    }

    private function denyUnlessAdmin()
    {
        if ((int) session('role_id') !== self::ROLE_ADMIN) {
            return redirect()->to('/unauthorized');
        }

        return null;
    }

    /** @return array<string,mixed>|null */
    private function findUser(int $userId): ?array
    {
        return db_connect()->table('users')
            ->select('user_id, username, first_name, last_name, role_id, status')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray() ?: null;
    }

    private function activeAdminCount(): int
    {
        return (int) db_connect()->table('users')
            ->where('role_id', self::ROLE_ADMIN)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /** Back to the list with the same filters and page the admin was looking at. */
    private function backToList(string $flash, string $message)
    {
        $back = (string) $this->request->getPost('return');
        // Only ever redirect inside this page.
        $url = str_starts_with($back, '?') ? site_url('admin/users') . $back : site_url('admin/users');

        return redirect()->to($url)->with($flash, $message);
    }
}
