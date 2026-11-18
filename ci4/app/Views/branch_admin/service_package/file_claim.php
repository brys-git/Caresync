<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$e = static fn ($v): string => esc((string) ($v ?? ''));
$statusLabels = ['pending' => 'under review', 'approved' => 'approved'];
?>
<link rel="stylesheet" href="<?= cs_asset_url('assets/css/burial-request.css') ?>">

<div class="br-page">
    <a class="br-back" href="<?= base_url($file_claim_path) ?>">← Choose another plan holder</a>
    <header class="br-head">
        <h1 class="br-title">File a claim for <?= $e($member_name) ?></h1>
        <p class="br-sub">You are filing this on behalf of the plan holder (<?= $e($holder['unique_identifier'] ?? '') ?>). They will be told it was filed, and your name is recorded on the claim.</p>
    </header>

    <?php if ($existing_claim): ?>
        <section class="br-card br-status">
            <h2 class="br-card__title">A claim is already <?= $e($statusLabels[$existing_claim['status']] ?? $existing_claim['status']) ?></h2>
            <p class="br-muted">Reference CLM-<?= str_pad((string) (int) $existing_claim['application_id'], 6, '0', STR_PAD_LEFT) ?> · Submitted <?= $e(date('j M Y', strtotime((string) $existing_claim['created_at']))) ?></p>
            <a class="br-btn br-btn--ghost" href="<?= base_url($requests_path . '/' . (int) $existing_claim['application_id']) ?>">Open the request</a>
        </section>
    <?php elseif (! $eligibility['eligible']): ?>
        <section class="br-notice">
            <h2 class="br-notice__title">A claim can't be filed right now</h2>
            <p class="br-body"><?= $e($eligibility['reason'] ?? 'This plan does not allow a claim at the moment.') ?></p>
            <a class="br-btn br-btn--ghost" href="<?= base_url($holder_path . '/' . (int) $holder['plan_holder_id']) ?>">Back to the plan holder</a>
        </section>
    <?php else: ?>
        <?= view('partials/burial_request_form', [
            'mode' => 'claim',
            'package' => $package,
            'base_price' => (float) ($package['base_price'] ?? 0),
            'form_quote' => $form_quote,
            'claimant_options' => $claimant_options,
            'attire_price' => (float) $attire_price,
            'plan_name' => (string) $plan_name,
            'member_name' => (string) $member_name,
            'member_address' => (string) $member_address,
            'beneficiary_prefill' => (string) $beneficiary_prefill,
            'branch_name' => (string) $branch_name,
            'branch_phone' => (string) $branch_phone,
            'form_errors' => is_array($form_errors) ? $form_errors : [],
            'form_action' => site_url($file_claim_path . '/' . (int) $holder['plan_holder_id']),
            'on_behalf' => true,
        ]) ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
