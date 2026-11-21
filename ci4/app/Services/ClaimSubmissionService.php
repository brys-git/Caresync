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
        // The claim is for the package of the client's OWN plan (not a fixed package).
        $ownPackage = (new ClaimAvailService())->planPackageId((new ClaimAvailService())->planFor($planHolderId) ?? []);

        return db_connect()->table('service_applications sa')
            ->select('sa.application_id, sa.status, sa.created_at, sa.awaiting_death_certificate, p.package_name')
            ->join('packages p', 'p.package_id = sa.package_id', 'inner')
            ->where('sa.plan_holder_id', $planHolderId)
            ->where('sa.package_id', $ownPackage)
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

    /**
     * "Who died?" choices: the plan holder (only when someone files at the branch - the plan holder's own login can't file
     * for their own death) and every registered beneficiary who is not already deceased.
     *
     * @return list<array{id:string, label:string, relationship:string, kind:string}>
     */
    public function deceasedOptions(int $planHolderId, bool $onBehalf): array
    {
        $db = db_connect();
        $out = [];
        if ($onBehalf) {
            $u = $db->table('plan_holders ph')->select('u.first_name, u.middle_name, u.last_name')->join('users u', 'u.user_id = ph.user_id', 'inner')->where('ph.plan_holder_id', $planHolderId)->get(1)->getRowArray() ?: [];
            $out[] = ['id' => 'holder', 'label' => trim(implode(' ', array_filter([$u['first_name'] ?? '', $u['middle_name'] ?? '', $u['last_name'] ?? '']))) . ' (the plan holder)', 'relationship' => 'Plan holder', 'kind' => 'holder'];
        }
        foreach ($this->livingBeneficiaries($planHolderId) as $b) {
            $out[] = ['id' => (string) (int) $b['beneficiary_id'], 'label' => $this->beneficiaryName($b) . ' (registered beneficiary)', 'relationship' => (string) ($b['relationship'] ?? ''), 'kind' => 'beneficiary'];
        }

        return $out;
    }

    /**
     * Who may be the claimant when someone files at the branch: the plan holder (when a beneficiary died), a registered
     * beneficiary or the emergency contact (when the plan holder died).
     *
     * @return list<array{id:string, label:string, relationship:string, kind:string}>
     */
    public function claimantOptions(int $planHolderId): array
    {
        $db = db_connect();
        $holder = $db->table('plan_holders ph')->select('ph.emergency_contact_name, ph.emergency_contact_relationship, u.first_name, u.middle_name, u.last_name')->join('users u', 'u.user_id = ph.user_id', 'inner')->where('ph.plan_holder_id', $planHolderId)->get(1)->getRowArray() ?: [];
        $out = [['id' => 'holder', 'label' => trim(implode(' ', array_filter([$holder['first_name'] ?? '', $holder['middle_name'] ?? '', $holder['last_name'] ?? '']))) . ' (the plan holder)', 'relationship' => 'Plan holder', 'kind' => 'holder']];
        foreach ($this->livingBeneficiaries($planHolderId) as $b) {
            $out[] = ['id' => (string) (int) $b['beneficiary_id'], 'label' => $this->beneficiaryName($b) . ' (registered beneficiary)', 'relationship' => (string) ($b['relationship'] ?? ''), 'kind' => 'beneficiary'];
        }
        if (trim((string) ($holder['emergency_contact_name'] ?? '')) !== '') {
            $out[] = ['id' => 'emergency', 'label' => trim((string) $holder['emergency_contact_name']) . ' (emergency contact)', 'relationship' => (string) ($holder['emergency_contact_relationship'] ?? ''), 'kind' => 'emergency'];
        }

        return $out;
    }

    /** @return list<array<string,mixed>> beneficiaries of the plan holder who have not died */
    private function livingBeneficiaries(int $planHolderId): array
    {
        $db = db_connect();
        $b = $db->table('beneficiaries')->where('plan_holder_id', $planHolderId);
        if ($db->fieldExists('status', 'beneficiaries')) {
            $b->where('status', 'active');
        }

        return $b->orderBy('is_primary', 'DESC')->orderBy('beneficiary_id', 'ASC')->get()->getResultArray();
    }

    private function beneficiaryName(array $b): string
    {
        return trim(implode(' ', array_filter([$b['first_name'] ?? '', $b['middle_name'] ?? '', $b['last_name'] ?? '', $b['name_extension'] ?? ''])));
    }

    /** The package of the plan holder's own plan (the one a claim releases), or null. */
    public function entitlementPackage(int $planHolderId = 0): ?array
    {
        $claimAvail = new ClaimAvailService();
        $packageId  = $claimAvail->planPackageId($planHolderId > 0 ? ($claimAvail->planFor($planHolderId) ?? []) : []);
        if ($packageId <= 0) {
            return null;
        }

        return db_connect()->table('packages')
            ->select('package_id, package_name, base_price, description, image_path, is_damayan_entitlement')
            ->where('package_id', $packageId)
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

        $package = $db->table('packages')
            ->select('package_id, package_name, base_price, is_damayan_entitlement')
            ->where('package_id', $packageId)
            ->get()
            ->getRowArray();
        if (! $package) {
            return ['ok' => false, 'error' => 'Selected package is unavailable.'];
        }

        // The one rule set for claims and avails (ClaimAvailService): 2 paid months in the current
        // cycle, no unsettled balance, one open request at a time. Checked again here on the server.
        $claimAvail = new ClaimAvailService();
        $el = $claimAvail->eligibility($planHolderId, $packageId);
        if (! $el['allowed']) {
            $msg = (string) $el['message'];
            if ($onBehalf) {
                $msg = strtr($msg, ['You have paid' => 'They have paid', 'You need' => 'The plan holder needs', 'You already have' => 'The plan holder already has', 'You can request' => 'The plan holder can request']);
            }

            return ['ok' => false, 'error' => $msg, 'redirect_open_claim' => $el['code'] === 'open_request'];
        }

        $isEntitlement = $claimAvail->requestType($claimAvail->planFor($planHolderId) ?? [], $packageId) === ClaimAvailService::TYPE_CLAIM;

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

        // ---- Who died? (policy: the plan holder, or a registered beneficiary who is still living - picked from the list)
        $policyOn = PlanClosureService::ready();
        $deceasedType = null;
        $deceasedBeneficiaryId = null;
        if ($policyOn) {
            $choice = $text('deceased_choice');
            if ($choice === 'holder') {
                $deceasedType = 'plan_holder';
                $hu = $db->table('users')->select('first_name, middle_name, last_name')->where('user_id', (int) $holder['user_id'])->get()->getRowArray() ?: [];
                $input['deceased_name'] = trim(implode(' ', array_filter([$hu['first_name'] ?? '', $hu['middle_name'] ?? '', $hu['last_name'] ?? ''])));
                if (! $onBehalf) {
                    $errors['deceased_choice'] = 'The plan holder cannot file a claim for their own death. A registered beneficiary or the emergency contact files it at the branch.';
                }
            } elseif (ctype_digit($choice) && (int) $choice > 0) {
                $bq = $db->table('beneficiaries')->where('beneficiary_id', (int) $choice)->where('plan_holder_id', $planHolderId);
                $dead = $bq->get(1)->getRowArray();
                if ($dead === null) {
                    $errors['deceased_choice'] = 'Choose who died from the registered beneficiaries of this plan.';
                } elseif ((string) ($dead['status'] ?? 'active') === 'deceased') {
                    $errors['deceased_choice'] = 'This beneficiary is already recorded as deceased. A claim was already made for them.';
                } else {
                    $deceasedType = 'beneficiary';
                    $deceasedBeneficiaryId = (int) $dead['beneficiary_id'];
                    $input['deceased_name'] = $this->beneficiaryName($dead);
                }
            } else {
                $errors['deceased_choice'] = 'Choose who died: the plan holder or one of the registered beneficiaries.';
            }
        }

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
        $claimantBeneficiaryId = null;
        $claimantType = $policyOn ? 'plan_holder' : null;
        if ($onBehalf) {
            // The claimant is the plan holder or one of the plan holder's REGISTERED beneficiaries,
            // picked from the list - never free text. The name comes from our own records.
            $choice = $text('claimant_choice');
            // Policy: when the plan holder died, a registered beneficiary or the emergency contact claims; when a
            // beneficiary died, the plan holder claims.
            if ($policyOn && $deceasedType === 'plan_holder' && $choice === 'holder') {
                $errors['claimant_choice'] = 'The plan holder died, so the claim is made by a registered beneficiary or the emergency contact.';
            } elseif ($policyOn && $deceasedType === 'beneficiary' && $choice !== 'holder') {
                $errors['claimant_choice'] = 'A beneficiary died, so the claim is made by the plan holder.';
            } elseif ($choice === 'emergency') {
                $ec = $db->table('plan_holders')->select('emergency_contact_name, emergency_contact_relationship')->where('plan_holder_id', $planHolderId)->get(1)->getRowArray() ?: [];
                if (trim((string) ($ec['emergency_contact_name'] ?? '')) === '') {
                    $errors['claimant_choice'] = 'No emergency contact is registered on this plan.';
                } else {
                    $claimantType = 'emergency_contact';
                    $claimantName = trim((string) $ec['emergency_contact_name']);
                    $claimantRelationship = $claimantRelationship !== '' ? $claimantRelationship : (string) ($ec['emergency_contact_relationship'] ?? '');
                }
            } elseif ($choice === 'holder') {
                $holderUser = $db->table('users')->select('first_name, middle_name, last_name')->where('user_id', (int) $holder['user_id'])->get()->getRowArray() ?: [];
                $claimantName = trim(implode(' ', array_filter([$holderUser['first_name'] ?? '', $holderUser['middle_name'] ?? '', $holderUser['last_name'] ?? ''])));
                $claimantRelationship = $claimantRelationship !== '' ? $claimantRelationship : 'Plan holder';
            } elseif (ctype_digit($choice) && (int) $choice > 0) {
                $ben = $db->table('beneficiaries')->where('beneficiary_id', (int) $choice)->where('plan_holder_id', $planHolderId)->get()->getRowArray();
                if ($ben === null) {
                    $errors['claimant_choice'] = 'Choose the claimant from the plan holder\'s registered beneficiaries.';
                } elseif ($policyOn && (string) ($ben['status'] ?? 'active') === 'deceased') {
                    $errors['claimant_choice'] = 'A beneficiary who has died cannot make the claim.';
                } else {
                    $claimantType = 'beneficiary';
                    $claimantBeneficiaryId = (int) $ben['beneficiary_id'];
                    $claimantName = trim(implode(' ', array_filter([$ben['first_name'], $ben['middle_name'], $ben['last_name'], $ben['name_extension']])));
                    $claimantRelationship = $claimantRelationship !== '' ? $claimantRelationship : (string) ($ben['relationship'] ?? '');
                }
            } else {
                $errors['claimant_choice'] = 'Choose who is making the claim: the plan holder or a registered beneficiary.';
            }
            if ($claimantRelationship === '') {
                $errors['claimant_relationship'] = 'Enter the claimant\'s relationship to the plan holder.';
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
                : ($onBehalf ? 'Upload a valid ID of the person making the claim.' : 'Upload a valid ID of the beneficiary.');
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

        // What is added to the client's balance, by the same rule the approval uses: a claim adds only
        // paid add-ons; an avail adds the package price (and add-ons). There is no "benefit credit".
        $priced = $claimAvail->priceItem($planHolderId, ['package_id' => $packageId, 'attire' => $burialAttireSelected]);
        $damayanBenefitApplied = 0.0;
        $applicationAmount = (float) ($priced['charge'] ?? 0.0);

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
                'request_type' => $isEntitlement ? ClaimAvailService::TYPE_CLAIM : ClaimAvailService::TYPE_AVAIL,
                'rule_version' => ClaimAvailService::RULE_VERSION,
                'awaiting_death_certificate' => $deathCertificate === null ? 1 : 0,
            ];

            if ($policyOn) {
                $insert['deceased_type'] = $deceasedType;
                $insert['deceased_beneficiary_id'] = $deceasedBeneficiaryId;
                $insert['claimant_type'] = $claimantType;
            }
            if ($onBehalf && $db->fieldExists('filed_on_behalf', 'service_applications')) {
                $insert['filed_by_user_id'] = $actorUserId;
                $insert['filed_on_behalf'] = 1;
                $insert['claimant_name'] = $claimantName;
                $insert['claimant_relationship'] = $claimantRelationship;
                if ($claimantBeneficiaryId !== null && $db->fieldExists('claimant_beneficiary_id', 'service_applications')) {
                    $insert['claimant_beneficiary_id'] = $claimantBeneficiaryId;
                }
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
                ? 'A ' . ($isEntitlement ? 'claim for ' : 'request for ') . $label . ' was filed for you at the branch by ' . ($filedBy['actor_name'] ?? '') . ' (' . ($filedBy['actor_role'] ?? 'branch') . ') for ' . $claimantName . ' (' . $claimantRelationship . ').'
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
