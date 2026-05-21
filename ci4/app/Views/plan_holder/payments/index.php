<?= $this->extend($role_layout ?? 'layouts/plan_holder') ?>

<?= $this->section('content') ?>
<?php
$planStatus = strtolower((string) ($plan['plan_status'] ?? 'inactive'));
$statusClass = $planStatus === 'completed' ? 'success' : ($planStatus === 'active' ? 'primary' : 'secondary');
?>

<style>
    .payment-shell {
        background:
            radial-gradient(circle at 12% 0%, rgba(15, 118, 110, 0.12), transparent 38%),
            radial-gradient(circle at 90% 10%, rgba(37, 99, 235, 0.12), transparent 42%);
        border-radius: 26px;
        padding: 1rem;
    }

    .payment-hero {
        border-radius: 28px;
        padding: 1.6rem;
        color: #fff;
        background:
            radial-gradient(circle at 80% 28%, rgba(255, 255, 255, 0.18), transparent 40%),
            linear-gradient(135deg, #0f766e, #2563eb);
        box-shadow: 0 24px 52px rgba(15, 23, 42, 0.18);
    }

    .glass-card {
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    }

    .metric-chip {
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #fff;
        padding: 0.8rem 0.95rem;
        height: 100%;
    }

    .metric-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .metric-value {
        color: #0f172a;
        font-weight: 800;
    }

    .history-row {
        cursor: pointer;
    }

    .history-row:hover {
        background: rgba(15, 118, 110, 0.06);
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.24rem 0.68rem;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .status-pill.paid {
        color: #166534;
        background: rgba(22, 163, 74, 0.16);
    }

    .status-pill.pending {
        color: #a16207;
        background: rgba(234, 179, 8, 0.18);
    }

    .status-pill.cancelled {
        color: #b91c1c;
        background: rgba(239, 68, 68, 0.16);
    }

    .status-pill.unpaid {
        color: #b91c1c;
        background: rgba(239, 68, 68, 0.16);
    }
</style>

<div class="container-fluid payment-shell">
    <div class="payment-hero mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="small text-uppercase text-white-50 fw-bold mb-2">Payment Management</div>
                <h1 class="h2 fw-bold mb-1">Track your contribution progress and payment history.</h1>
                <div class="text-white-75">Plan Holder: <?= esc(trim((string) (($plan_holder['first_name'] ?? '') . ' ' . ($plan_holder['last_name'] ?? '')))) ?: '-' ?></div>
                <div class="text-white-75">Member ID: <?= esc((string) ($plan_holder['unique_identifier'] ?? '-')) ?></div>
            </div>
            <div class="text-lg-end">
                <div class="small text-uppercase text-white-50 fw-bold mb-1">Current Plan</div>
                <div class="h5 mb-1"><?= esc((string) ($plan['program_name'] ?? 'No active plan')) ?></div>
                <span class="badge bg-light text-dark text-uppercase"><?= esc((string) ($plan['plan_status'] ?? 'inactive')) ?></span>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (! $plan): ?>
        <div class="glass-card p-4 text-center text-secondary">
            You do not have an active plan yet. Payment details will appear here after a plan is assigned.
        </div>
    <?php else: ?>
        <div class="glass-card p-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Payment Overview</h2>
                    <p class="text-secondary mb-0">Plan summary based on your active plan record.</p>
                </div>
                <span class="badge bg-<?= esc($statusClass) ?> text-uppercase"><?= esc((string) ($plan['plan_status'] ?? 'inactive')) ?></span>
            </div>

            <div class="row g-3">
                <div class="col-sm-6 col-xl-3"><div class="metric-chip"><div class="metric-label">Monthly Fee</div><div class="metric-value">P<?= number_format((float) ($plan['monthly_fee'] ?? 0), 2) ?></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="metric-chip"><div class="metric-label">Start Date</div><div class="metric-value"><?= esc((string) ($plan['start_date'] ?? '-')) ?></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="metric-chip"><div class="metric-label">Months Paid</div><div class="metric-value"><?= esc((string) ($plan['months_paid'] ?? 0)) ?></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="metric-chip"><div class="metric-label">Remaining Balance</div><div class="metric-value text-danger">P<?= number_format((float) ($plan['remaining_balance'] ?? 0), 2) ?></div></div></div>
            </div>
        </div>

        <div class="glass-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h5 mb-0">Payment Status</h2>
                <div class="small text-secondary"><?= esc((string) ($summary['months_paid'] ?? 0)) ?> / <?= esc((string) ($summary['expected_months'] ?? 12)) ?> months paid</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="metric-chip">
                        <div class="metric-label">Total Paid</div>
                        <div class="metric-value text-success">P<?= number_format((float) ($summary['total_paid'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="metric-chip">
                        <div class="metric-label">Remaining Balance</div>
                        <div class="metric-value text-danger">P<?= number_format((float) ($summary['remaining_balance'] ?? 0), 2) ?></div>
                    </div>
                </div>
            </div>

            <div class="progress" role="progressbar" aria-label="Contribution Progress" aria-valuenow="<?= esc((string) ($summary['progress_percent'] ?? 0)) ?>" aria-valuemin="0" aria-valuemax="100" style="height: 14px; border-radius: 999px;">
                <div class="progress-bar bg-success" style="width: <?= esc((string) ($summary['progress_percent'] ?? 0)) ?>%"></div>
            </div>
            <div class="small text-secondary mt-2">Contribution Progress: <?= esc((string) ($summary['progress_percent'] ?? 0)) ?>%</div>
        </div>

        <div class="glass-card p-4">
            <ul class="nav nav-tabs mb-3" id="paymentDataTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="payment-history-tab" data-bs-toggle="tab" data-bs-target="#payment-history-panel" type="button" role="tab" aria-controls="payment-history-panel" aria-selected="true">
                        Payment History
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-contributions-tab" data-bs-toggle="tab" data-bs-target="#payment-contributions-panel" type="button" role="tab" aria-controls="payment-contributions-panel" aria-selected="false">
                        Contributions (Paid / Unpaid)
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="payment-history-panel" role="tabpanel" aria-labelledby="payment-history-tab" tabindex="0">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Payment History</h2>
                            <p class="text-secondary mb-0">Latest payments first. Click any row to view full details.</p>
                        </div>
                        <form method="get" action="<?= base_url('client/payment') ?>" class="d-flex align-items-center gap-2">
                            <label class="small text-secondary" for="statusFilter">Status</label>
                            <select id="statusFilter" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" <?= $selected_status === '' ? 'selected' : '' ?>>All</option>
                                <option value="paid" <?= $selected_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="cancelled" <?= $selected_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <?php $rowStatus = strtolower((string) ($payment['status'] ?? 'pending')); ?>
                                    <tr class="history-row" data-payment-id="<?= esc((string) $payment['payment_id']) ?>">
                                        <td><?= esc((string) ($payment['payment_date'] ?? '-')) ?></td>
                                        <td class="fw-bold">P<?= number_format((float) ($payment['amount'] ?? 0), 2) ?></td>
                                        <td><?= esc(strtoupper((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                        <td><?= esc((string) (($payment['reference_number'] ?? '') !== '' ? $payment['reference_number'] : '-')) ?></td>
                                        <td><span class="status-pill <?= esc($rowStatus) ?>"><?= esc((string) ($payment['status'] ?? '-')) ?></span></td>
                                        <td><?= esc((string) (($payment['remarks'] ?? '') !== '' ? $payment['remarks'] : '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary py-4">No payment records found for this filter.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="payment-contributions-panel" role="tabpanel" aria-labelledby="payment-contributions-tab" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Contribution Table</h2>
                            <p class="text-secondary mb-0">Monthly contribution tracking based on your active Damayan plan.</p>
                        </div>
                    </div>

                    <?php
                        $expectedMonths = max(1, (int) ($summary['expected_months'] ?? 12));
                        $monthsPaid = max(0, (int) ($summary['months_paid'] ?? 0));
                        $monthlyFee = (float) ($plan['monthly_fee'] ?? 0);
                    ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Month #</th>
                                    <th>Contribution Amount</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($month = 1; $month <= $expectedMonths; $month++): ?>
                                    <?php $isPaid = $month <= $monthsPaid; ?>
                                    <tr>
                                        <td><?= esc((string) $month) ?></td>
                                        <td class="fw-semibold">P<?= number_format($monthlyFee, 2) ?></td>
                                        <td>
                                            <span class="status-pill <?= $isPaid ? 'paid' : 'unpaid' ?>">
                                                <?= $isPaid ? 'Paid' : 'Unpaid' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-labelledby="paymentDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentDetailLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentDetailContent" class="text-secondary">Select a payment row to view details.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const rows = document.querySelectorAll('.history-row');
        const modalElement = document.getElementById('paymentDetailModal');
        const detailContent = document.getElementById('paymentDetailContent');

        if (!modalElement || !detailContent || rows.length === 0 || typeof bootstrap === 'undefined') {
            return;
        }

        const detailModal = new bootstrap.Modal(modalElement);

        function buildDetailHtml(payment) {
            const status = (payment.status || '').toString().toUpperCase();
            const method = (payment.payment_method || '').toString().toUpperCase();
            const receiver = ((payment.receiver_first_name || '') + ' ' + (payment.receiver_last_name || '')).trim() || '-';
            const branch = payment.branch_name || '-';
            const reference = payment.reference_number || '-';
            const remarks = payment.remarks || '-';

            return `
                <div class="d-grid gap-2 small">
                    <div><strong>Payment Date:</strong> ${payment.payment_date || '-'}</div>
                    <div><strong>Amount:</strong> P${Number(payment.amount || 0).toFixed(2)}</div>
                    <div><strong>Method:</strong> ${method}</div>
                    <div><strong>Reference Number:</strong> ${reference}</div>
                    <div><strong>Status:</strong> ${status}</div>
                    <div><strong>Remarks:</strong> ${remarks}</div>
                    <hr class="my-2">
                    <div><strong>Received By:</strong> ${receiver}</div>
                    <div><strong>Branch:</strong> ${branch}</div>
                </div>
            `;
        }

        rows.forEach((row) => {
            row.addEventListener('click', async function () {
                const paymentId = row.getAttribute('data-payment-id');
                if (!paymentId) {
                    return;
                }

                detailContent.textContent = 'Loading payment details...';
                detailModal.show();

                try {
                    const response = await fetch(`<?= base_url('client/payment/details') ?>/${paymentId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to load payment details.');
                    }

                    detailContent.innerHTML = buildDetailHtml(data.payment || {});
                } catch (error) {
                    detailContent.innerHTML = `<div class="text-danger">${error.message}</div>`;
                }
            });
        });
    })();
</script>
<?= $this->endSection() ?>
