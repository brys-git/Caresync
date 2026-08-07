<?php

namespace App\Services\Import;

/**
 * Generates temporary login credentials for imported clients who have no
 * existing account. Username/email are created at staging (shown on the review
 * screen, regenerated when the admin edits the name); the password is created
 * at commit time.
 */
class ImportCredentialService
{
    /** Placeholder email domain (RFC-2606 — never resolves, clearly temporary). */
    private const EMAIL_DOMAIN = 'kaagapay.local';

    private const PASSWORD_CHARSET = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const PASSWORD_SYMBOLS = '!@#$%^&*-_+?';

    /**
     * @param array<int, string> $takenUsernames usernames already generated in this batch
     */
    public function generateUsername(string $firstName, string $lastName, array $takenUsernames = []): string
    {
        $base = strtolower(substr($firstName, 0, 1) . $lastName);
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?? '';
        if ($base === '') {
            $base = 'client';
        }
        // Username rule requires >= 4 chars.
        while (strlen($base) < 4) {
            $base .= substr(self::PASSWORD_CHARSET, random_int(0, strlen(self::PASSWORD_CHARSET) - 1), 1);
        }
        $base = substr($base, 0, 20);

        if (! $this->usernameExists($base) && ! in_array($base, $takenUsernames, true)) {
            return $base;
        }

        $suffix = 2;
        while (true) {
            $candidate = $base . '_' . $suffix;
            if (strlen($candidate) > 24) {
                $candidate = substr($candidate, 0, 24);
            }
            if (! $this->usernameExists($candidate) && ! in_array($candidate, $takenUsernames, true)) {
                return $candidate;
            }
            $suffix++;
            if ($suffix > 50) {
                return $base . '_' . bin2hex(random_bytes(2));
            }
        }
    }

    /**
     * @param array<int, string> $takenEmails emails already generated in this batch
     */
    public function generateEmail(string $username, array $takenEmails = []): string
    {
        $email = $username . '@' . self::EMAIL_DOMAIN;

        if (! $this->emailExists($email) && ! in_array($email, $takenEmails, true)) {
            return $email;
        }

        $suffix = 2;
        while (true) {
            $candidate = $username . $suffix . '@' . self::EMAIL_DOMAIN;
            if (! $this->emailExists($candidate) && ! in_array($candidate, $takenEmails, true)) {
                return $candidate;
            }
            $suffix++;
            if ($suffix > 50) {
                return $username . '_' . bin2hex(random_bytes(2)) . '@' . self::EMAIL_DOMAIN;
            }
        }
    }

    /**
     * A 12-character password guaranteed to contain upper + lower + digit + symbol,
     * so it satisfies the app's password validation.
     */
    public function generatePassword(): string
    {
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $digits = '23456789';

        $password = '';
        // Guarantee one of each required class.
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= self::PASSWORD_SYMBOLS[random_int(0, strlen(self::PASSWORD_SYMBOLS) - 1)];

        while (strlen($password) < 12) {
            $password .= self::PASSWORD_CHARSET[random_int(0, strlen(self::PASSWORD_CHARSET) - 1)];
        }

        return str_shuffle($password);
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    private function usernameExists(string $username): bool
    {
        $row = db_connect()->table('users')->select('user_id')->where('username', $username)->get()->getRow();

        return $row !== null;
    }

    private function emailExists(string $email): bool
    {
        $row = db_connect()->table('users')->select('user_id')->where('email', $email)->get()->getRow();

        return $row !== null;
    }
}
