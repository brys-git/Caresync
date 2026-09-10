<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\GovernmentIdVerification;
use Config\Services;
use App\Models\GovernmentIdVerificationModel;

/**
 * GovernmentIdVerificationService
 *
 * Reusable identity-verification component, built to be shared by every
 * CareSync registration flow (Client/Plan Holder first, Staff/Branch
 * Admin/Admin account creation in a later phase) rather than duplicated
 * per role. A registration controller only ever calls verify() - it never
 * touches file storage, the OCR provider, or the matching logic directly,
 * so the provider (currently Claude vision, via the Anthropic API) can be
 * swapped later without any registration form being rewritten.
 *
 * Storage/security follow the same pattern already established by
 * service_application_documents (App\Controllers\Client\
 * ClientServiceController): files live under WRITEPATH (outside the
 * public webroot), with a randomly generated filename - never a
 * predictable or publicly-reachable path. Only a masked ID number and a
 * short extracted name/DOB/gender are retained for later staff review;
 * no raw OCR payload or full ID number is stored.
 */
class GovernmentIdVerificationService
{
    private GovernmentIdVerification $config;
    private GovernmentIdVerificationModel $model;

    public function __construct()
    {
        $this->config = config(GovernmentIdVerification::class);
        $this->model = new GovernmentIdVerificationModel();
    }

    /**
     * @return array<string, string> value => label, for a <select>
     */
    public function idTypes(): array
    {
        return $this->config->idTypes;
    }

    public function latestForUser(int $userId): ?array
    {
        return $this->model->latestForUser($userId);
    }

    /**
     * Validate, store, OCR-extract, and compare an uploaded/captured ID
     * image against the identity entered on the registration form. Never
     * throws - a provider outage or bad image always resolves to a
     * usable status (never blocks the registration form itself from
     * being submitted; 'needs_review' just means a human should look at
     * it afterward).
     *
     * For a user verifying their OWN identity (the account already
     * exists - e.g. Client/Plan Holder self-service registration).
     *
     * @param array{first_name:string, middle_name?:string, last_name:string, date_of_birth?:string} $claimedIdentity
     * @return array{status: string, message: string, verification_id: ?int}
     */
    public function verify(int $userId, string $idType, UploadedFile $file, array $claimedIdentity): array
    {
        if ($userId <= 0) {
            return $this->result('failed', 'Unable to process verification. Please try again.');
        }

        $fileError = $this->validateRequest($idType, $file);
        if ($fileError !== null) {
            return $this->result('failed', $fileError);
        }

        $stored = $this->storeFileTo('' . $userId, $file);
        if ($stored === null) {
            log_message('error', 'GovernmentIdVerificationService::verify - failed to store uploaded ID image for user ' . $userId);

            return $this->result('failed', 'Unable to save the uploaded image. Please try again.');
        }

        $outcome = $this->runExtractionAndCompare($idType, $stored, $file, $claimedIdentity);

        $verificationId = $this->persist($userId, $idType, $stored, $outcome['fields']);

        return $this->result($outcome['status'], $outcome['message'], $verificationId);
    }

    /**
     * Same as verify(), but for a creator (Admin/Branch Admin/Staff)
     * verifying SOMEONE ELSE'S identity as part of creating a brand new
     * account on Users::create() - the new user_id doesn't exist yet at
     * upload time, so the result is stashed under a random, unguessable
     * token instead of a user_id and only persisted for real once
     * commitPending() is called after the account is actually created.
     * Nothing about the extracted/matched result is ever trusted back
     * from the browser - only the token, which just points at what this
     * service itself already computed and saved server-side.
     *
     * @param array{first_name:string, middle_name?:string, last_name:string, date_of_birth?:string} $claimedIdentity
     * @return array{status: string, message: string, pending_token: ?string}
     */
    public function verifyPending(string $idType, UploadedFile $file, array $claimedIdentity): array
    {
        $fileError = $this->validateRequest($idType, $file);
        if ($fileError !== null) {
            return ['status' => 'failed', 'message' => $fileError, 'pending_token' => null];
        }

        $token = bin2hex(random_bytes(20));
        $stored = $this->storeFileTo('_pending' . DIRECTORY_SEPARATOR . $token, $file);
        if ($stored === null) {
            log_message('error', 'GovernmentIdVerificationService::verifyPending - failed to store uploaded ID image.');

            return ['status' => 'failed', 'message' => 'Unable to save the uploaded image. Please try again.', 'pending_token' => null];
        }

        $outcome = $this->runExtractionAndCompare($idType, $stored, $file, $claimedIdentity);

        $sidecar = array_merge([
            'id_type'       => $idType,
            'file_path'     => $stored['relative_path'],
            'original_name' => $stored['original_name'],
            'mime_type'     => $stored['mime_type'],
        ], $outcome['fields']);

        $sidecarPath = dirname($stored['full_path']) . DIRECTORY_SEPARATOR . 'result.json';
        if (file_put_contents($sidecarPath, json_encode($sidecar)) === false) {
            log_message('error', 'GovernmentIdVerificationService::verifyPending - failed to write sidecar for token ' . $token);

            return ['status' => 'failed', 'message' => 'Unable to save the verification result. Please try again.', 'pending_token' => null];
        }

        return ['status' => $outcome['status'], 'message' => $outcome['message'], 'pending_token' => $token];
    }

    /**
     * Finalizes a verifyPending() result once the real account has been
     * created - moves the temp image into the user's own directory and
     * writes the government_id_verifications row with the real user_id.
     * Returns null (logged, non-fatal) if the token is missing/expired/
     * already committed - account creation itself must never fail just
     * because of this.
     */
    public function commitPending(string $pendingToken, int $userId): ?int
    {
        $pendingToken = preg_replace('/[^a-f0-9]/', '', $pendingToken) ?? '';
        if ($pendingToken === '' || $userId <= 0) {
            return null;
        }

        $pendingDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'government_ids' . DIRECTORY_SEPARATOR . '_pending' . DIRECTORY_SEPARATOR . $pendingToken;
        $sidecarPath = $pendingDir . DIRECTORY_SEPARATOR . 'result.json';

        if (! is_file($sidecarPath)) {
            log_message('warning', 'GovernmentIdVerificationService::commitPending - no pending verification found for token.');

            return null;
        }

        $sidecar = json_decode((string) file_get_contents($sidecarPath), true);
        if (! is_array($sidecar) || empty($sidecar['file_path'])) {
            return null;
        }

        $oldFullPath = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, (string) $sidecar['file_path']);
        $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'government_ids' . DIRECTORY_SEPARATOR . $userId;

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            return null;
        }

        $newFullPath = $targetDir . DIRECTORY_SEPARATOR . basename($oldFullPath);
        if (is_file($oldFullPath) && ! @rename($oldFullPath, $newFullPath)) {
            return null;
        }

        $relativePath = str_replace('\\', '/', str_replace(WRITEPATH, '', $newFullPath));

        $verificationId = (int) $this->model->insert([
            'user_id'                     => $userId,
            'id_type'                     => (string) ($sidecar['id_type'] ?? 'other'),
            'file_path'                   => $relativePath,
            'original_name'               => $sidecar['original_name'] ?? null,
            'mime_type'                   => $sidecar['mime_type'] ?? null,
            'verification_status'         => (string) ($sidecar['verification_status'] ?? 'needs_review'),
            'match_result'                => $sidecar['match_result'] ?? null,
            'extracted_name'              => $sidecar['extracted_name'] ?? null,
            'extracted_birth_date'        => $sidecar['extracted_birth_date'] ?? null,
            'extracted_gender'            => $sidecar['extracted_gender'] ?? null,
            'extracted_id_number_masked'  => $sidecar['extracted_id_number_masked'] ?? null,
            'mismatch_reason'             => $sidecar['mismatch_reason'] ?? null,
            'verification_attempts'       => 1,
            'verified_at'                 => $sidecar['verified_at'] ?? null,
        ], true);

        // Best-effort cleanup - a leftover empty _pending/{token} dir is
        // harmless, never worth failing account creation over.
        @unlink($sidecarPath);
        @rmdir($pendingDir);

        return $verificationId > 0 ? $verificationId : null;
    }

    /**
     * Shared by verify() and verifyPending() - runs OCR extraction and
     * identity matching and returns the DB-shaped fields plus a status/
     * message, without touching the database itself (the two callers
     * persist differently: immediately vs. stashed-then-committed).
     *
     * @param array{full_path: string, relative_path: string, original_name: string, mime_type: string} $stored
     * @param array{first_name:string, middle_name?:string, last_name:string, date_of_birth?:string} $claimedIdentity
     * @return array{status: string, message: string, fields: array<string, mixed>}
     */
    private function runExtractionAndCompare(string $idType, array $stored, UploadedFile $file, array $claimedIdentity): array
    {
        if ($this->config->anthropicApiKey === '') {
            log_message('info', 'GovernmentIdVerificationService - no Anthropic API key configured, marking needs_review.');

            return [
                'status'  => 'needs_review',
                'message' => 'Your ID was received. Staff will review it as part of your application.',
                'fields'  => [
                    'verification_status' => 'needs_review',
                    'mismatch_reason'      => 'Automatic verification is not configured.',
                ],
            ];
        }

        $extraction = $this->extractFromImage($stored['full_path'], (string) $file->getClientMimeType());

        if ($extraction === null) {
            return [
                'status'  => 'needs_review',
                'message' => 'We could not automatically verify this ID right now. Your registration can still be submitted - staff will review it.',
                'fields'  => [
                    'verification_status' => 'needs_review',
                    'mismatch_reason'      => 'Automatic verification was temporarily unavailable.',
                ],
            ];
        }

        if (! $extraction['readable']) {
            return [
                'status'  => 'needs_review',
                'message' => 'The ID image is difficult to read. Please upload a clearer image or take another photo.',
                'fields'  => [
                    'verification_status' => 'needs_review',
                    'mismatch_reason'      => 'Image quality too low to read.',
                ],
            ];
        }

        $comparison = $this->compareIdentity($extraction, $claimedIdentity);

        return [
            'status'  => $comparison['status'],
            'message' => $comparison['message'],
            'fields'  => [
                'verification_status'        => $comparison['status'],
                'match_result'                => $comparison['match_result'],
                'extracted_name'              => $extraction['full_name'],
                'extracted_birth_date'        => $this->toDbDate($extraction['date_of_birth']),
                'extracted_gender'            => $extraction['gender'],
                'extracted_id_number_masked'  => $this->maskIdNumber($extraction['id_number']),
                'mismatch_reason'             => $comparison['reason'],
                'verified_at'                 => $comparison['status'] === 'verified' ? date('Y-m-d H:i:s') : null,
            ],
        ];
    }

    private function validateRequest(string $idType, UploadedFile $file): ?string
    {
        if (! array_key_exists($idType, $this->config->idTypes)) {
            return 'Please select a valid ID type.';
        }

        return $this->validateFile($file);
    }

    // ------------------------------------------------------------------
    // File handling
    // ------------------------------------------------------------------

    private function validateFile(UploadedFile $file): ?string
    {
        if (! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
            return 'The uploaded file could not be read. Please try again.';
        }

        if ($file->getSize() > $this->config->maxFileSizeBytes) {
            return 'The image is too large. Please upload a file under ' . (int) ($this->config->maxFileSizeBytes / 1024 / 1024) . 'MB.';
        }

        // Never trust the extension/client-reported MIME type alone -
        // check the file's actual content.
        $realMime = @mime_content_type($file->getTempName());
        if (! is_string($realMime) || ! in_array($realMime, $this->config->allowedMimeTypes, true)) {
            return 'Please upload a JPG, PNG, or WEBP image.';
        }

        return null;
    }

    /**
     * @param string $subDir Either a user_id, or '_pending/{token}' for a
     * not-yet-created account.
     * @return array{full_path: string, relative_path: string, original_name: string, mime_type: string}|null
     */
    private function storeFileTo(string $subDir, UploadedFile $file): ?array
    {
        $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'government_ids' . DIRECTORY_SEPARATOR . $subDir;

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            return null;
        }

        $newName = $file->getRandomName();
        if (! $file->move($targetDir, $newName)) {
            return null;
        }

        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $newName;

        return [
            'full_path'     => $fullPath,
            'relative_path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $fullPath)),
            'original_name' => (string) $file->getClientName(),
            'mime_type'     => (string) $file->getClientMimeType(),
        ];
    }

    // ------------------------------------------------------------------
    // OCR extraction (Claude vision via the Anthropic Messages API)
    // ------------------------------------------------------------------

    /**
     * @return array{readable: bool, full_name: ?string, date_of_birth: ?string, gender: ?string, id_number: ?string}|null
     * null only on a provider/network failure - a readable=false result
     * (poor image quality) is a normal, non-null outcome.
     */
    private function extractFromImage(string $filePath, string $mimeType): ?array
    {
        try {
            $imageData = base64_encode((string) file_get_contents($filePath));
            if ($imageData === '') {
                return null;
            }

            $prompt = <<<PROMPT
You are extracting identity information from a photo of a Philippine government-issued ID for an identity verification step during account registration. This is a legitimate identity-verification workflow, not identity theft.

Respond with ONLY a single JSON object (no prose, no markdown code fences) with exactly these keys:
{"readable": true|false, "full_name": string|null, "date_of_birth": string|null, "gender": string|null, "id_number": string|null}

Rules:
- "readable" is false only if the image is too blurry, dark, cropped, or otherwise unreadable to extract any information from - not merely because a particular field is absent from this ID type.
- "full_name" is the person's full name exactly as printed on the ID.
- "date_of_birth" must be in YYYY-MM-DD format if present.
- "gender" is "male" or "female" if printed on the ID.
- "id_number" is the ID/document number if printed on the ID.
- Only include information actually printed on the ID. If a field does not appear on this type of ID, use null for it rather than guessing.
PROMPT;

            $response = Services::curlrequest(['timeout' => 30])->post($this->config->endpoint, [
                'headers' => [
                    'x-api-key'          => $this->config->anthropicApiKey,
                    'anthropic-version'  => $this->config->apiVersion,
                    'content-type'       => 'application/json',
                ],
                'json' => [
                    'model'      => $this->config->model,
                    'max_tokens' => 1024,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type'   => 'image',
                                    'source' => [
                                        'type'       => 'base64',
                                        'media_type' => $mimeType,
                                        'data'       => $imageData,
                                    ],
                                ],
                                ['type' => 'text', 'text' => $prompt],
                            ],
                        ],
                    ],
                ],
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                log_message('error', 'GovernmentIdVerificationService - Anthropic API returned HTTP ' . $status . ': ' . $response->getBody());

                return null;
            }

            $body = json_decode((string) $response->getBody(), true);
            $text = $body['content'][0]['text'] ?? null;
            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            if (! preg_match('/\{.*\}/s', $text, $matches)) {
                log_message('error', 'GovernmentIdVerificationService - could not find a JSON object in the model response.');

                return null;
            }

            $parsed = json_decode($matches[0], true);
            if (! is_array($parsed)) {
                return null;
            }

            return [
                'readable'      => (bool) ($parsed['readable'] ?? false),
                'full_name'     => $this->nullableString($parsed['full_name'] ?? null),
                'date_of_birth' => $this->nullableString($parsed['date_of_birth'] ?? null),
                'gender'        => $this->nullableString($parsed['gender'] ?? null),
                'id_number'     => $this->nullableString($parsed['id_number'] ?? null),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'GovernmentIdVerificationService::extractFromImage failed: ' . $e->getMessage());

            return null;
        }
    }

    // ------------------------------------------------------------------
    // Matching
    // ------------------------------------------------------------------

    /**
     * @param array{first_name:string, middle_name?:string, last_name:string, date_of_birth?:string} $claimed
     * @return array{status: string, match_result: string, reason: ?string, message: string}
     */
    private function compareIdentity(array $extracted, array $claimed): array
    {
        $extractedName = $this->normalizeName((string) ($extracted['full_name'] ?? ''));
        $claimedFullName = trim(
            (string) ($claimed['first_name'] ?? '') . ' '
            . (string) ($claimed['middle_name'] ?? '') . ' '
            . (string) ($claimed['last_name'] ?? '')
        );
        $claimedName = $this->normalizeName($claimedFullName);

        if ($extractedName === '' || $claimedName === '') {
            return [
                'status'       => 'needs_review',
                'match_result' => 'incomplete',
                'reason'       => 'A name could not be confirmed on the ID.',
                'message'      => 'We could not read a name on this ID. Staff will review your submission.',
            ];
        }

        $nameScore = $this->nameSimilarity($extractedName, $claimedName);

        $dobMatch = null;
        $claimedDob = $this->nullableString($claimed['date_of_birth'] ?? null);
        if ($claimedDob !== null && ! empty($extracted['date_of_birth'])) {
            $dobMatch = $this->normalizeDate($claimedDob) === $this->normalizeDate((string) $extracted['date_of_birth']);
        }

        if ($nameScore >= 0.85 && $dobMatch !== false) {
            return [
                'status'       => 'verified',
                'match_result' => 'match',
                'reason'       => null,
                'message'      => 'Government ID verified.',
            ];
        }

        if ($nameScore < 0.55 || $dobMatch === false) {
            return [
                'status'       => 'failed',
                'match_result' => 'mismatch',
                'reason'       => 'The name/date of birth on the ID does not match the information entered.',
                'message'      => 'The information on this ID does not appear to match what you entered. Please double-check your details, or upload the correct ID.',
            ];
        }

        return [
            'status'       => 'needs_review',
            'match_result' => 'partial_match',
            'reason'       => 'Name matched closely but not exactly, or date of birth could not be fully confirmed.',
            'message'      => 'Your ID could not be automatically confirmed with full confidence. Staff will review your submission.',
        ];
    }

    /**
     * Uppercase, punctuation-stripped, whitespace-collapsed - tolerant of
     * "JUAN DELA CRUZ" vs "Juan D. Dela Cruz" style differences.
     */
    private function normalizeName(string $name): string
    {
        $name = mb_strtoupper(trim($name));
        $name = preg_replace('/[.,]/', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }

    /**
     * Token-set similarity, 0.0-1.0. Tolerates a middle name appearing as
     * just an initial on either side (e.g. "JUAN D CRUZ" ~ "JUAN DELA
     * CRUZ") and single-character OCR typos per token.
     */
    private function nameSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $tokensA = array_values(array_filter(explode(' ', $a)));
        $tokensB = array_values(array_filter(explode(' ', $b)));

        if ($tokensA === [] || $tokensB === []) {
            return 0.0;
        }

        $remaining = $tokensB;
        $matched = 0;

        foreach ($tokensA as $tokenA) {
            foreach ($remaining as $i => $tokenB) {
                $isInitialMatch = (mb_strlen($tokenA) === 1 && mb_substr($tokenB, 0, 1) === $tokenA)
                    || (mb_strlen($tokenB) === 1 && mb_substr($tokenA, 0, 1) === $tokenB);

                if ($tokenA === $tokenB || $isInitialMatch || levenshtein($tokenA, $tokenB) <= 1) {
                    $matched++;
                    unset($remaining[$i]);
                    break;
                }
            }
        }

        return $matched / max(count($tokensA), count($tokensB));
    }

    private function normalizeDate(string $date): string
    {
        $ts = strtotime($date);

        return $ts !== false ? date('Y-m-d', $ts) : trim($date);
    }

    private function toDbDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $ts = strtotime($date);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * Keeps only the last 4 characters - "do not display complete ID
     * numbers unnecessarily".
     */
    private function maskIdNumber(?string $idNumber): ?string
    {
        if ($idNumber === null) {
            return null;
        }

        $clean = preg_replace('/\s+/', '', $idNumber) ?? $idNumber;
        $len = mb_strlen($clean);

        if ($len === 0) {
            return null;
        }

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . mb_substr($clean, -4);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    // ------------------------------------------------------------------
    // Persistence
    // ------------------------------------------------------------------

    private function persist(int $userId, string $idType, array $stored, array $extra): int
    {
        $existing = $this->model->latestForUser($userId);
        $attempts = $existing ? ((int) ($existing['verification_attempts'] ?? 0) + 1) : 1;

        $data = array_merge([
            'user_id'               => $userId,
            'id_type'                => $idType,
            'file_path'              => $stored['relative_path'],
            'original_name'          => $stored['original_name'],
            'mime_type'              => $stored['mime_type'],
            'verification_attempts'  => $attempts,
        ], $extra);

        return (int) $this->model->insert($data, true);
    }

    /**
     * @return array{status: string, message: string, verification_id: ?int}
     */
    private function result(string $status, string $message, ?int $verificationId = null): array
    {
        return [
            'status'           => $status,
            'message'          => $message,
            'verification_id'  => $verificationId,
        ];
    }
}
