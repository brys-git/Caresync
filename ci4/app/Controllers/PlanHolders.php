<?php

namespace App\Controllers;

use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

class PlanHolders extends BaseController
{
    /**
     * Prompt 7, Step B: this page's own registration FORM is gone - it now
     * only shows the "Plan Holder Verification Queue" (approvals), with a
     * link out to the signed-in role's own new register page
     * (branch-admin/client/register or admin/client/register, both built
     * on the same shared wizard client self-service uses). branches/
     * existingUsers/id_types are no longer needed here since there's no
     * form left on this page to feed.
     */
    public function register()
    {
        // Only Admin (role 1) and BranchAdmin (role 2) can review approvals here.
        $roleId = (int) session('role_id');
        if (!in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized')->with('error', 'You do not have permission to view this page.');
        }

        $db = db_connect();

        $approvalRegistrations = [];
        $hasPendingTable = $this->pendingTableExists();
        if ($hasPendingTable) {
            $approvalRegistrations = $db->table('pending_plan_holder_registrations pr')
                ->select('pr.*, u.first_name, u.last_name, u.email, u.contact_number, b.branch_name, reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name')
                ->join('users u', 'u.user_id = pr.user_id', 'left')
                ->join('branches b', 'b.branch_id = pr.branch_id', 'left')
                ->join('users reviewer', 'reviewer.user_id = pr.reviewed_by', 'left')
                ->orderBy('pr.pending_registration_id', 'DESC')
                ->get()
                ->getResultArray();
        }

        $registerUrl = $roleId === 1 ? base_url('admin/client/register') : base_url('branch-admin/client/register');

        return view('plan_holders/register', [
            'has_pending_table' => $hasPendingTable,
            'approval_registrations' => $approvalRegistrations,
            'role_layout' => $this->resolveLayoutView(),
            'register_url' => $registerUrl,
            'page_title' => 'Plan holder verification queue',
            'page_sub' => 'Review and act on Government ID verification requests.',
            'page_actions' => '<a href="' . esc($registerUrl, 'attr') . '" class="btn btn-primary"><i class="ti ti-user-plus" aria-hidden="true"></i> Register a plan holder</a>',
        ]);
    }

    public function approvals(): ResponseInterface|string
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized');
        }

        if ($roleId === 1) {
            return redirect()->to('/plan-holders/register?tab=approvals');
        }

        if (! $this->pendingTableExists()) {
            return redirect()->back()->with('error', 'The registration queue is not available right now. Please try again later or contact the System Administrator.');
        }

        $db = db_connect();
        $builder = $db->table('pending_plan_holder_registrations pr')
            ->select('pr.*, u.first_name, u.last_name, u.email, u.contact_number, b.branch_name, reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name')
            ->join('users u', 'u.user_id = pr.user_id', 'left')
            ->join('branches b', 'b.branch_id = pr.branch_id', 'left')
            ->join('users reviewer', 'reviewer.user_id = pr.reviewed_by', 'left')
            ->orderBy('pr.pending_registration_id', 'DESC');

        if ($roleId === 2) {
            $builder->where('pr.branch_id', (int) session('branch_id'));
        }

        $registrations = $builder->get()->getResultArray();

        return view('plan_holders/approvals', [
            'role_layout' => $this->resolveLayoutView(),
            'page_title' => 'Plan holder registration approvals',
            'page_sub' => 'Review pending account registration requests before granting plan holder access.',
            'registrations' => $registrations,
        ]);
    }

    public function approve(int $pendingRegistrationId)
    {
        return $this->handleApprovalDecision($pendingRegistrationId, 'approved');
    }

    public function reject(int $pendingRegistrationId)
    {
        return $this->handleApprovalDecision($pendingRegistrationId, 'rejected');
    }

    private function resolveLayoutView(): string
    {
        $role = (int) session()->get('role_id');

        if ($role === 1) {
            return 'layouts/admin';
        }

        if ($role === 2) {
            return 'layouts/branch_admin';
        }

        if ($role === 3) {
            return 'layouts/staff';
        }

        return 'layouts/plan_holder';
    }

    private function hasExistingPlanHolder(int $userId): bool
    {
        return (new PlanHolderModel())->where('user_id', $userId)->first() !== null;
    }

    private function handleApprovalDecision(int $pendingRegistrationId, string $decision)
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [1, 2], true)) {
            return redirect()->to('/unauthorized');
        }

        if (! $this->pendingTableExists()) {
            return redirect()->back()->with('error', 'The registration queue is not available right now. Please try again later or contact the System Administrator.');
        }

        $db = db_connect();
        $query = $db->table('pending_plan_holder_registrations')
            ->where('pending_registration_id', $pendingRegistrationId);

        if ($roleId === 2) {
            $query->where('branch_id', (int) session('branch_id'));
        }

        $pending = $query->get()->getRowArray();
        if (! $pending) {
            return redirect()->back()->with('error', 'Registration request was not found.');
        }

        if (($pending['status'] ?? 'pending') !== 'pending') {
            return redirect()->back()->with('error', 'This registration request has already been reviewed.');
        }

        $db->transBegin();

        try {
            if ($decision === 'approved') {
                $userId = (int) ($pending['user_id'] ?? 0);

                if (! $this->hasExistingPlanHolder($userId)) {
                    $saved = (new PlanHolderModel())->insert([
                        'user_id' => $userId,
                        'branch_id' => (int) ($pending['branch_id'] ?? 0),
                        'unique_identifier' => $this->generateUniqueIdentifier($userId),
                        'address_no' => (string) ($pending['address_no'] ?? ''),
                        'address_street' => (string) ($pending['address_street'] ?? ''),
                        'address_barangay' => (string) ($pending['address_barangay'] ?? ''),
                        'address_city' => (string) ($pending['address_city'] ?? ''),
                        'date_of_birth' => $pending['date_of_birth'] ?? null,
                        'place_of_birth' => (string) ($pending['place_of_birth'] ?? ''),
                        'age' => $pending['age'] === null ? null : (int) $pending['age'],
                        'gender' => (string) ($pending['gender'] ?? ''),
                        'civil_status' => (string) ($pending['civil_status'] ?? ''),
                        'citizenship' => (string) ($pending['citizenship'] ?? ''),
                        'height' => $pending['height'] ?? null,
                        'weight' => $pending['weight'] ?? null,
                        'spouse_name' => (string) ($pending['spouse_name'] ?? ''),
                        'spouse_birthdate' => $pending['spouse_birthdate'] ?? null,
                        'spouse_occupation' => (string) ($pending['spouse_occupation'] ?? ''),
                        'senior_citizen_id' => (string) ($pending['senior_citizen_id'] ?? ''),
                        'organization_affiliation' => (string) ($pending['organization_affiliation'] ?? ''),
                        'status' => 'active',
                        'is_linked_account' => 1,
                    ]);

                    if (! $saved) {
                        throw new \RuntimeException('Failed to create plan holder record during approval.');
                    }
                }

                (new UserModel())->update($userId, [
                    'is_plan_holder' => 1,
                    'branch_id' => (int) ($pending['branch_id'] ?? 0),
                    'account_status' => 'verified',
                ]);

                (new NotificationService())->notify($userId, 'Your plan holder registration was approved. You can now apply for services and manage payments.', 'registration_pending');
                (new ActivityLogService())->log((int) session('user_id'), 'approved', 'plan_holder', $pendingRegistrationId, 'Approved plan holder registration', ['status' => 'pending'], ['status' => 'approved']);
            } else {
                $rejectionNotes = trim((string) $this->request->getPost('rejection_notes'));
                (new NotificationService())->notify((int) ($pending['user_id'] ?? 0), 'Your plan holder registration was rejected. ' . ($rejectionNotes !== '' ? 'Reason: ' . $rejectionNotes : 'Please update your details and submit again.'), 'registration_pending');
                (new ActivityLogService())->log((int) session('user_id'), 'rejected', 'plan_holder', $pendingRegistrationId, 'Rejected plan holder registration', ['status' => 'pending'], ['status' => 'rejected']);
            }

            $updatePayload = [
                'status' => $decision,
                'reviewed_by' => (int) session('user_id'),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ];

            if ($decision === 'rejected') {
                $updatePayload['rejection_notes'] = trim((string) $this->request->getPost('rejection_notes'));
            }

            $db->table('pending_plan_holder_registrations')
                ->where('pending_registration_id', $pendingRegistrationId)
                ->update($updatePayload);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return redirect()->back()->with('success', 'Registration request ' . $decision . ' successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function pendingTableExists(): bool
    {
        return db_connect()->tableExists('pending_plan_holder_registrations');
    }

    private function generateUniqueIdentifier(int $userId): string
    {
        $planHolderModel = new PlanHolderModel();
        $seed = 'PH-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT) . '-' . date('Ymd');
        $candidate = $seed;
        $counter = 1;

        while ($planHolderModel->where('unique_identifier', $candidate)->first() !== null) {
            $candidate = $seed . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $candidate;
    }
}
