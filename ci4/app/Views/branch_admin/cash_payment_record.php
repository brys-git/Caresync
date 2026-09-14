<?= $this->extend('layouts/branch_admin') ?>

<?= $this->section('content') ?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<section class="cs-panel">
    <div class="cs-panel__body">
        <form method="post" action="<?= base_url('branch-admin/cash-payment-record/save') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="client_name">Client Name <span class="text-danger">*</span></label>
                    <input id="client_name" name="client_name" class="form-control" value="<?= esc(old('client_name')) ?>" required>
                    <small class="text-muted">Name of the client paying in cash at the branch</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="months_covered">Months Covered <span class="text-danger">*</span></label>
                    <input id="months_covered" name="months_covered" type="number" min="1" max="12" class="form-control" value="<?= esc(old('months_covered', '1')) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="amount">Amount <span class="text-muted">(readonly)</span></label>
                    <input id="amount" type="text" class="form-control" readonly value="₱">
                    <small class="text-muted">Auto-calculated at ₱240/month</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="receipt_number">Official Receipt Number <span class="text-danger">*</span></label>
                    <input id="receipt_number" name="receipt_number" class="form-control" placeholder="e.g., OR-2026-001234" value="<?= esc(old('receipt_number')) ?>" required>
                    <small class="text-muted">Unique receipt identifier. Client will use this to verify payment.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Payment Date</label>
                    <input type="text" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                    <small class="text-muted">Automatically set to today</small>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Record Payment</button>
                <a href="<?= base_url('branch-admin/cash-payments') ?>" class="btn btn-outline-secondary">View Payments</a>
            </div>
        </form>
    </div>
</section>

<script>
    (function() {
        const monthsCovered = document.getElementById('months_covered');
        const amountField = document.getElementById('amount');
        const monthlyFee = 240;

        function updateAmount() {
            const months = parseInt(monthsCovered.value) || 1;
            const total = months * monthlyFee;
            amountField.value = '₱' + total.toLocaleString();
        }

        monthsCovered.addEventListener('change', updateAmount);
        monthsCovered.addEventListener('input', updateAmount);
        updateAmount();
    })();
</script>
<?= $this->endSection() ?>
