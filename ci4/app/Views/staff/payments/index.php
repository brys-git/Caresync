<?= $this->extend($role_layout ?? 'layouts/staff') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Payment Management</h4>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (! empty($branch_issue)): ?>
        <div class="alert alert-warning"><?= esc($branch_issue) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'monitoring') ? 'active' : '' ?>" href="<?= site_url('/staff/payments') ?>">Payment Monitoring</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'record') ? 'active' : '' ?>" href="<?= site_url('/staff/payments/record') ?>">Record Payment</a>
        </li>
        <li class="nav-item" role="presentation">
            <?php if (! empty($selected_payment)): ?>
                <a class="nav-link <?= ($active_tab === 'update') ? 'active' : '' ?>" href="<?= site_url('/staff/payments/edit/' . (int) $selected_payment['payment_id']) ?>">Update Payment</a>
            <?php else: ?>
                <a class="nav-link <?= ($active_tab === 'update') ? 'active' : '' ?>" href="#">Update Payment</a>
            <?php endif; ?>
        </li>
    </ul>

    <?php if ($active_tab === 'monitoring'): ?>
        <?= $this->include('staff/payments/monitoring') ?>
    <?php endif; ?>

    <?php if ($active_tab === 'record'): ?>
        <?= $this->include('staff/payments/create') ?>
    <?php endif; ?>

    <?php if ($active_tab === 'update'): ?>
        <?= $this->include('staff/payments/edit') ?>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
