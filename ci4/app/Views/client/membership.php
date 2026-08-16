<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'unregistered');
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$user = $access['user'] ?? [];
$beneficiaries = $beneficiaries ?? [];
$summary = is_array($membership_summary) ? $membership_summary : [];
$planRow = is_array($plan) ? $plan : [];
$branchInfo = is_array($branch_info) ? $branch_info : [];
$planHolder = is_array($plan_holder) ? $plan_holder : [];

$membershipState = strtolower((string) ($summary['membership_state'] ?? ($planRow['membership_state'] ?? 'active')));
$overdueMonths = (int) ($summary['overdue_months'] ?? ($planRow['overdue_months'] ?? 0));
$monthsPaid = (int) ($summary['months_paid'] ?? ($planRow['months_paid'] ?? 0));
$monthlyFee = (float) ($summary['monthly_fee'] ?? ($planRow['monthly_fee'] ?? ($program['monthly_fee'] ?? 240)));
$totalPlan = (float) ($summary['total_plan_amount'] ?? \App\Services\MembershipService::TOTAL_CONTRIBUTION);
$paidAmount = (float) ($summary['paid_amount'] ?? 0);
$remaining = (float) ($summary['remaining_balance'] ?? max(0, $totalPlan - $paidAmount));
$progress = $totalPlan > 0 ? min(100, max(0, (int) round(($paidAmount / $totalPlan) * 100))) : 0;
$memberSince = (string) ($summary['start_date'] ?? ($planRow['start_date'] ?? ''));
$coverageUntil = (string) ($summary['payment_coverage_until'] ?? ($planRow['payment_coverage_until'] ?? ''));
$nextDue = (string) ($summary['next_due_date'] ?? ($planRow['next_due_date'] ?? ''));
$memberId = (string) ($planHolder['unique_identifier'] ?? 'Not assigned');

$initialPaymentStatus = (string) ($access['initial_payment_status'] ?? 'none');
$fullName = trim((string) (
    ($user['first_name'] ?? '') . ' ' .
    ($user['middle_name'] ?? '') . ' ' .
    ($user['last_name'] ?? '') . ' ' .
    ($user['name_extension'] ?? '')
));

$fmtDate = static function (?string $d): string {
    $d = (string) $d;
    if ($d === '' || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($d);

    return $ts ? date('M d, Y', $ts) : $d;
};

$stateBadge = match ($membershipState) {
    'active'     => ['label' => 'Active',     'color' => '#22a06b'],
    'delinquent' => ['label' => 'Delinquent', 'color' => '#c2760a'],
    'suspended'  => ['label' => 'Suspended',  'color' => '#dc2626'],
    'completed'  => ['label' => 'Completed',  'color' => '#1e3a5f'],
    default      => ['label' => ucfirst($membershipState), 'color' => '#64748b'],
};
$isDelinquent = $membershipState === 'delinquent' || $overdueMonths > 0;
$isSuspended = $membershipState === 'suspended';
$awaitingActivation = $state === 'awaiting_activation';
$cancelledPayment = $initialPaymentStatus === 'cancelled';
?>

<style>
    .mm {
        --mm-navy: #1e3a5f;
        --mm-navy-hover: #162d4a;
        --mm-navy-soft: #e8eef6;
        --mm-gold: #d4a843;
        --mm-gold-soft: #fdf6e3;
        --mm-green: #22a06b;
        --mm-green-soft: #e6f9ee;
        --mm-red: #dc2626;
        --mm-red-soft: #fff1f1;
        --mm-slate: #64748b;
        --mm-slate-soft: #eef1f5;
        --mm-border: #e2e8f0;
        --mm-radius: 16px;
        --mm-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 6px 18px rgba(30,58,95,0.06);
    }
    .mm { color: #1a202c; }
    .mm .mm-card {
        background: #fff;
        border: 1px solid var(--mm-border);
        border-radius: var(--mm-radius);
        box-shadow: var(--mm-shadow);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .mm .mm-card__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 18px 22px 0;
    }
    .mm .mm-card__title {
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mm .mm-card__title .mdi { color: var(--mm-navy); }
    .mm .mm-card__body { padding: 18px 22px 22px; }

    /* Hero */
    .mm-hero {
        background: linear-gradient(120deg, var(--mm-navy) 0%, #24476f 55%, #2f5885 100%);
        color: #fff;
        border-radius: var(--mm-radius);
        padding: 26px 28px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    .mm-hero::after {
        content: "\F0072";
        font-family: "Material Design Icons";
        position: absolute;
        right: -16px;
        bottom: -30px;
        font-size: 9rem;
        opacity: 0.08;
    }
    .mm-hero__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .mm-hero__eyebrow {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        opacity: 0.75;
        margin-bottom: 6px;
    }
    .mm-hero__name { font-size: 1.5rem; font-weight: 800; margin: 0 0 4px; }
    .mm-hero__meta { font-size: 0.84rem; opacity: 0.85; }
    .mm-hero__right { text-align: right; }
    .mm-hero__pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.78rem;
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 999px;
        color: #fff;
        background: rgba(255,255,255,0.16);
        border: 1px solid rgba(255,255,255,0.28);
    }
    .mm-hero__dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .mm-hero__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-top: 20px;
    }
    .mm-hero__cell { background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.16); border-radius: 12px; padding: 12px 14px; }
    .mm-hero__cell-label { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.7; }
    .mm-hero__cell-value { font-size: 0.94rem; font-weight: 800; margin-top: 3px; }

    /* Stat cards */
    .mm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    .mm-stat {
        background: #fff;
        border: 1px solid var(--mm-border);
        border-top: 3px solid var(--mm-navy);
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: var(--mm-shadow);
    }
    .mm-stat--green { border-top-color: var(--mm-green); }
    .mm-stat--gold { border-top-color: var(--mm-gold); }
    .mm-stat--red { border-top-color: var(--mm-red); }
    .mm-stat__label {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--mm-slate);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .mm-stat__label .mdi { font-size: 0.95rem; }
    .mm-stat__value { font-size: 1.3rem; font-weight: 800; margin-top: 5px; }

    /* Progress */
    .mm-progress-track {
        height: 12px;
        background: var(--mm-slate-soft);
        border-radius: 999px;
        overflow: hidden;
        margin: 10px 0 6px;
    }
    .mm-progress-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--mm-gold), var(--mm-green));
        transition: width 0.3s;
    }
    .mm-fin-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
    .mm-fin {
        border: 1px solid var(--mm-border);
        border-radius: 14px;
        padding: 16px 18px;
        background: #fff;
        box-shadow: var(--mm-shadow);
    }
    .mm-fin__label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--mm-slate); }
    .mm-fin__value { font-size: 1.25rem; font-weight: 800; margin-top: 5px; }
    .mm-fin--gold .mm-fin__value { color: var(--mm-gold); }
    .mm-fin--green .mm-fin__value { color: var(--mm-green); }
    .mm-fin--navy .mm-fin__value { color: var(--mm-navy); }

    /* Alerts */
    .mm-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border-radius: 12px;
        padding: 13px 16px;
        font-size: 0.86rem;
        font-weight: 600;
        margin-bottom: 18px;
        border: 1.5px solid;
    }
    .mm-alert .mdi { margin-top: 1px; }
    .mm-alert--warn { background: var(--mm-gold-soft); color: var(--mm-gold); border-color: #f3d9a4; }
    .mm-alert--danger { background: var(--mm-red-soft); color: var(--mm-red); border-color: #fecaca; }
    .mm-alert--info { background: var(--mm-navy-soft); color: var(--mm-navy); border-color: #c8d6e8; }

    /* Detail grid */
    .mm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 28px; }
    .mm-row { display: flex; justify-content: space-between; gap: 14px; padding: 11px 0; border-bottom: 1px dashed var(--mm-border); font-size: 0.86rem; }
    .mm-row:last-child { border-bottom: 0; }
    .mm-row dt { color: var(--mm-slate); font-weight: 700; flex: 0 0 46%; }
    .mm-row dd { margin: 0; font-weight: 800; text-align: right; }

    /* Tables */
    .mm-table-wrap { overflow-x: auto; }
    .mm-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .mm-table thead th {
        padding: 11px 14px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--mm-navy);
        background: var(--mm-navy-soft);
        border: 1px solid var(--mm-border);
        text-align: left;
        white-space: nowrap;
    }
    .mm-table tbody td {
        padding: 11px 14px;
        border: 1px solid var(--mm-border);
        vertical-align: middle;
    }
    .mm-table tbody tr:hover td { background: #f8fafc; }
    .mm-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.7rem;
        font-weight: 800;
        padding: 3px 10px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .mm-badge--primary { background: var(--mm-navy-soft); color: var(--mm-navy); }
    .mm-empty {
        text-align: center;
        padding: 28px 16px;
        color: var(--mm-slate);
        font-size: 0.85rem;
    }
    .mm-empty .mdi { display: block; font-size: 1.9rem; color: var(--mm-slate-soft); margin-bottom: 6px; }

    .mm-restricted-wrap { position: relative; }
    .mm-restricted-blur { filter: blur(4px); pointer-events: none; user-select: none; }
    .mm-restricted-modal {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.55);
    }

    @media (max-width: 992px) {
        .mm-stats { grid-template-columns: repeat(2, 1fr); }
        .mm-fin-summary { grid-template-columns: 1fr; }
        .mm-grid { grid-template-columns: 1fr; }
        .mm-hero__right { text-align: left; }
    }
</style>

<div class="mm">
    <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="font-weight:800;">Membership Details</h1>
            <p class="text-muted mb-0">Damayan program membership overview.</p>
        </div>
        <a href="<?= base_url('client/payment') ?>" class="btn btn-sm btn-outline-primary">
            <i class="mdi mdi-receipt"></i> View Payments
        </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="mm-alert mm-alert--danger"><i class="mdi mdi-alert-circle-outline"></i><span><?= esc(session()->getFlashdata('error')) ?></span></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="mm-alert mm-alert--info"><i class="mdi mdi-check-circle-outline"></i><span><?= esc(session()->getFlashdata('success')) ?></span></div>
    <?php endif; ?>

    <?php if ($state === 'unregistered'): ?>
        <div class="mm-restricted-wrap">
            <div class="mm-restricted-blur">
                <div class="mm-card">
                    <div class="mm-card__body" style="padding:24px;">
                        <h5 class="mb-3">Membership Record</h5>
                        <div class="mm-grid">
                            <div class="mm-row"><dt>Program</dt><dd><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></dd></div>
                            <div class="mm-row"><dt>Status</dt><dd>Restricted</dd></div>
                            <div class="mm-row"><dt>Monthly Contribution</dt><dd>₱<?= number_format((float) ($program['monthly_fee'] ?? 240), 2) ?></dd></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mm-restricted-modal">
                <div class="card shadow" style="max-width:420px;">
                    <div class="card-body text-center p-4">
                        <i class="mdi mdi-lock-outline" style="font-size:2.2rem;color:var(--mm-slate);"></i>
                        <h5 class="mt-2 mb-1">Membership not yet registered</h5>
                        <p class="text-muted small mb-3">Complete plan registration to view your membership details.</p>
                        <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register Now</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php if ($awaitingActivation): ?>
            <div class="mm-alert mm-alert--info">
                <i class="mdi mdi-information-outline"></i>
                <span><strong>Registration complete.</strong> Submit your initial payment to activate your membership.</span>
            </div>
        <?php endif; ?>
        <?php if ($cancelledPayment): ?>
            <div class="mm-alert mm-alert--danger">
                <i class="mdi mdi-close-circle-outline"></i>
                <span><strong>Payment was rejected.</strong> Please resubmit your initial payment to continue.</span>
            </div>
        <?php endif; ?>
        <?php if ($isDelinquent): ?>
            <div class="mm-alert mm-alert--warn">
                <i class="mdi mdi-alert-outline"></i>
                <span><strong>Your membership is delinquent.</strong> Please settle your balance to keep your coverage active.</span>
            </div>
        <?php endif; ?>
        <?php if ($isSuspended): ?>
            <div class="mm-alert mm-alert--danger">
                <i class="mdi mdi-pause-circle-outline"></i>
                <span><strong>Your membership is suspended.</strong> Contact your branch office for assistance.</span>
            </div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="mm-hero">
            <div class="mm-hero__top">
                <div>
                    <div class="mm-hero__eyebrow"><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></div>
                    <h2 class="mm-hero__name"><?= esc($fullName !== '' ? $fullName : 'Member') ?></h2>
                    <div class="mm-hero__meta"><?= esc($memberId) ?> &middot; Member since <?= esc($fmtDate($memberSince)) ?></div>
                </div>
                <div class="mm-hero__right">
                    <span class="mm-hero__pill">
                        <span class="mm-hero__dot" style="background:<?= esc($stateBadge['color']) ?>"></span>
                        <?= esc($stateBadge['label']) ?>
                    </span>
                </div>
            </div>
            <div class="mm-hero__grid">
                <div class="mm-hero__cell">
                    <div class="mm-hero__cell-label">Monthly Contribution</div>
                    <div class="mm-hero__cell-value">₱<?= number_format($monthlyFee, 2) ?></div>
                </div>
                <div class="mm-hero__cell">
                    <div class="mm-hero__cell-label">Months Paid</div>
                    <div class="mm-hero__cell-value"><?= $monthsPaid ?> month<?= $monthsPaid === 1 ? '' : 's' ?></div>
                </div>
                <div class="mm-hero__cell">
                    <div class="mm-hero__cell-label">Coverage Until</div>
                    <div class="mm-hero__cell-value"><?= esc($fmtDate($coverageUntil)) ?></div>
                </div>
                <div class="mm-hero__cell">
                    <div class="mm-hero__cell-label">Branch Office</div>
                    <div class="mm-hero__cell-value"><?= esc((string) ($branchInfo['branch_name'] ?? '—')) ?></div>
                </div>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="mm-stats">
            <div class="mm-stat mm-stat--green">
                <div class="mm-stat__label"><i class="mdi mdi-calendar-check"></i> Coverage Until</div>
                <div class="mm-stat__value"><?= esc($fmtDate($coverageUntil)) ?></div>
            </div>
            <div class="mm-stat">
                <div class="mm-stat__label"><i class="mdi mdi-calendar-clock"></i> Next Due Date</div>
                <div class="mm-stat__value"><?= esc($fmtDate($nextDue)) ?></div>
            </div>
            <div class="mm-stat mm-stat--gold">
                <div class="mm-stat__label"><i class="mdi mdi-cash-check"></i> Months Paid</div>
                <div class="mm-stat__value"><?= $monthsPaid ?></div>
            </div>
            <div class="mm-stat <?= $overdueMonths > 0 ? 'mm-stat--red' : '' ?>">
                <div class="mm-stat__label"><i class="mdi mdi-clock-alert-outline"></i> Overdue Months</div>
                <div class="mm-stat__value"><?= $overdueMonths ?></div>
            </div>
        </div>

        <!-- Contribution progress -->
        <div class="mm-card">
            <div class="mm-card__head">
                <h5 class="mm-card__title"><i class="mdi mdi-chart-donut"></i> Contribution Progress</h5>
                <span class="text-muted" style="font-size:0.82rem;font-weight:700;"><?= $progress ?>% paid</span>
            </div>
            <div class="mm-card__body">
                <div class="mm-progress-track"><div class="mm-progress-bar" style="width:<?= $progress ?>%"></div></div>
                <div class="d-flex justify-content-between mt-2" style="font-size:0.78rem;color:var(--mm-slate);font-weight:600;">
                    <span>₱<?= number_format($paidAmount, 2) ?> paid</span>
                    <span>₱<?= number_format($totalPlan, 2) ?> total contribution</span>
                </div>
            </div>
        </div>

        <div class="mm-fin-summary">
            <div class="mm-fin mm-fin--navy">
                <div class="mm-fin__label">Total Contribution</div>
                <div class="mm-fin__value">₱<?= number_format($totalPlan, 2) ?></div>
            </div>
            <div class="mm-fin mm-fin--green">
                <div class="mm-fin__label">Total Paid</div>
                <div class="mm-fin__value">₱<?= number_format($paidAmount, 2) ?></div>
            </div>
            <div class="mm-fin mm-fin--gold">
                <div class="mm-fin__label">Remaining Balance</div>
                <div class="mm-fin__value">₱<?= number_format($remaining, 2) ?></div>
            </div>
        </div>

        <!-- Registration details -->
        <div class="mm-card">
            <div class="mm-card__head">
                <h5 class="mm-card__title"><i class="mdi mdi-account-details"></i> Registration Details</h5>
            </div>
            <div class="mm-card__body">
                <div class="mm-grid">
                    <div class="mm-row"><dt>Coordinator</dt><dd><?= esc((string) ($planHolder['coordinator'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Application Date</dt><dd><?= esc($fmtDate((string) ($planHolder['application_date'] ?? ''))) ?></dd></div>
                    <div class="mm-row"><dt>Date of Birth</dt><dd><?= esc($fmtDate((string) ($planHolder['date_of_birth'] ?? ''))) ?></dd></div>
                    <div class="mm-row"><dt>Place of Birth</dt><dd><?= esc((string) ($planHolder['place_of_birth'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Gender</dt><dd><?= esc((string) ($planHolder['gender'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Civil Status</dt><dd><?= esc((string) ($planHolder['civil_status'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Citizenship</dt><dd><?= esc((string) ($planHolder['citizenship'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Contact Number</dt><dd><?= esc((string) ($user['contact_number'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Email</dt><dd><?= esc((string) ($user['email'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Address</dt><dd><?= esc(trim((string) (($planHolder['address_no'] ?? '') . ' ' . ($planHolder['address_street'] ?? '') . ' ' . ($planHolder['address_barangay'] ?? '') . ' ' . ($planHolder['address_city'] ?? '')))) ?: '—' ?></dd></div>
                    <div class="mm-row"><dt>Senior Citizen ID</dt><dd><?= esc((string) ($planHolder['senior_citizen_id'] ?? '—')) ?></dd></div>
                    <div class="mm-row"><dt>Organization Affiliation</dt><dd><?= esc((string) ($planHolder['organization_affiliation'] ?? '—')) ?></dd></div>
                </div>
            </div>
        </div>

        <!-- Beneficiaries -->
        <div class="mm-card">
            <div class="mm-card__head">
                <h5 class="mm-card__title"><i class="mdi mdi-account-multiple"></i> Beneficiaries</h5>
                <span class="mm-badge mm-badge--primary"><?= count($beneficiaries) ?> record<?= count($beneficiaries) === 1 ? '' : 's' ?></span>
            </div>
            <div class="mm-card__body">
                <?php if ($beneficiaries): ?>
                    <div class="mm-table-wrap">
                        <table class="mm-table">
                            <thead>
                                <tr>
                                    <th>Complete Name</th>
                                    <th>Relationship</th>
                                    <th>Birthday</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beneficiaries as $ben): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc(trim((string) (
                                                ($ben['first_name'] ?? '') . ' ' .
                                                ($ben['middle_name'] ?? '') . ' ' .
                                                ($ben['last_name'] ?? '') . ' ' .
                                                ($ben['name_extension'] ?? '')
                                            ))) ?></strong>
                                        </td>
                                        <td><?= esc((string) ($ben['relationship'] ?? '—')) ?></td>
                                        <td><?= esc($fmtDate((string) ($ben['date_of_birth'] ?? ''))) ?></td>
                                        <td>
                                            <?php if ((int) ($ben['is_primary'] ?? 0) === 1): ?>
                                                <span class="mm-badge mm-badge--primary">Primary</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="mm-empty"><i class="mdi mdi-account-multiple-outline"></i>No beneficiaries recorded.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contributions -->
        <div class="mm-card">
            <div class="mm-card__head">
                <h5 class="mm-card__title"><i class="mdi mdi-receipt-text-outline"></i> Recent Contributions</h5>
                <a href="<?= base_url('client/payment') ?>" style="font-size:0.8rem;font-weight:700;color:var(--mm-navy);text-decoration:none;">View All &rarr;</a>
            </div>
            <div class="mm-card__body">
                <?php if ($current_contributions): ?>
                    <div class="mm-table-wrap">
                        <table class="mm-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_contributions as $pay): ?>
                                    <?php
                                        $payStatus = strtolower((string) ($pay['status'] ?? ''));
                                        $payBadge = match ($payStatus) {
                                            'paid', 'approved', 'verified' => ['bg' => 'var(--mm-green-soft)', 'color' => 'var(--mm-green)'],
                                            'pending' => ['bg' => 'var(--mm-gold-soft)', 'color' => 'var(--mm-gold)'],
                                            'cancelled', 'rejected' => ['bg' => 'var(--mm-red-soft)', 'color' => 'var(--mm-red)'],
                                            default => ['bg' => 'var(--mm-slate-soft)', 'color' => 'var(--mm-slate)'],
                                        };
                                    ?>
                                    <tr>
                                        <td><?= esc($fmtDate((string) ($pay['payment_date'] ?? ''))) ?></td>
                                        <td><?= esc((string) ($pay['reference_number'] ?? '—')) ?></td>
                                        <td><?= esc(ucfirst((string) ($pay['payment_method'] ?? '—'))) ?></td>
                                        <td><strong>₱<?= number_format((float) ($pay['amount'] ?? 0), 2) ?></strong></td>
                                        <td><span class="mm-badge" style="background:<?= esc($payBadge['bg']) ?>;color:<?= esc($payBadge['color']) ?>;"><?= esc(ucfirst($payStatus !== '' ? $payStatus : '—')) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="mm-empty"><i class="mdi mdi-receipt-text-outline"></i>No contributions recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>
