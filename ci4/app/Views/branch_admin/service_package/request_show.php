<?php
/**
 * Claim detail/review page - shared by Branch Admin
 * (BranchAdmin\ServiceApplicationController) and Staff
 * (Staff\ServiceApplicationController), per the panel brief: claims are
 * processed by staff or an Encoder, not just Branch Admin. Set
 * $claim_base_path to the caller's own "requests" route prefix so the
 * download/approve/reject/back links point at the right role.
 */
$claimBasePath = (string) ($claim_base_path ?? '/branch-admin/service-package/requests');
?>
<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="mb-3 text-end">
    <a href="<?= site_url($claimBasePath) ?>" class="btn btn-ghost btn-sm">Back to Claims</a>
</div>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<section class="cs-panel mb-3">
    <div class="cs-panel__body">
        <div class="row gy-3">
            <div class="col-md-4"><strong>Applicant:</strong> <?= esc((string) ($request['first_name'] ?? '') . ' ' . (string) ($request['last_name'] ?? '')) ?></div>
            <div class="col-md-4"><strong>Request ID:</strong> <?= esc((string) ($request['application_id'] ?? '-')) ?></div>
            <div class="col-md-4"><strong>Status:</strong> <?= cs_status((string) ($request['status'] ?? '')) ?></div>
            <div class="col-md-6"><strong>Requested on:</strong> <?= cs_date($request['created_at'] ?? null) ?></div>
            <div class="col-md-6"><strong>Branch ID:</strong> <?= esc((string) ($request['branch_id'] ?? '-')) ?></div>
            <div class="col-md-6"><strong>Type:</strong> <?= esc((string) (($request['service_list_id'] ?? 0) > 0 ? 'Service' : 'Package')) ?></div>
            <div class="col-md-6"><strong>Selected:</strong> <?= esc((string) ($request['service_name'] ?? $request['package_name'] ?? '-')) ?></div>
            <div class="col-md-6"><strong>Price:</strong> <?= cs_money(($request['service_price'] ?? $request['package_price']) ?? 0) ?></div>
            <?php if (! empty($request['filed_on_behalf'])): ?>
                <div class="col-md-6"><strong>Filed by:</strong> <?= esc(trim((string) ($request['filer_first'] ?? '') . ' ' . (string) ($request['filer_last'] ?? ''))) ?> (<?= esc((string) ($request['filer_role'] ?: 'Branch Admin')) ?>)</div>
                <div class="col-md-6"><strong>Claimed by:</strong> <?= esc((string) ($request['claimant_name'] ?? '-')) ?> (<?= esc((string) ($request['claimant_relationship'] ?? '-')) ?>)</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$info       = $claim_info ?? null;
$viewerRole = (int) ($viewer_role ?? 2);
$kind       = (string) ($info['type'] ?? 'claim');
$avail      = $info['avail'] ?? null;
$elig       = $info['eligibility'] ?? null;
$bal        = $info['balances'] ?? null;
$status     = (string) ($request['status'] ?? '');
$money      = static fn ($v): string => '₱' . number_format((float) $v, 2);
$person     = static fn ($f, $l): string => trim((string) $f . ' ' . (string) $l);
?>
<?php $priced = $info['priced'] ?? null; $owes = $priced !== null && (float) $priced['charge'] > 0.004; ?>
<?php if ($info !== null): ?>
<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title"><?= $kind === 'service' ? 'Single service request' : ($kind === 'avail' ? 'Avail of another package' : 'Claim of the plan\'s own package') ?></h2>
        <p class="cs-panel__note">
            <?php if ($kind === 'service'): ?>
                A single service is treated as an avail of its price (until Kaagapay confirms): <?= $money($priced['charge'] ?? 0) ?> is added to the client's balance, with a down payment and monthly instalments.
            <?php elseif ($kind === 'avail'): ?>
                The package price<?= ($priced['addons_total'] ?? 0) > 0 ? ' and the add-ons' : '' ?> is added to the client's balance. A down payment is paid first, then fixed monthly instalments, on top of the plan contribution.
            <?php else: ?>
                The package is released at no extra charge. The remaining plan balance stays and the client keeps paying the monthly contribution.
                <?php if ($owes): ?><strong>The add-on (<?= esc((string) $priced['addons_label']) ?> <?= $money($priced['addons_total']) ?>) is added to the balance</strong> with the same down payment and instalment rules.<?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="cs-panel__body">
        <?php if ($bal !== null): ?>
            <div class="row g-3 mb-2">
                <div class="col-md-3"><small class="cs-muted d-block">Plan balance</small><strong><?= $money($bal['plan_balance']) ?></strong></div>
                <div class="col-md-3"><small class="cs-muted d-block">Package balance</small><strong><?= $money($bal['package_balance']) ?></strong></div>
                <div class="col-md-3"><small class="cs-muted d-block">Total balance</small><strong><?= $money($bal['total_balance']) ?></strong></div>
                <div class="col-md-3"><small class="cs-muted d-block">Monthly total due</small><strong><?= $money($bal['monthly_total_due']) ?></strong></div>
            </div>
        <?php endif; ?>

        <?php if ($status === 'pending' && $elig !== null && empty($elig['allowed'])): ?>
            <div class="alert alert-warning mb-0"><strong>Cannot be approved yet:</strong> <?= esc((string) $elig['message']) ?></div>
        <?php endif; ?>

        <?php if ($status === 'rejected'): ?>
            <div class="alert alert-danger mb-0"><strong>Rejected.</strong> Reason shown to the client: <?= esc((string) ($request['rejection_reason'] ?? '-')) ?></div>
        <?php endif; ?>

        <?php if ($avail !== null): ?>
            <hr>
            <h3 class="h6">Avail terms (set when it was approved)</h3>
            <div class="row g-3">
                <div class="col-md-3"><small class="cs-muted d-block">Package price</small><strong><?= $money($avail['package_price']) ?></strong></div>
                <div class="col-md-3"><small class="cs-muted d-block">Down payment (<?= esc(rtrim(rtrim(number_format((float) $avail['down_payment_rate'] * 100, 2), '0'), '.')) ?>%)</small>
                    <strong><?= $avail['down_payment_status'] === 'waived' ? 'Waived' : $money($avail['down_payment_amount']) ?></strong></div>
                <div class="col-md-3"><small class="cs-muted d-block">Amount financed</small><strong><?= $money($avail['financed_amount']) ?></strong></div>
                <div class="col-md-3"><small class="cs-muted d-block">Monthly instalment</small><strong><?= $money($avail['monthly_instalment']) ?></strong> <span class="cs-muted">× <?= (int) $avail['term_months'] ?> months</span></div>
                <div class="col-md-4"><small class="cs-muted d-block">Down payment status</small>
                    <?php if ($avail['status'] === 'cancelled'): ?><span class="badge text-bg-danger">Cancelled - no down payment</span>
                    <?php elseif ($avail['down_payment_status'] === 'paid'): ?><span class="badge text-bg-success">Paid and verified <?= $avail['down_payment_paid_at'] ? esc(cs_date($avail['down_payment_paid_at'])) : '' ?></span>
                    <?php elseif ($avail['down_payment_status'] === 'waived'): ?><span class="badge text-bg-info">Waived</span>
                    <?php else: ?><span class="badge text-bg-warning">Waiting: <?= $money(max(0, (float) $avail['down_payment_amount'] - (float) ($avail['down_paid'] ?? 0))) ?> due by <?= esc(cs_date($avail['down_payment_due_by'])) ?></span><?php endif; ?>
                </div>
                <div class="col-md-4"><small class="cs-muted d-block">Approved by</small><strong><?= esc($person($avail['approver_first'] ?? '', $avail['approver_last'] ?? '') ?: '-') ?></strong> <span class="cs-muted"><?= esc(cs_date($avail['approved_at'])) ?></span></div>
                <div class="col-md-4"><small class="cs-muted d-block">First instalment due</small><strong><?= $avail['first_due_date'] ? esc(cs_date($avail['first_due_date'])) : 'After the down payment is verified' ?></strong></div>
                <?php if ($avail['down_payment_status'] === 'waived'): ?>
                    <div class="col-12"><div class="alert alert-info mb-0"><strong>Down payment waived</strong> by <?= esc($person($avail['waiver_first'] ?? '', $avail['waiver_last'] ?? '') ?: '-') ?> on <?= esc(cs_date($avail['waived_at'])) ?>. Reason: <?= esc((string) $avail['waiver_reason']) ?></div></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<div class="row g-3">
    <div class="col-lg-6">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Deceased Information</h2></div>
            <div class="cs-panel__body">
                <div class="mb-3"><strong>Name:</strong> <?= esc((string) ($request['deceased_name'] ?? '-')) ?></div>
                <div class="mb-3"><strong>Date of death:</strong> <?= cs_date($request['deceased_date_of_death'] ?? null) ?></div>
                <div class="mb-3"><strong>Relationship:</strong> <?= esc((string) ($request['relationship_to_deceased'] ?? '-')) ?></div>
                <div class="mb-3"><strong>Address:</strong> <?= esc((string) ($request['deceased_address'] ?? '-')) ?></div>
            </div>
        </section>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Beneficiary Details</h2></div>
            <div class="cs-panel__body">
                <div class="mb-3"><strong>Name:</strong> <?= esc((string) ($request['beneficiary_name'] ?? '-')) ?></div>
                <div class="mb-3"><strong>Contact:</strong> <?= esc((string) ($request['beneficiary_contact'] ?? '-')) ?></div>
                <div class="mb-3"><strong>Notes:</strong> <?= esc((string) ($request['application_notes'] ?? '-')) ?></div>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Supporting Documents</h2></div>
            <div class="cs-panel__body">
                <?php if ((int) ($request['awaiting_death_certificate'] ?? 0) === 1): ?>
                    <div class="alert alert-warning mb-3">Awaiting death certificate. The client submitted without it.</div>
                <?php endif; ?>
                <?php if (empty($documents)): ?>
                    <div class="text-muted">No documents uploaded yet.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($documents as $document): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <?php $documentTypeLabels = ['death_certificate' => 'Death certificate', 'beneficiary_id' => 'Beneficiary ID', 'other' => 'Other']; ?>
                                    <?php if (! empty($document['document_type'])): ?><strong><?= esc($documentTypeLabels[$document['document_type']] ?? (string) $document['document_type']) ?>:</strong> <?php endif; ?>
                                    <?= esc((string) ($document['original_name'] ?? 'Unnamed document')) ?>
                                </span>
                                <a href="<?= site_url($claimBasePath . '/document/' . (int) $document['document_id']) ?>" class="btn btn-sm btn-outline-primary">Download</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Actions</h2></div>
            <div class="cs-panel__body">
                <?php if (($request['status'] ?? '') === 'pending'): ?>
                    <?php $isAvail = $owes; $canApprove = $elig === null || ! empty($elig['allowed']); $upfront = ! empty($info['upfront']); ?>

                    <?php if ($isAvail && $viewerRole !== 2): ?>
                        <div class="alert alert-info">Only a Branch Admin can approve or reject this, because it adds an amount to the balance and sets the down payment, the term and any waiver. You can see the terms below and leave a note for the Branch Admin.</div>
                    <?php endif; ?>

                    <?php if ($isAvail && ! empty($info['quotes'])): ?>
                        <?php $terms = $info['terms']; ?>
                        <?php
                        $dueBy   = $info['down_due_by'] ?? null;
                        $quotes  = [];
                        foreach ($info['quotes'] as $t => $pair) {
                            foreach (['normal', 'waived'] as $k) {
                                $q = $pair[$k] ?? [];
                                $sched = $q['schedule'] ?? [];
                                $quotes[$t][$k] = [
                                    'price' => $q['package']['price'] ?? 0, 'down' => $q['down_payment_amount'] ?? 0, 'financed' => $q['financed_amount'] ?? 0,
                                    'monthly' => $q['monthly_instalment'] ?? 0, 'last' => $sched !== [] ? end($sched)['amount'] : 0,
                                    'total_after' => $q['total_balance_after'] ?? 0, 'monthly_total' => $q['monthly_total_due'] ?? 0, 'rate' => $q['down_payment_rate'] ?? 0,
                                ];
                            }
                        }
                        ?>
                        <form action="<?= site_url($claimBasePath . '/approve/' . (int) $request['application_id']) ?>" method="post" id="approveAvailForm"
                              <?php if ($viewerRole === 2): ?>data-cs-confirm="Approve this avail with the terms shown? The client is told the down payment and its deadline." data-cs-confirm-title="Approve avail" data-cs-confirm-ok="Approve"<?php endif; ?>>
                            <?= csrf_field() ?>
                            <?php if ($upfront): ?>
                                <input type="hidden" name="term" value="0">
                                <div class="alert alert-info">This amount is small, so it is paid <strong>in full up front</strong> (no instalments). There is no term to choose.</div>
                            <?php else: ?>
                            <h3 class="h6">Choose the term</h3>
                            <?php endif; ?>
                            <?php foreach ($terms as $i => $t): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="term" id="term<?= (int) $t ?>" value="<?= (int) $t ?>" <?= $i === 1 || count($terms) === 1 ? 'checked' : '' ?> <?= $viewerRole !== 2 ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="term<?= (int) $t ?>"><?= (int) $t ?> months</label>
                                </div>
                            <?php endforeach; ?>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="waive_down_payment" id="waiveDown" value="1" <?= $viewerRole !== 2 ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="waiveDown">Waive the down payment (special case)</label>
                            </div>
                            <div class="mt-2 d-none" id="waiverWrap">
                                <label class="form-label" for="waiver_reason">Reason for the waiver (at least <?= (int) $info['waiver_min'] ?> characters; stored with your name and the date)</label>
                                <textarea class="form-control" id="waiver_reason" name="waiver_reason" rows="2" minlength="<?= (int) $info['waiver_min'] ?>"></textarea>
                            </div>

                            <div class="border rounded p-3 mt-3" id="quoteBox" aria-live="polite">
                                <h3 class="h6">Quote</h3>
                                <dl class="row mb-0">
                                    <dt class="col-7">Package price</dt><dd class="col-5 text-end" id="qPrice"></dd>
                                    <dt class="col-7">Down payment <span id="qRate" class="cs-muted"></span></dt><dd class="col-5 text-end" id="qDown"></dd>
                                    <dt class="col-7">Amount financed</dt><dd class="col-5 text-end" id="qFinanced"></dd>
                                    <dt class="col-7">Monthly instalment</dt><dd class="col-5 text-end" id="qMonthly"></dd>
                                    <dt class="col-7">Last instalment</dt><dd class="col-5 text-end" id="qLast"></dd>
                                    <dt class="col-7">Monthly total due (with plan contribution)</dt><dd class="col-5 text-end" id="qMonthlyTotal"></dd>
                                    <dt class="col-7">Total balance after approval</dt><dd class="col-5 text-end" id="qTotal"></dd>
                                    <dt class="col-7">Down payment must be paid by</dt><dd class="col-5 text-end" id="qDue"></dd>
                                </dl>
                            </div>

                            <?php if ($viewerRole === 2): ?>
                                <button type="submit" class="btn btn-primary mt-3" <?= $canApprove ? '' : 'disabled' ?>>Approve avail</button>
                            <?php endif; ?>
                        </form>
                        <script>
                        (function () {
                            var Q = <?= json_encode($quotes) ?>;
                            var dueBy = <?= json_encode($dueBy ? date('F j, Y', strtotime($dueBy)) : '') ?>;
                            var fmt = function (v) { return '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
                            var waive = document.getElementById('waiveDown'), wrap = document.getElementById('waiverWrap'), reason = document.getElementById('waiver_reason');
                            function term() { var r = document.querySelector('input[name="term"]:checked'); return r ? r.value : (Q['0'] ? '0' : null); }
                            function draw() {
                                var t = term(); if (!t || !Q[t]) { return; }
                                var w = waive.checked, q = Q[t][w ? 'waived' : 'normal'];
                                document.getElementById('qPrice').textContent = fmt(q.price);
                                document.getElementById('qDown').textContent = w ? 'Waived' : fmt(q.down);
                                document.getElementById('qRate').textContent = w ? '' : (q.rate >= 1 ? '(in full)' : '(' + (q.rate * 100).toFixed(0) + '%)');
                                document.getElementById('qFinanced').textContent = fmt(q.financed);
                                document.getElementById('qMonthly').textContent = q.monthly > 0 ? fmt(q.monthly) + ' × ' + t : 'None (paid in full)';
                                document.getElementById('qLast').textContent = fmt(q.last);
                                document.getElementById('qMonthlyTotal').textContent = fmt(q.monthly_total);
                                document.getElementById('qTotal').textContent = fmt(q.total_after);
                                document.getElementById('qDue').textContent = w ? 'Not needed (waived)' : dueBy;
                                wrap.classList.toggle('d-none', !w);
                                reason.required = w;
                            }
                            document.querySelectorAll('input[name="term"]').forEach(function (r) { r.addEventListener('change', draw); });
                            waive.addEventListener('change', draw);
                            draw();
                        })();
                        </script>
                        <?php if ($viewerRole === 2): ?>
                            <hr>
                            <form action="<?= site_url($claimBasePath . '/reject/' . (int) $request['application_id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <label class="form-label" for="rej_reason">Reject this request (the client sees the reason)</label>
                                <div class="d-flex gap-2">
                                    <input type="text" id="rej_reason" name="rejection_reason" class="form-control" placeholder="Reason (required)" required minlength="5">
                                    <button type="submit" class="btn btn-danger">Reject</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <form action="<?= site_url($claimBasePath . '/approve/' . (int) $request['application_id']) ?>" method="post" class="d-inline-block me-2">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary" <?= $canApprove ? '' : 'disabled' ?>>Approve</button>
                        </form>
                        <form action="<?= site_url($claimBasePath . '/reject/' . (int) $request['application_id']) ?>" method="post" class="d-inline-block">
                            <?= csrf_field() ?>
                            <input type="text" name="rejection_reason" class="form-control form-control-sm d-inline-block me-1" style="max-width: 220px;" placeholder="Reason (required)" required minlength="5">
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-muted">This claim is <?= esc(ucfirst((string) ($request['status'] ?? 'unknown'))) ?>.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="cs-panel mt-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Notes for the Branch Admin</h2><p class="cs-panel__note">Internal. The client does not see these.</p></div>
            <div class="cs-panel__body">
                <?php foreach (($notes ?? []) as $n): ?>
                    <div class="border-bottom py-2">
                        <div><?= nl2br(esc((string) $n['note'])) ?></div>
                        <div class="cs-table__sub"><?= esc(trim((string) $n['first_name'] . ' ' . (string) $n['last_name'])) ?> (<?= esc((string) ($n['role_name'] ?? '')) ?>) · <?= esc(cs_date($n['created_at'], true)) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($notes)): ?><p class="cs-muted">No notes yet.</p><?php endif; ?>
                <form action="<?= site_url($claimBasePath . '/note/' . (int) $request['application_id']) ?>" method="post" class="mt-3">
                    <?= csrf_field() ?>
                    <label class="form-label" for="note">Add a note</label>
                    <textarea id="note" name="note" class="form-control" rows="2" maxlength="1000" required></textarea>
                    <button type="submit" class="btn btn-outline-primary btn-sm mt-2">Save note</button>
                </form>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
