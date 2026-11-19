<?php
/**
 * Official receipt body (same header/layout/stylesheet as the report PDFs).
 * Data comes from ReceiptPdfService::respond().
 *
 * @var array<string,mixed> $branch  branches row or null
 */
$branch  = $branch ?? null;
$address = '';
if (is_array($branch)) {
    $address = implode(', ', array_filter(array_map('trim', [
        (string) ($branch['address_street'] ?? ''),
        (string) ($branch['address_barangay'] ?? ''),
        (string) ($branch['address_city'] ?? ''),
        (string) ($branch['address_province'] ?? ''),
    ])));
}
$row = static fn (string $label, string $value): string =>
    '<tr><td style="width:32%;background:#f0f0f0;font-weight:bold;">' . esc($label) . '</td><td>' . $value . '</td></tr>';
?>
<table class="rpt-table" style="margin-bottom:10px;">
    <tbody>
        <?= $row('Receipt (OR) number', '<strong>' . esc($receipt_no) . '</strong>') ?>
        <?= $row('Branch', esc((string) ($branch['branch_name'] ?? '-'))) ?>
        <?php if ($address !== ''): ?><?= $row('Branch address', esc($address)) ?><?php endif; ?>
        <?php if (! empty($branch['contact_number'])): ?><?= $row('Branch contact number', esc((string) $branch['contact_number'])) ?><?php endif; ?>
    </tbody>
</table>

<table class="rpt-table" style="margin-bottom:10px;">
    <tbody>
        <?= $row('Received from', esc($holder_name !== '' ? $holder_name : '-')) ?>
        <?= $row('ID Control No.', esc($id_control_no ?? '-')) ?>
        <?= $row('Plan holder ID', esc($holder_id)) ?>
        <?= $row('Plan / program', esc($program_name)) ?>
        <?php if ($package_name !== ''): ?><?= $row('Package', esc($package_name)) ?><?php endif; ?>
    </tbody>
</table>

<table class="rpt-table" style="margin-bottom:10px;">
    <tbody>
        <?= $row('Payment date', esc($payment_date !== '' ? date('F j, Y', strtotime($payment_date)) : '-')) ?>
        <?= $row('Amount paid', '<strong>' . esc(cs_peso($amount)) . '</strong>') ?>
        <?php if ($discount > 0): ?>
            <?= $row('Discount', esc(cs_peso($discount)) . ($discount_reason !== '' ? ' (' . esc($discount_reason) . ')' : '')) ?>
        <?php endif; ?>
        <?php if (! empty($applied_to)): ?>
            <?php foreach ($applied_to as $i => $a): ?>
                <?= $row($i === 0 ? 'Applied to' : '', esc($a['label']) . ' &mdash; ' . esc(cs_peso($a['amount']))) ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <?= $row('Payment method', esc($method_label)) ?>
        <?php if ($is_gcash): ?><?= $row('GCash reference number', esc($reference !== '' ? $reference : '-')) ?><?php endif; ?>
        <?= $row('Months covered', (int) $months . ' month' . ($months === 1 ? '' : 's')) ?>
        <?= $row('Coverage period', $coverage /* already escaped by cs_coverage_period() */) ?>
        <?php if ($remaining_after !== null): ?>
            <?= $row(! empty($applied_to) ? 'Remaining plan balance after this payment' : 'Remaining balance after this payment', esc(cs_peso($remaining_after))) ?>
        <?php endif; ?>
    </tbody>
</table>

<table class="rpt-table" style="margin-bottom:14px;">
    <tbody>
        <?= $row('Received by', esc($received_by !== '' ? $received_by : '-')) ?>
        <?= $row('Verified by', esc($verified_by !== '' ? $verified_by : '-')) ?>
    </tbody>
</table>

<p class="rpt-note" style="text-align:center;">Thank you for your contribution. Please keep this receipt for your records.</p>
