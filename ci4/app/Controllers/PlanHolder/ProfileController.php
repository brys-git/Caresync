<?php

namespace App\Controllers\PlanHolder;

use App\Controllers\BaseController;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ProfileController extends BaseController
{
    public function index(): string
    {
        return $this->renderProfilePage(false);
    }

    public function edit(): string
    {
        return $this->renderProfilePage(true);
    }

    public function update()
    {
        $profile = $this->getCurrentClientProfile();
        $userId = (int) $profile['user_id'];

        $rules = [
            'email' => 'required|valid_email|max_length[100]',
            'contact_number' => 'required|max_length[30]',
            'address_street' => 'required|max_length[100]',
            'address_barangay' => 'required|max_length[100]',
            'address_city' => 'required|max_length[100]',
            'civil_status' => 'required|max_length[20]',
            'citizenship' => 'required|max_length[50]',
            'height' => 'permit_empty|decimal',
            'weight' => 'permit_empty|decimal',
            'spouse_name' => 'permit_empty|max_length[100]',
            'spouse_birthdate' => 'permit_empty|valid_date[Y-m-d]',
            'spouse_occupation' => 'permit_empty|max_length[100]',
            'organization_affiliation' => 'permit_empty|max_length[100]',
            'new_password' => 'permit_empty|min_length[8]|max_length[255]',
            'confirm_password' => 'permit_empty|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email = trim((string) $this->request->getPost('email'));
        $contactNumber = trim((string) $this->request->getPost('contact_number'));
        $newPassword = (string) $this->request->getPost('new_password');

        $userModel = new UserModel();
        $planHolderModel = new PlanHolderModel();
        $existingByEmail = $userModel
            ->where('email', $email)
            ->where('user_id !=', $userId)
            ->first();

        if ($existingByEmail) {
            return redirect()->back()->withInput()->with('error', 'Email is already in use by another account.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $userData = [
                'email' => $email,
                'contact_number' => $contactNumber,
            ];

            if ($newPassword !== '') {
                $userData['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $updatedUser = $userModel->update($userId, $userData);
            if (! $updatedUser) {
                throw new \RuntimeException('Failed to update account credentials.');
            }

            $updatedPlanHolder = $planHolderModel
                ->where('user_id', $userId)
                ->set([
                    'address_street' => trim((string) $this->request->getPost('address_street')),
                    'address_barangay' => trim((string) $this->request->getPost('address_barangay')),
                    'address_city' => trim((string) $this->request->getPost('address_city')),
                    'civil_status' => trim((string) $this->request->getPost('civil_status')),
                    'citizenship' => trim((string) $this->request->getPost('citizenship')),
                    'height' => $this->nullableDecimal('height'),
                    'weight' => $this->nullableDecimal('weight'),
                    'spouse_name' => trim((string) $this->request->getPost('spouse_name')),
                    'spouse_birthdate' => $this->nullableDate('spouse_birthdate'),
                    'spouse_occupation' => trim((string) $this->request->getPost('spouse_occupation')),
                    'organization_affiliation' => trim((string) $this->request->getPost('organization_affiliation')),
                ])
                ->update();

            if (! $updatedPlanHolder) {
                throw new \RuntimeException('Failed to update personal profile details.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return redirect()->to(base_url('client/profile'))->with('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function renderProfilePage(bool $editMode): string
    {
        $profile = $this->getCurrentClientProfile();

        return view('plan_holder/profile/index', [
            'role_layout' => 'layouts/plan_holder',
            'profile' => $profile,
            'edit_mode' => $editMode,
        ]);
    }

    private function getCurrentClientProfile(): array
    {
        $userId = (int) session('user_id');
        $roleId = (int) session('role_id');

        if ($userId <= 0 || $roleId !== 4) {
            throw PageNotFoundException::forPageNotFound();
        }

        $profile = db_connect()->table('users u')
            ->select('u.user_id, u.username, u.email, u.contact_number, u.account_status, u.first_name, u.middle_name, u.last_name, u.name_extension, p.plan_holder_id, p.address_no, p.address_street, p.address_barangay, p.address_city, p.date_of_birth, p.place_of_birth, p.age, p.gender, p.civil_status, p.citizenship, p.height, p.weight, p.spouse_name, p.spouse_birthdate, p.spouse_occupation, p.organization_affiliation, p.unique_identifier, p.status AS membership_status')
            ->join('plan_holders p', 'p.user_id = u.user_id', 'inner')
            ->where('u.user_id', $userId)
            ->get()
            ->getRowArray();

        if (! $profile) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $profile;
    }

    private function nullableDecimal(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }

    private function nullableDate(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }
}
