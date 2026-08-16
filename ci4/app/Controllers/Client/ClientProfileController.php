<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\PlanHolderModel;
use App\Config\ValidationRules;
use App\Helpers\QueryHelper;

/**
 * ClientProfileController
 * 
 * Handles client profile management
 * Part of the refactored ClientPortal controller
 * 
 * Uses centralized validation rules and query helpers
 * to reduce code duplication
 */
class ClientProfileController extends BaseController
{
    use ClientPortalTrait;

    /**
     * Display client profile
     */
    public function profile(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];
        $planHolder = $access['plan_holder'];

        return view('client/profile', [
            'role_layout' => 'layouts/plan_holder',
            'user' => $user,
            'plan_holder' => $planHolder,
            'access' => $access,
        ]);
    }

    /**
     * Display client profile in edit mode
     */
    public function editProfile(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];
        $planHolder = $access['plan_holder'];

        return view('client/profile', [
            'role_layout' => 'layouts/plan_holder',
            'user' => $user,
            'plan_holder' => $planHolder,
            'access' => $access,
            'edit_mode' => true,
        ]);
    }

    /**
     * Update client profile information
     */
    public function updateProfile()
    {
        try {
            $user = $this->currentUser();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        
        $userId = (int) $user['user_id'];
        
        // Use centralized validation rules with email uniqueness check
        $rules = ValidationRules::getProfileRulesWithEmailUniqueness($userId);
        $messages = ValidationRules::getValidationMessages();

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Check email uniqueness using QueryHelper
        if (QueryHelper::emailExists(trim((string) $this->request->getPost('email')), $userId)) {
            return redirect()->back()->withInput()->with('error', 'Email is already in use by another account.');
        }

        $userModel = new UserModel();
        $planHolderModel = new PlanHolderModel();

        $db = db_connect();
        $db->transBegin();

        try {
            $userModel->update($userId, [
                'email' => trim((string) $this->request->getPost('email')),
                'contact_number' => trim((string) $this->request->getPost('contact_number')),
                'first_name' => trim((string) $this->request->getPost('first_name')),
                'last_name' => trim((string) $this->request->getPost('last_name')),
            ]);

            $planHolder = $this->currentPlanHolder();
            if ($planHolder) {
                $planHolderModel->update((int) $planHolder['plan_holder_id'], [
                    'address_barangay' => trim((string) $this->request->getPost('address_barangay')),
                    'address_city' => trim((string) $this->request->getPost('address_city')),
                    'civil_status' => trim((string) $this->request->getPost('civil_status')),
                    'citizenship' => trim((string) $this->request->getPost('citizenship')),
                ]);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Unable to save profile details.');
            }

            $db->transCommit();

            return redirect()->to('/client/profile')->with('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display change password form
     */
    public function changePassword(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $user = $access['user'];

        return view('client/profile_change_password', [
            'role_layout' => 'layouts/plan_holder',
            'user' => $user,
            'access' => $access,
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword()
    {
        try {
            $user = $this->currentUser();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $userId = (int) $user['user_id'];

        // Validate password fields
        $rules = [
            'current_password' => 'required|min_length[6]',
            'new_password' => 'required|min_length[8]|differs[current_password]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        $messages = [
            'current_password' => [
                'required' => 'Current password is required',
                'min_length' => 'Current password must be at least 6 characters',
            ],
            'new_password' => [
                'required' => 'New password is required',
                'min_length' => 'New password must be at least 8 characters',
                'differs' => 'New password must be different from current password',
            ],
            'confirm_password' => [
                'required' => 'Password confirmation is required',
                'matches' => 'Passwords do not match',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userModel = new UserModel();
        $currentPassword = trim((string) $this->request->getPost('current_password'));
        $newPassword = trim((string) $this->request->getPost('new_password'));

        // Verify current password
        if (! password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'Current password is incorrect.');
        }

        // Check if new password contains username
        if (stripos($newPassword, (string) ($user['username'] ?? '')) !== false) {
            return redirect()->back()->withInput()->with('error', 'New password cannot contain your username.');
        }

        try {
            $userModel->update($userId, [
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            ]);

            return redirect()->to('/client/profile')->with('success', 'Password updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update password. Please try again.');
        }
    }
}
