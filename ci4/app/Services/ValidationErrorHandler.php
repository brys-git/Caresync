<?php

namespace App\Services;

/**
 * ValidationErrorHandler Service
 * 
 * Centralized error handling and formatting for validation failures
 * Provides consistent error messages and status codes across the application
 * 
 * Usage:
 * $handler = new ValidationErrorHandler();
 * if (!$this->validate($rules)) {
 *     return $handler->handleValidationError($this->validator->getErrors());
 * }
 */
class ValidationErrorHandler
{
    /**
     * ValidationErrorHandler constructor
     */
    public function __construct() {}

    /**
     * Format validation errors for display to user
     * 
     * @param array $errors - Array of validation errors from $this->validator->getErrors()
     * @return array - Formatted errors with user-friendly messages
     */
    public function formatErrors(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $field => $message) {
            $formatted[$field] = $this->getUserFriendlyMessage($field, $message);
        }

        return $formatted;
    }

    /**
     * Get user-friendly error message based on field and error type
     * 
     * @param string $field - Field name
     * @param string $message - Original error message
     * @return string - User-friendly message
     */
    public function getUserFriendlyMessage(string $field, string $message): string
    {
        // Extract rule type from message
        if (str_contains($message, 'required')) {
            return $this->getRequiredMessage($field);
        }

        if (str_contains($message, 'valid_email')) {
            return 'Please enter a valid email address.';
        }

        if (str_contains($message, 'is_unique')) {
            return ucfirst($field) . ' is already in use. Please try another.';
        }

        if (str_contains($message, 'numeric')) {
            return ucfirst($field) . ' must be a number.';
        }

        if (str_contains($message, 'min_length')) {
            return ucfirst($field) . ' is too short. Check the minimum length.';
        }

        if (str_contains($message, 'max_length')) {
            return ucfirst($field) . ' is too long. Check the maximum length.';
        }

        if (str_contains($message, 'in_list')) {
            return 'Invalid selection for ' . $field . '.';
        }

        // Default: return original message
        return $message;
    }

    /**
     * Get contextual "required" message based on field
     * 
     * @param string $field - Field name
     * @return string
     */
    private function getRequiredMessage(string $field): string
    {
        $messages = [
            'email' => 'Email address is required.',
            'password' => 'Password is required.',
            'first_name' => 'First name is required.',
            'last_name' => 'Last name is required.',
            'contact_number' => 'Contact number is required.',
            'plan_id' => 'Please select a plan.',
            'package_id' => 'Please select a package.',
            'service_id' => 'Please select a service.',
            'payment_method' => 'Please select a payment method.',
            'amount' => 'Amount is required.',
            'beneficiary_name' => 'Beneficiary name is required.',
            'relationship' => 'Relationship is required.',
            'civil_status' => 'Civil status is required.',
        ];

        return $messages[strtolower($field)] ?? ucfirst($field) . ' is required.';
    }

    /**
     * Validate and return errors as JSON (for AJAX requests)
     * 
     * @param array $errors - Validation errors
     * @param string $status - Status (default: 'error')
     * @return array - Formatted JSON response
     */
    public function getJsonResponse(array $errors, string $status = 'error'): array
    {
        return [
            'status' => $status,
            'code' => 422,
            'message' => 'Validation failed. Please check your input.',
            'errors' => $this->formatErrors($errors),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Validate and return summary error message (for redirect)
     * 
     * @param array $errors - Validation errors
     * @param int $maxErrors - Maximum number of errors to summarize
     * @return string - Summary message like "3 errors found: email, password, name"
     */
    public function getSummaryMessage(array $errors, int $maxErrors = 3): string
    {
        if (empty($errors)) {
            return 'Validation failed.';
        }

        $count = count($errors);
        $fields = implode(', ', array_slice(array_keys($errors), 0, $maxErrors));

        if ($count === 1) {
            return "Please correct the $fields field.";
        }

        if ($count > $maxErrors) {
            return "$count errors found: $fields, and more. Please review all fields.";
        }

        return "$count errors found: $fields. Please review these fields.";
    }

    /**
     * Handle form validation error and redirect
     * 
     * @param array $errors - Validation errors from validator
     * @param string $backUrl - URL to redirect to (default: back)
     * @param string $summaryType - How to show errors: 'summary' or 'list'
     * @return void - Performs redirect with session data
     */
    public function handleValidationError(
        array $errors,
        string $backUrl = 'back',
        string $summaryType = 'summary'
    ): \CodeIgniter\HTTP\RedirectResponse {
        $session = session();

        if ($summaryType === 'list') {
            $session->setFlashdata('validation_errors', $errors);
            $message = $this->getSummaryMessage($errors);
        } else {
            $message = $this->getSummaryMessage($errors);
        }

        if ($backUrl === 'back') {
            return redirect()->back()
                ->withInput()
                ->with('error', $message)
                ->with('validation_errors', $errors);
        } else {
            return redirect()->to($backUrl)
                ->with('error', $message)
                ->with('validation_errors', $errors);
        }
    }

    /**
     * Check if specific field has validation errors
     * 
     * @param string $field - Field name to check
     * @param array $errors - All validation errors
     * @return bool
     */
    public function hasError(string $field, array $errors): bool
    {
        return ! empty($errors[$field] ?? null);
    }

    /**
     * Get error for specific field
     * 
     * @param string $field - Field name
     * @param array $errors - All validation errors
     * @return string - Error message or empty string
     */
    public function getFieldError(string $field, array $errors): string
    {
        if (! $this->hasError($field, $errors)) {
            return '';
        }

        return $this->getUserFriendlyMessage($field, $errors[$field]);
    }

    /**
     * Get all errors as a single error string (comma-separated)
     * 
     * @param array $errors - All validation errors
     * @return string - All errors joined by comma and space
     */
    public function getErrorString(array $errors): string
    {
        $messages = [];
        foreach ($errors as $field => $error) {
            $messages[] = $this->getUserFriendlyMessage($field, $error);
        }
        return implode('; ', $messages);
    }

    /**
     * Log validation error for debugging/audit trail
     * 
     * @param array $errors - Validation errors
     * @param string $context - Context (e.g., 'profile_update', 'plan_registration')
     * @param int $userId - User ID (optional)
     * @return bool - Success status
     */
    public function logValidationError(
        array $errors,
        string $context,
        int $userId = 0
    ): bool {
        try {
            $logger = \Config\Services::logger();
            
            $logData = [
                'context' => $context,
                'user_id' => $userId,
                'error_count' => count($errors),
                'errors' => $errors,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ];

            $logger->info('Validation Error: ' . json_encode($logData));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Validate and apply custom rules if needed
     * 
     * @param array $rules - Validation rules
     * @param array $data - Data to validate
     * @param array $customRules - Custom rule callbacks
     * @return array - Errors array (empty if validation passes)
     */
    public function validateWithCustomRules(
        array $rules,
        array $data,
        array $customRules = []
    ): array {
        // This would be integrated with CodeIgniter's validation service
        // For now, it's a placeholder for more complex validation logic
        $errors = [];

        foreach ($customRules as $field => $callback) {
            if (isset($data[$field])) {
                $result = $callback($data[$field], $data);
                if ($result !== true) {
                    $errors[$field] = $result;
                }
            }
        }

        return $errors;
    }

    /**
     * Get validation rules with descriptions (for API documentation)
     * 
     * @return array - Rules with descriptions
     */
    public static function getRulesDocumentation(): array
    {
        return [
            'valid_philippine_phone' => [
                'description' => 'Validates Philippine contact number format',
                'accepts' => '09xxxxxxxxx or +639xxxxxxxxx',
                'example' => '09123456789',
            ],
            'valid_beneficiary_name' => [
                'description' => 'Validates beneficiary name (at least 2 words)',
                'accepts' => 'Full name with first and last name',
                'example' => 'Juan Dela Cruz',
            ],
            'valid_relationship' => [
                'description' => 'Validates relationship to beneficiary',
                'accepts' => 'spouse, child, parent, sibling, etc.',
                'example' => 'spouse',
            ],
            'valid_civil_status' => [
                'description' => 'Validates civil status',
                'accepts' => 'single, married, widowed, divorced, separated, annulled',
                'example' => 'married',
            ],
            'valid_future_date' => [
                'description' => 'Validates date is in the future',
                'accepts' => 'Any date after today',
                'example' => '2026-05-15',
            ],
            'valid_time_format' => [
                'description' => 'Validates time in 24-hour format',
                'accepts' => 'HH:MM format',
                'example' => '14:30',
            ],
        ];
    }
}
