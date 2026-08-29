<?= $this->extend($role_layout ?? 'layouts/plan_holder') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Service Balances</h1>
            <p class="text-muted mb-0">Track funeral package balances separate from membership contributions.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Service</th>
                        <th>Package Cost</th>
                        <th>Assistance</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($balances)): ?>
                        <tr><td colspan="7" class="text-center">No service balances found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($balances as $balance): ?>
                            <tr>
                                <td><?= esc(trim((string) ($balance['first_name'] ?? '') . ' ' . (string) ($balance['last_name'] ?? ''))) ?></td>
                                <td><?= esc((string) ($balance['service_name'] ?? $balance['package_name'] ?? '-')) ?></td>
                                <td>P<?= esc(number_format((float) ($balance['package_cost'] ?? 0), 2)) ?></td>
                                <td>P<?= esc(number_format((float) ($balance['assistance_amount'] ?? 0), 2)) ?></td>
                                <td>P<?= esc(number_format((float) ($balance['remaining_balance'] ?? 0), 2)) ?></td>
                                <td><?= esc((string) ($balance['status'] ?? '-')) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?= site_url($route_prefix . '/' . (int) ($balance['service_balance_id'] ?? 0)) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>