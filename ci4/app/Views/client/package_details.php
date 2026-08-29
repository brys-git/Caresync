<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'new'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Package Details</h1>
            <p class="text-muted mb-0">Review the package information.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=packages') ?>">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2"><?= esc((string) ($package['package_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
            <div class="row g-2">
                <div class="col-md-4"><strong>Base Price:</strong> P<?= esc(number_format((float) ($package['base_price'] ?? 0), 2)) ?></div>
                <div class="col-md-4"><strong>Customizable:</strong> <?= ((int) ($package['is_customizable'] ?? 0) === 1) ? 'Yes' : 'No' ?></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Services Included</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Description</th>
                            <th>Base Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rows = $package_services ?? []; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="3" class="text-center">No services listed.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= esc((string) ($row['service_name'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($row['description'] ?? '-')) ?></td>
                                    <td>P<?= esc(number_format((float) ($row['base_price'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <?php if ($state === 'approved'): ?>
            <a class="btn btn-primary" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">Apply for Package</a>
        <?php elseif ($state === 'pending'): ?>
            <div class="alert alert-warning">Approval required before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
