<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Filing a casket claim / package request: the eligibility rules, the input
 * validation, the document upload and the database writes, in ONE place.
 *
 * The plan holder (Client\ClientServiceController) and the Branch Admin
 * filing on the plan holder's behalf (BranchAdmin\ClaimController) both go
 * through here, so the rules can never drift apart. The only difference is
 * who is recorded as having filed it ($filedBy).
 */
class ClaimSubmissionService
{
    /** Flat add-on price for the optional burial attire. */
    public const BURIAL_ATTIRE_PRICE = 1500.0;

    /** Months paid needed before anything can be claimed or availed. */
    public const MIN_MONTHS_PAID = 2;

    /**
     * The plan's live standing: whether it may apply at all, the membership
     * summary and the active plan id.
     *
     * @return array{0: bool, 1: array|null, 2: int} [canApply, membership, planId]
     */
    public function eligibility(int $planHolderId): array
    {
        $membershipService = new MembershipService();
        $activePlan = $planHolderId > 0 ? $membershipService->getActivePlan($planHolderId) : null;
        $monthsPaid = (int) ($activePlan['months_paid'] ?? 0);
        $planId = (int) ($activePlan['plan_id'] ?? 0);

        $membership = $planHolderId > 0 ? $membershipService->getMembershipSummary($planHolderId) : null;

        if (is_array($membership) && $membership !== []) {
            $monthsPaid = (int) ($membership['months_paid'] ?? $monthsPaid);
            $canApply = (! empty($membership['can_access_services'])) && $monthsPaid >= self::MIN_MONTHS_PAID;
        } else {
            $canApply = $activePlan !== null && $monthsPaid >= self::MIN_MONTHS_PAID;
        }

        return [$canApply, $membership, $planId];
    }

    /**
     * The ONE CLAIM-eligibility check for the entitled package: 2+ verified
     * months in the current cycle and not already claimed in this cycle.
     *
     * @return array{eligible: bool, reason: ?string}
     */
    public function entitlementClaimStatus(bool $canApply, ?array $membership, int $planId): array
    {
        if (! $canApply) {
            $monthsPaid = (int) ($membership['months_paid'] ?? 0);
            $canAccessServices = is_array($membership) && ! empty($membership['can_access_services']);

            if ($canAccessServices && $monthsPaid < self::MIN_MONTHS_PAID) {
                return [
                    'eligible' => false,
                    'reason' => "You need at least 2 paid months to claim your entitled package or avail services. You have paid {$monthsPaid} month(s).",
                ];
            }

            if (is_array($membership) && ! empty($membership['service_block_reason'])) {
                return ['eligible' => false, 'reason' => (string) $membership['service_block_reason']];
            }

            return ['eligible' => false, 'reason' => 'Become an active, eligible Plan Holder to claim this entitlement.'];
        }

        if ($planId <= 0) {
            return ['eligible' => false, 'reason' => 'Become an active, eligible Plan Holder to claim this entitlement.'];
        }

        $cycle = (new CycleService())->currentCycle($planId);
        if ($cycle['entitlement_claimed']) {
            $remaining = number_format((float) $cycle['remaining'], 2);

            return [
                'eligible' => false,
                'reason' => "You've already claimed your entitled package for this cycle. You have ₱{$remaining} remaining on your current contribution. Once it's fully paid, a new cycle begins and you can claim again after 2 months.",
            ];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * The member's claim that is still open: one awaiting review, or one already
     * approved for the current cycle. Stops the same casket being filed twice.
     */
    public function openClaim(int $planHolderId, int $planId): ?array
    {
        $cycleNumber = $planId > 0 ? (new CycleService())->currentCycle($planId)['cycle_number'] : 0;

        return db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.status, sa.created_at, sa.awaiting_death_certificate, p.package_name')
            ->join('packages p', 'p.package_id = sa.package_id', 'inner')
            ->where('sa.plan_holder_id', $planHolderId)
            ->where('p.is_damayan_entitlement', 1)
            ->groupStart()
                ->where('sa.status', 'pending')
                ->orGroupStart()
                    ->where('sa.status', 'approved')
                    ->where('sa.cycle_number', $cycleNumber)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('sa.application_id', 'DESC')
            ->get(1)
            ->getRowArray() ?: null;
    }

    /** The Damayan entitlement (Regular casket) package, or null. */
    public function entitlementPackage(): ?array
    {
        return db_connect()->table('packages')
            ->select('package_id, package_name, base_price, description, image_path, is_damayan_entitlement')
            ->where('is_damayan_entitlement', 1)
            ->orderBy('package_id', 'ASC')
            ->get()
            ->getRowArray() ?: null;
    }

    /**
     * Files the claim / request. Checks everything again here (never trusts a
     * button being hidden), validates, stores the documents, notifies.
     *
     * @param array<string,mixed>      $post   the submitted fields
     * @param array<string,mixed>      $files  from $request->getFiles()
     * @param array{on_behalf?:bool,actor_name?:string,actor_role?:string}|null $filedBy
     *        null = the plan holder filed it themselves. For a Branch Admin:
     *        ['on_behalf' => true, 'actor_name' => ..., 'actor_role' => 'Branch Admin']
     *
     * @return array{ok:bool, application_id?:int, error?:string, errors?:array<string,string>, redirect_open_claim?:bool}
     */
    public function submit(int $planHolderId, int $packageId, array $post, array $files, int $actorUserId, ?array $filedBy = null): array
    {
        $db = db_connect();
        $onBehalf = (bool) ($filedBy['on_behalf'] ?? false);

        $holder = $db->table('plan_holders')->select('plan_holder_id, user_id, branch_id')->where('plan_holder_id', $planHolderId)->get()->getRowArray();
        if (! $holder) {
            return ['ok' => false, 'error' => 'No plan holder profile found.'];
        }

        $who = $onBehalf ? 'This plan holder' : 'You';

        [$canApply, $membership, $planId] = $this->eligibility($planHolderId);

        $monthsPaid = (int) ($membership['months_paid'] ?? 0);
        if ($monthsPaid < self::MIN_MONTHS_PAID) {
            return ['ok' => false, 'error' => $who . ' must complete at least 2 months of payments before requesting services.'];
        }

        $serviceAccess = (new MembershipService())->serviceAccess($planHolderId);
        if (! $serviceAccess['allowed']) {
            return ['ok' => false, 'error' => $serviceAccess['reason'] ?: 'This plan does not allow service requests at this time.'];
        }

        $package = $db->table('packages')
            ->select('package_id, package_name, base_price, is_damayan_entitlement')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();
        if (! $package) {
            return ['ok' => false, 'error' => 'Selected package is unavailable.'];
        }

        $isEntitlement = (int) ($package['is_damayan_entitlement'] ?? 0) === 1;

        if ($isEntitlement) {
            $claimStatus = $this->entitlementClaimStatus($canApply, $membership, $planId);
            if (! $claimStatus['eligible']) {
                return ['ok' => false, 'error' => (string) $claimStatus['reason']];
            }

            if ($this->openClaim($planHolderId, $planId) !== null) {
                return ['ok' => false, 'error' => 'A claim is already in progress for this entitlement.', 'redirect_open_claim' => true];
            }
        }

        // ---- input -------------------------------------------------------
        $text = static fn (string $key): string => trim((string) ($post[$key] ?? ''));
        $input = [
            'deceased_name' => $text('deceased_name'),
            'deceased_date_of_death' => $text('deceased_date_of_death'),
            'deceased_address' => $text('deceased_address'),
            'relationship_to_deceased' => $text('relationship_to_deceased'),
            'beneficiary_name' => $text('beneficiary_name'),
            'beneficiary_contact' => $text('beneficiary_contact'),
        ];

        $errors = [];
        if ($input['deceased_name'] === '') {
            $errors['deceased_name'] = 'Enter the full name of the deceased.';
        } elseif (mb_strlen($input['deceased_name']) > 150) {
            $errors['deceased_name'] = 'Keep the name under 150 characters.';
        }
        if ($input['deceased_date_of_death'] !== '') {
            $dateOfDeath = strtotime($input['deceased_date_of_death']);
            if ($dateOfDeath === false) {
                $errors['deceased_date_of_death'] = 'Enter a valid date.';
            } elseif (date('Y-m-d', $dateOfDeath) > date('Y-m-d')) {
                $errors['deceased_date_of_death'] = 'The date of death can’t be in the future.';
            }
        }
        if (mb_strlen($input['deceased_address']) > 255) {
            $errors['deceased_address'] = 'Keep the address under 255 characters.';
        }
        if ($input['beneficiary_name'] === '') {
            $errors['beneficiary_name'] = 'Enter the name of the contact person.';
        } elseif (mb_strlen($input['beneficiary_name']) > 150) {
            $errors['beneficiary_name'] = 'Keep the name under 150 characters.';
        }
        if (! preg_match('/^09\d{9}$/', (string) preg_replace('/[\s-]/', '', $input['beneficiary_contact']))) {
            $errors['beneficiary_contact'] = 'Enter an 11-digit mobile number that starts with 09.';
        } else {
            $input['beneficiary_contact'] = preg_replace('/[\s-]/', '', $input['beneficiary_contact']);
        }

        // Who is making the claim (filled in when a Branch Admin files it).
        $claimantName = $text('claimant_name');
        $claimantRelationship = $text('claimant_relationship');
        if ($onBehalf) {
            if ($claimantName === '') {
                $errors['claimant_name'] = 'Enter the name of the person making the claim.';
            } elseif (mb_strlen($claimantName) > 150) {
                $errors['claimant_name'] = 'Keep the name under 150 characters.';
            }
            if ($claimantRelationship === '') {
                $errors['claimant_relationship'] = 'Choose who is making the claim.';
            } elseif (mb_strlen($claimantRelationship) > 100) {
                $errors['claimant_relationship'] = 'Keep this under 100 characters.';
            }
        }

        $uploads = $files['documents'] ?? [];
        $deathCertificate = $this->acceptedUpload($uploads['death_certificate'] ?? null);
        $beneficiaryId = $this->acceptedUpload($uploads['beneficiary_id'] ?? null);
        $otherDocuments = [];
        foreach (is_array($uploads['other'] ?? null) ? $uploads['other'] : [] as $file) {
            if (! $this->hasUpload($file)) {
                continue;
            }
            $accepted = $this->acceptedUpload($file);
            if ($accepted === null) {
                $errors['documents_other'] = 'Other documents must be images or PDFs up to 5 MB each.';
                break;
            }
            $otherDocuments[] = $accepted;
        }

        if ($beneficiaryId === null) {
            $errors['documents_beneficiary_id'] = $this->hasUpload($uploads['beneficiary_id'] ?? null)
                ? 'The ID must be an image or PDF up to 5 MB.'
                : 'Upload a valid ID of the beneficiary.';
        }
        if ($this->hasUpload($uploads['death_certificate'] ?? null) && $deathCertificate === null) {
            $errors['documents_death_certificate'] = 'The death certificate must be an image or PDF up to 5 MB.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        // ---- amounts -----------------------------------------------------
        $burialAttireSelected = ! empty($post['burial_attire']);
        $burialAttirePrice = $burialAttireSelected ? self::BURIAL_ATTIRE_PRICE : null;

        $basePrice = (float) $package['base_price'];
        if ($isEntitlement) {
            // Fully covered - the whole casket price is the benefit itself.
            $damayanBenefitApplied = $basePrice;
            $applicationAmount = $burialAttireSelected ? self::BURIAL_ATTIRE_PRICE : 0.0;
        } else {
            $damayanBenefitApplied = 0.0;
            if (! empty($membership) && (! empty($membership['can_access_services']))) {
                $planRow = $db->table('plans')->where('plan_id', $planId)->get()->getRowArray();
                $damayanBenefitApplied = min($basePrice, PlanLedgerService::targetFor($planRow ?: []));
            }
            $applicationAmount = $basePrice - $damayanBenefitApplied + ($burialAttireSelected ? self::BURIAL_ATTIRE_PRICE : 0.0);
        }

        // ---- save --------------------------------------------------------
        $db->transBegin();

        try {
            $insert = [
                'plan_holder_id' => $planHolderId,
                'package_id' => $packageId,
                'status' => 'pending',
                'deceased_name' => $input['deceased_name'],
                'deceased_date_of_death' => $input['deceased_date_of_death'] !== '' ? $input['deceased_date_of_death'] : null,
                'deceased_address' => $input['deceased_address'] ?: null,
                'relationship_to_deceased' => $input['relationship_to_deceased'] ?: null,
                'beneficiary_name' => $input['beneficiary_name'],
                'beneficiary_contact' => $input['beneficiary_contact'],
                'application_notes' => $text('application_notes') ?: null,
                'burial_attire_selected' => $burialAttireSelected ? 1 : 0,
                'burial_attire_price' => $burialAttirePrice,
                'damayan_benefit_applied' => $damayanBenefitApplied,
                'application_amount' => $applicationAmount,
                'awaiting_death_certificate' => $deathCertificate === null ? 1 : 0,
            ];

            if ($onBehalf && $db->fieldExists('filed_on_behalf', 'service_applications')) {
                $insert['filed_by_user_id'] = $actorUserId;
                $insert['filed_on_behalf'] = 1;
                $insert['claimant_name'] = $claimantName;
                $insert['claimant_relationship'] = $claimantRelationship;
            }

            $db->table('service_applications')->insert($insert);
            $applicationId = (int) $db->insertID();

            $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'service_applications' . DIRECTORY_SEPARATOR . $applicationId;
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $documentsToStore = [];
            if ($deathCertificate !== null) {
                $documentsToStore[] = ['death_certificate', $deathCertificate];
            }
            $documentsToStore[] = ['beneficiary_id', $beneficiaryId];
            foreach ($otherDocuments as $file) {
                $documentsToStore[] = ['other', $file];
            }

            $docModel = new \App\Models\ServiceApplicationDocumentModel();
            foreach ($documentsToStore as [$documentType, $file]) {
                $newName = $file->getRandomName();
                if ($file->move($targetDir, $newName)) {
                    $docModel->insert([
                        'application_id' => $applicationId,
                        'document_type' => $documentType,
                        'filename' => $newName,
                        'original_name' => $file->getClientName(),
                        'mime_type' => $file->getClientMimeType(),
                        'path' => str_replace('\\', '/', str_replace(WRITEPATH, '', $targetDir . DIRECTORY_SEPARATOR . $newName)),
                        'uploaded_by' => $actorUserId,
                    ]);
                }
            }

            $label = (string) $package['package_name'];
            $message = $onBehalf
                ? 'A ' . ($isEntitlement ? 'claim for ' : 'request for ') . $label . ' was filed for you by your ' . ($filedBy['actor_role'] ?? 'branch') . ', ' . ($filedBy['actor_name'] ?? '') . '.'
                : ($isEntitlement ? 'Your claim for ' : 'Your application for ') . $label . ' has been submitted.';

            (new NotificationService())->notify((int) $holder['user_id'], $message, 'registration_pending');

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to submit application.');
            }

            $db->transCommit();

            return ['ok' => true, 'application_id' => $applicationId];
        } catch (\Throwable $e) {
            $db->transRollback();

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function hasUpload(mixed $file): bool
    {
        return $file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }

    public function acceptedUpload(mixed $file): ?UploadedFile
    {
        if (! $file instanceof UploadedFile || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if ($file->getSize() > 5 * 1024 * 1024 || ! in_array($file->getMimeType(), $allowed, true)) {
            return null;
        }

        return $file;
    }
}
