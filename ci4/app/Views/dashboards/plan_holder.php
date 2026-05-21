<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$isPlanHolder = (bool) ($is_plan_holder ?? true);
$serviceStats = [
    'services' => count($service_requests ?? []),
    'payments' => count($payment_history ?? []),
    'packages' => count($packages ?? []),
];
?>

<style>
    .plan-holder-shell {
        position: relative;
    }

    .plan-holder-hero {
        background:
            radial-gradient(circle at top left, rgba(15, 118, 110, 0.16), transparent 34%),
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.16), transparent 28%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(247, 250, 252, 0.94));
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        border-radius: 28px;
        overflow: hidden;
    }

    .plan-holder-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.08);
        color: #0f766e;
        font-weight: 700;
        font-size: 0.8rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .plan-holder-hero__panel {
        background: linear-gradient(160deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.96));
        color: rgba(255, 255, 255, 0.92);
        border-radius: 24px;
        padding: 1.5rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .plan-holder-hero__panel::after {
        content: '';
        position: absolute;
        inset: auto -3rem -3rem auto;
        width: 10rem;
        height: 10rem;
        border-radius: 50%;
        background: rgba(37, 99, 235, 0.28);
        filter: blur(22px);
    }

    .stat-pill {
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
        padding: 1rem 1.1rem;
        height: 100%;
    }

    .service-card__icon {
        width: 3rem;
        height: 3rem;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.12), rgba(37, 99, 235, 0.12));
        color: #0f766e;
    }
</style>

<div class="container-fluid plan-holder-shell">
    <?php if (! $isPlanHolder): ?>
        <?php $pendingStatus = (string) ($pending_registration['status'] ?? ''); ?>
        <div class="plan-holder-hero mb-4 p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="plan-holder-hero__badge mb-3">
                        <i class="ti ti-lock"></i>
                        <span>Limited Access</span>
                    </div>
                    <h1 class="display-6 fw-bold mb-3">Complete your plan holder registration first.</h1>
                    <p class="text-secondary mb-0" style="max-width: 44rem;">Your account is active, but service applications, membership management, and payment features are locked until you register as a plan holder.</p>
                    <?php if ($pendingStatus === 'pending'): ?>
                        <div class="alert alert-info mt-3 mb-0" style="max-width: 44rem;">
                            Your registration was submitted on <?= esc((string) ($pending_registration['created_at'] ?? '-')) ?> and is pending approval.
                        </div>
                    <?php elseif ($pendingStatus === 'rejected'): ?>
                        <div class="alert alert-warning mt-3 mb-0" style="max-width: 44rem;">
                            Your latest registration was rejected. Update your details and submit again.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <div class="plan-holder-hero__panel">
                        <div class="position-relative" style="z-index: 1;">
                            <div class="text-white-50 small text-uppercase fw-semibold mb-2">Next step</div>
                            <h2 class="h3 fw-bold mb-3">Submit your registration details</h2>
                            <p class="text-white-75 mb-4">Once submitted, your dashboard will unlock services, membership details, and payment tracking.</p>
                            <a href="<?= esc((string) ($registration_url ?? base_url('plan-holder-registration'))) ?>" class="btn btn-light fw-semibold px-4">
                                <?= $pendingStatus === 'pending' ? 'View Registration Status' : 'Register as Plan Holder' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3 opacity-75">
                        <div class="service-card__icon"><i class="ti ti-clipboard-list"></i></div>
                        <div>
                            <div class="small text-muted fw-semibold">Service Applications</div>
                            <div class="h6 mb-0">Locked until registration</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3 opacity-75">
                        <div class="service-card__icon"><i class="ti ti-id-badge"></i></div>
                        <div>
                            <div class="small text-muted fw-semibold">Membership Details</div>
                            <div class="h6 mb-0">Locked until registration</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3 opacity-75">
                        <div class="service-card__icon"><i class="ti ti-receipt"></i></div>
                        <div>
                            <div class="small text-muted fw-semibold">Payments</div>
                            <div class="h6 mb-0">Locked until registration</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
    <div class="plan-holder-hero mb-4 p-4 p-lg-5">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <div class="plan-holder-hero__badge mb-3">
                    <i class="ti ti-stars"></i>
                    <span>Plan Holder Dashboard</span>
                </div>
                <h1 class="display-6 fw-bold mb-3">Your membership at a glance.</h1>
                <p class="text-secondary mb-4" style="max-width: 42rem;">Track your plan status, payments, and service requests from one dashboard. Service options are now available under the Services page.</p>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-pill">
                            <div class="small text-uppercase text-muted fw-semibold mb-1">Membership</div>
                            <div class="h4 mb-0"><?= esc((string) ($membership['membership_status'] ?? 'n/a')) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-pill">
                            <div class="small text-uppercase text-muted fw-semibold mb-1">Plan Status</div>
                            <div class="h4 mb-0"><?= esc((string) ($membership['plan_status'] ?? 'n/a')) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-pill">
                            <div class="small text-uppercase text-muted fw-semibold mb-1">Remaining Balance</div>
                            <div class="h4 mb-0">P<?= number_format((float) ($membership['remaining_balance'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="plan-holder-hero__panel">
                    <div class="position-relative" style="z-index: 1;">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <div class="text-white-50 small text-uppercase fw-semibold">Account summary</div>
                                <div class="h3 fw-bold mb-0"><?= esc((string) ($membership['unique_identifier'] ?? '-')) ?></div>
                            </div>
                            <div class="rounded-circle bg-white bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 3.25rem; height: 3.25rem;">
                                <i class="ti ti-layout-dashboard fs-4"></i>
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-4" style="background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.08);">
                                <div>
                                    <div class="text-white-50 small">Branch</div>
                                    <div class="fw-semibold"><?= esc((string) ($membership['branch_name'] ?? '-')) ?></div>
                                </div>
                                <i class="ti ti-building text-white-50 fs-4"></i>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-4" style="background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.08);">
                                <div>
                                    <div class="text-white-50 small">Locked Price</div>
                                    <div class="fw-semibold">P<?= number_format((float) ($membership['locked_price'] ?? 0), 2) ?></div>
                                </div>
                                <i class="ti ti-currency-peso text-white-50 fs-4"></i>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-4" style="background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.08);">
                                <div>
                                    <div class="text-white-50 small">Start Date</div>
                                    <div class="fw-semibold"><?= esc((string) ($membership['effective_date'] ?? '-')) ?></div>
                                </div>
                                <i class="ti ti-calendar-event text-white-50 fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="service-card__icon"><i class="ti ti-package"></i></div>
                    <div>
                        <div class="small text-muted fw-semibold">Packages Available</div>
                        <div class="h3 mb-0"><?= esc((string) $serviceStats['packages']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="service-card__icon"><i class="ti ti-receipt"></i></div>
                    <div>
                        <div class="small text-muted fw-semibold">Recent Payments</div>
                        <div class="h3 mb-0"><?= esc((string) $serviceStats['payments']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="service-card__icon"><i class="ti ti-clipboard-list"></i></div>
                    <div>
                        <div class="small text-muted fw-semibold">Service Requests</div>
                        <div class="h3 mb-0"><?= esc((string) $serviceStats['services']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="h5 mb-1">Payment History</h3>
                            <p class="text-secondary mb-0">Most recent transactions tied to your current plan.</p>
                        </div>
                        <i class="ti ti-receipt-2 text-primary fs-3"></i>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_history as $payment): ?>
                                    <tr>
                                        <td><?= esc((string) $payment['payment_date']) ?></td>
                                        <td>P<?= number_format((float) ($payment['amount'] ?? 0), 2) ?></td>
                                        <td><?= esc(strtoupper((string) $payment['payment_method'])) ?></td>
                                        <td><span class="badge text-bg-<?= ((string) ($payment['status'] ?? '') === 'paid') ? 'success' : 'warning' ?>"><?= esc((string) $payment['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($payment_history)): ?>
                                    <tr><td colspan="4" class="text-center text-secondary py-4">No payment history yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="h5 mb-1">Service Requests</h3>
                            <p class="text-secondary mb-0">Your latest submitted requests and their progress.</p>
                        </div>
                        <i class="ti ti-clipboard-check text-primary fs-3"></i>
                    </div>
                    <div class="d-grid gap-3">
                        <?php foreach ($service_requests as $request): ?>
                            <div class="d-flex justify-content-between align-items-start p-3 rounded-4" style="background: rgba(15,23,42,.02); border: 1px solid rgba(148,163,184,.16);">
                                <div>
                                    <div class="fw-bold">#<?= esc((string) $request['application_id']) ?> <?= esc((string) ($request['package_name'] ?? '-')) ?></div>
                                    <div class="small text-secondary">Submitted <?= esc((string) ($request['created_at'] ?? '-')) ?></div>
                                </div>
                                <span class="badge text-bg-<?= ((string) ($request['status'] ?? '') === 'approved' || (string) ($request['status'] ?? '') === 'completed') ? 'success' : 'primary' ?>"><?= esc((string) $request['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($service_requests)): ?>
                            <div class="text-center text-secondary py-4">No service requests yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
