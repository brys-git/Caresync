<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">

<?php
    $phName = esc(trim((string) (($plan_holder['first_name'] ?? '') . ' ' . ($plan_holder['last_name'] ?? ''))));
    $phId = esc((string) ($plan_holder['unique_identifier'] ?? ''));
?>

<div class="so">

    <!-- Header -->
    <div>
        <h1 class="so-header__title">Service History</h1>
        <p class="so-header__sub">Complete service history for <?= $phName ?> (<?= $phId ?>)</p>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error"><i class="mdi mdi-alert-circle-outline"></i> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success"><i class="mdi mdi-check-circle-outline"></i> <?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <!-- KPI Summary -->
    <?php
        $totalCount = count($services);
        $completedCount = 0;
        $ongoingCount = 0;
        $cancelledCount = 0;
        foreach ($services as $s) {
            $st = strtolower((string) ($s['status'] ?? ''));
            if ($st === 'completed') $completedCount++;
            elseif ($st === 'ongoing') $ongoingCount++;
            elseif ($st === 'cancelled') $cancelledCount++;
        }
    ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
        <div class="so-card" style="padding:16px 18px;">
            <div style="font-size:0.78rem;font-weight:700;color:var(--so-ink-faint);text-transform:uppercase;letter-spacing:0.05em;">Total Services</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--so-ink);"><?= $totalCount ?></div>
        </div>
        <div class="so-card" style="padding:16px 18px;">
            <div style="font-size:0.78rem;font-weight:700;color:var(--so-ink-faint);text-transform:uppercase;letter-spacing:0.05em;">Completed</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--so-green);"><?= $completedCount ?></div>
        </div>
        <div class="so-card" style="padding:16px 18px;">
            <div style="font-size:0.78rem;font-weight:700;color:var(--so-ink-faint);text-transform:uppercase;letter-spacing:0.05em;">Ongoing</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--so-blue);"><?= $ongoingCount ?></div>
        </div>
        <div class="so-card" style="padding:16px 18px;">
            <div style="font-size:0.78rem;font-weight:700;color:var(--so-ink-faint);text-transform:uppercase;letter-spacing:0.05em;">Cancelled</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--so-red);"><?= $cancelledCount ?></div>
        </div>
    </div>

    <!-- Services Table -->
    <div class="so-card">
        <div class="so-card__header">
            <h3 class="so-card__title">All Services</h3>
            <a href="<?= site_url('/branch-admin/service-package/ongoing') ?>" class="so-card__link">Back to Ongoing</a>
        </div>
        <div class="so-card__body" style="padding-top:4px;">
            <?php if (empty($services)): ?>
                <div class="so-empty"><i class="mdi mdi-tools"></i>No services found for this plan holder.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="so-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Service / Package</th>
                                <th>Assigned Staff</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $svc):
                                $status = strtolower((string) ($svc['status'] ?? 'pending'));
                                $svcName = esc((string) ($svc['service_name'] ?? $svc['package_name'] ?? '-'));
                                $staffName = esc(trim((string) (($svc['staff_first_name'] ?? '') . ' ' . ($svc['staff_last_name'] ?? '')))) ?: '-';
                                $notes = esc((string) ($svc['notes'] ?? '-'));
                                $badgeClass = match ($status) {
                                    'completed' => 'so-badge--green',
                                    'ongoing' => 'so-badge--teal',
                                    'cancelled' => 'so-badge--amber',
                                    default => 'so-badge--slate',
                                };
                            ?>
                            <tr>
                                <td><strong><?= esc((string) ($svc['service_date'] ?? '-')) ?></strong></td>
                                <td><?= $svcName ?></td>
                                <td><?= $staffName ?></td>
                                <td><span class="so-badge <?= $badgeClass ?>"><?= esc(ucfirst($status)) ?></span></td>
                                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= $notes ?>"><?= $notes ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
