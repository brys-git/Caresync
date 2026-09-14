<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$isPlanHolder = (bool) ($is_plan_holder ?? true);
$m            = $membership ?? [];
$payments     = $payment_history  ?? [];
$requests     = $service_requests ?? [];

$locked    = (float) ($m['locked_price'] ?? 0);
$remaining = (float) ($m['remaining_balance'] ?? 0);
$paid      = max(0, $locked - $remaining);
$progress  = $locked > 0 ? min(100, round(($paid / $locked) * 100)) : 0;
?>

<?php if (! $isPlanHolder): ?>
    <?php $pendingStatus = (string) ($pending_registration['status'] ?? ''); ?>

    <section class="cs-panel">
        <div class="cs-panel__body" style="max-width:56ch">
            <?= cs_status($pendingStatus !== '' ? $pendingStatus : 'draft') ?>

            <h2 class="cs-serif mt-3 mb-2" style="font-size:1.5rem;letter-spacing:-.01em">
                <?php if ($pendingStatus === 'pending'): ?>
                    Your registration is with your branch
                <?php elseif ($pendingStatus === 'rejected'): ?>
                    Your registration needs changes
                <?php else: ?>
                    One step left before your plan starts
                <?php endif; ?>
            </h2>

            <p class="cs-muted">
                <?php if ($pendingStatus === 'pending'): ?>
                    Submitted <?= cs_date($pending_registration['created_at'] ?? null) ?>.
                    A branch officer reviews it and you'll be notified here once it's approved.
                <?php elseif ($pendingStatus === 'rejected'): ?>
                    Your branch asked for corrections. Update your details and send it again.
                <?php else: ?>
                    Payments, service requests, and your plan record unlock once you register
                    as a plan holder and a branch approves it.
                <?php endif; ?>
            </p>

            <a class="btn btn-primary" href="<?= esc((string) ($registration_url ?? base_url('plan-holder-registration'))) ?>">
                <?= $pendingStatus === 'pending' ? 'Check registration status' : 'Continue registration' ?>
            </a>
        </div>
    </section>

<?php else: ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <section class="cs-panel h-100">
                <div class="cs-panel__body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="cs-muted" style="font-size:.8125rem">Plan number</div>
                            <div class="cs-serif cs-num" style="font-size:1.375rem;font-weight:600">
                                <?= esc((string) ($m['unique_identifier'] ?? '—')) ?>
                            </div>
                        </div>
                        <?= cs_status((string) ($m['plan_status'] ?? 'inactive')) ?>
                    </div>

                    <!-- The balance is the one thing a plan holder opens this page to see. -->
                    <div class="cs-meterrow">
                        <span>Paid so far</span>
                        <span><?= esc((string) $progress) ?>% of <?= cs_money($locked) ?></span>
                    </div>
                    <div class="cs-meter" role="img"
                         aria-label="<?= esc($progress) ?> percent of your plan is paid">
                        <div class="cs-meter__fill <?= $progress < 25 ? 'cs-meter__fill--warn' : '' ?>"
                             style="width: <?= esc((string) $progress) ?>%"></div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-sm-6">
                            <div class="cs-muted" style="font-size:.8125rem">Paid to date</div>
                            <div class="cs-num" style="font-size:1.125rem;font-weight:600;color:var(--cs-ok)">
                                <?= cs_money($paid) ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="cs-muted" style="font-size:.8125rem">Remaining balance</div>
                            <div class="cs-num" style="font-size:1.125rem;font-weight:600;color:var(--cs-brass)">
                                <?= cs_money($remaining) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cs-panel__foot d-flex justify-content-between align-items-center">
                    <span class="cs-muted">Price locked at enrolment and never re-priced.</span>
                    <a class="btn btn-primary btn-sm" href="<?= base_url('client/payment') ?>">Make a payment</a>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="cs-panel h-100">
                <div class="cs-panel__head">
                    <h2 class="cs-panel__title">Plan details</h2>
                </div>
                <div class="cs-panel__body">
                    <?php
                    $rows = [
                        ['Membership', ucfirst((string) ($m['membership_status'] ?? '—'))],
                        ['Branch',     (string) ($m['branch_name'] ?? '—')],
                        ['Package',    (string) ($m['package_name'] ?? '—')],
                        ['Start date', cs_date($m['effective_date'] ?? null)],
                    ];
                    ?>
                    <?php foreach ($rows as $i => [$label, $value]): ?>
                        <div class="d-flex justify-content-between align-items-baseline gap-3 <?= $i < count($rows) - 1 ? 'mb-2 pb-2' : '' ?>"
                             style="<?= $i < count($rows) - 1 ? 'border-bottom:1px solid var(--cs-line-soft)' : '' ?>">
                            <span class="cs-muted"><?= esc($label) ?></span>
                            <span class="fw-semibold text-end"><?= $value ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <section class="cs-panel h-100">
                <div class="cs-panel__head">
                    <div>
                        <h2 class="cs-panel__title">Payment history</h2>
                        <p class="cs-panel__note">Every payment recorded against your plan.</p>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?= base_url('client/payment') ?>">All</a>
                </div>

                <div class="cs-panel__body cs-panel__body--flush">
                    <?php if ($payments === []): ?>
                        <?= view('components/empty_state', [
                            'icon'   => 'ti-receipt',
                            'title'  => 'No payments recorded yet',
                            'text'   => 'Once your first payment is posted by your branch or collector, it appears here with a receipt.',
                            'action' => ['label' => 'Make a payment', 'url' => 'client/payment'],
                        ]) ?>
                    <?php else: ?>
                        <div class="cs-tablewrap">
                            <table class="cs-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th class="cs-num">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td class="text-nowrap"><?= cs_date($payment['payment_date'] ?? null) ?></td>
                                            <td><?= esc(ucfirst(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '—')))) ?></td>
                                            <td><?= cs_status((string) ($payment['status'] ?? 'pending')) ?></td>
                                            <td class="cs-num fw-semibold"><?= cs_money($payment['amount'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="cs-panel h-100">
                <div class="cs-panel__head">
                    <div>
                        <h2 class="cs-panel__title">Service requests</h2>
                        <p class="cs-panel__note">What you've asked your branch to arrange.</p>
                    </div>
                </div>

                <div class="cs-panel__body <?= $requests === [] ? 'cs-panel__body--flush' : '' ?>">
                    <?php if ($requests === []): ?>
                        <?= view('components/empty_state', [
                            'icon'   => 'ti-clipboard-list',
                            'title'  => 'No requests yet',
                            'text'   => 'When you need to use your plan, start a request and your branch will take it from there.',
                            'action' => ['label' => 'Request a service', 'url' => 'client/service'],
                        ]) ?>
                    <?php else: ?>
                        <?php foreach ($requests as $i => $request): ?>
                            <div class="d-flex justify-content-between align-items-start gap-2 <?= $i > 0 ? 'mt-2 pt-2' : '' ?>"
                                 style="<?= $i > 0 ? 'border-top:1px solid var(--cs-line-soft)' : '' ?>">
                                <div class="lh-sm">
                                    <div class="fw-semibold"><?= esc((string) ($request['package_name'] ?? 'Service request')) ?></div>
                                    <div class="cs-table__sub">
                                        Request #<?= esc((string) ($request['application_id'] ?? '—')) ?>
                                        · <?= cs_date($request['created_at'] ?? null) ?>
                                    </div>
                                </div>
                                <?= cs_status((string) ($request['status'] ?? 'pending')) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

<?php endif; ?>
<?= $this->endSection() ?>
