<?php

namespace App\Config;

use App\Services\IdVerificationService;

/**
 * ValidationRules Configuration
 *
 * Centralized validation rules used across controllers
 * to avoid duplication and maintain consistency
 *
 * Usage:
 * $rules = ValidationRules::getProfileRules();
 * if (! $this->validate($rules)) { ... }
 */
class ValidationRules
{
    /**
     * User Profile Update Rules
     */
    public static function getProfileRules(): array
    {
        return [
            'first_name' => 'required|max_length[50]',
            'last_name' => 'required|max_length[50]',
            'email' => 'required|valid_email|max_length[100]',
            'contact_number' => 'permit_empty|max_length[20]',
        ];
    }

    /**
     * User Profile Update Rules with Email Uniqueness Check
     * 
     * @param int $userId - Current user ID to exclude from uniqueness check
     */
    public static function getProfileRulesWithEmailUniqueness(int $userId = 0): array
    {
        $rules = self::getProfileRules();
        $rules['email'] = 'required|valid_email|max_length[100]|is_unique[users.email,user_id,' . $userId . ']';
        return $rules;
    }

    /**
     * Plan Registration Rules
     *
     * Step 1 (Applicant) + Step 3 (Government ID). The spouse/beneficiary
     * conditional rules are applied in the controller (spouse depends on
     * civil_status; beneficiaries are array rows).
     */
    public static function getPlanRegistrationRules(): array
    {
        return [
            // Applicant identity
            'last_name' => 'required|max_length[50]',
            'first_name' => 'required|max_length[50]',
            'email' => 'required|valid_email|max_length[100]',
            'gender' => 'required|in_list[Male,Female,Other]',
            'date_of_birth' => 'required|valid_date[Y-m-d]',
            'civil_status' => 'required|in_list[Single,Married,Divorced,Widowed]',
            'contact_number' => 'required|regex_match[/^[0-9+\-()\s]+$/]|min_length[10]|max_length[20]',
            'address_barangay' => 'required|max_length[100]',
            'address_city' => 'required|max_length[100]',
            'citizenship' => 'permit_empty|max_length[50]',
            'application_date' => 'required|valid_date[Y-m-d]',
            'coordinator_user_id' => 'required|is_natural_no_zero',
            'branch_id' => 'required|numeric',
            'package_id' => 'required|numeric',

            // Government ID (Level 1)
            'id_type' => 'required|in_list[' . implode(',', IdVerificationService::supportedIdKeys()) . ']',
            'id_number' => 'required|max_length[100]',
        ];
    }

    /**
     * Payment Submission Rules
     */
    public static function getPaymentRules(): array
    {
        return [
            'months_covered' => 'required|in_list[1,3,6,12]',
            'amount' => 'required|decimal',
            'payment_method' => 'required|in_list[gcash,cash]',
            'reference_number' => 'required|max_length[100]',
        ];
    }

    /**
     * Service Application Rules
     */
    public static function getServiceApplicationRules(): array
    {
        return [
            'service_id' => 'required|numeric',
            'requested_date' => 'required|valid_date[Y-m-d]',
            'location_address' => 'required|min_length[10]|max_length[500]',
            'contact_person' => 'required|min_length[3]|max_length[100]',
        ];
    }

    /**
     * Staff Schedule Rules
     */
    public static function getStaffScheduleRules(): array
    {
        return [
            'user_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
            'duty_date' => 'required|valid_date[Y-m-d]',
            'start_time' => 'required|valid_date[H:i]',
            'end_time' => 'required|valid_date[H:i]',
            'duty_type' => 'required|in_list[regular,emergency,training,maintenance]',
        ];
    }

    /**
     * Email Field Validation Rule
     */
    public static function getEmailRule(bool $unique = true, int $excludeUserId = 0): string
    {
        $rule = 'required|valid_email|max_length[100]';
        
        if ($unique && $excludeUserId > 0) {
            $rule .= '|is_unique[users.email,user_id,' . $excludeUserId . ']';
        } elseif ($unique) {
            $rule .= '|is_unique[users.email]';
        }
        
        return $rule;
    }

    /**
     * Get validation message overrides (optional)
     */
    public static function getValidationMessages(): array
    {
        return [
            'last_name' => [
                'required' => 'Last name is required',
            ],
            'first_name' => [
                'required' => 'Given name is required',
            ],
            'contact_number' => [
                'required' => 'Contact number is required',
                'regex_match' => 'Contact number contains invalid characters',
                'min_length' => 'Contact number must be at least 10 digits',
            ],
            'email' => [
                'required' => 'Email address is required',
                'valid_email' => 'Please enter a valid email address',
                'is_unique' => 'Email is already registered in the system',
            ],
            'gender' => [
                'required' => 'Gender is required',
                'in_list' => 'Invalid gender selection',
            ],
            'date_of_birth' => [
                'required' => 'Date of birth is required',
                'valid_date' => 'Please enter a valid date of birth',
            ],
            'application_date' => [
                'required' => 'Date of application is required',
                'valid_date' => 'Please enter a valid application date',
            ],
            'coordinator_user_id' => [
                'required' => 'Please select your coordinator',
                'is_natural_no_zero' => 'Please select a valid coordinator',
            ],
            'spouse_first_name' => [
                'required' => 'Spouse given name is required when civil status is Married',
            ],
            'spouse_last_name' => [
                'required' => 'Spouse last name is required when civil status is Married',
            ],
            'id_type' => [
                'required' => 'Please select the government ID type',
                'in_list' => 'Unsupported government ID type',
            ],
            'id_number' => [
                'required' => 'ID number is required',
            ],
            'package_id' => [
                'required' => 'Please select a package',
                'numeric' => 'Invalid package selection',
            ],
            'service_id' => [
                'required' => 'Please select a service',
                'numeric' => 'Invalid service selection',
            ],
        ];
    }
}
