<?php
/**
 * The money side of availing a package, as the avail form shows it (partials/burial_request_form.php renders the same
 * rows with JavaScript from the same ClaimAvailService::formQuote() numbers): package price, the amount added to the
 * balance, the down payment, the monthly instalment for every term, and the total balance afterwards.
 *
 * Expects: $q = formQuote()['plain'] (an array with 'name', 'item_price', 'added', 'down', 'rate', 'upfront',
 * 'financed', 'terms', 'plan_balance', 'plan_waived', 'total_after', 'down_days', 'plan_monthly').
 */
$m = static fn ($v): string => cs_money((float) $v);
$rows = [];
if (! empty($q['free'])) {
    $rows[] = [(string) $q['name'] . ' (in your plan)', 'Free', 'cs-routerow--credit'];
} else {
    $rows[] = [(string) $q['name'] . ' - package price', $m($q['item_price']), ''];
}
if (($q['addons_total'] ?? 0) > 0) {
    $rows[] = [(string) $q['addons_label'] . ' add-on', '+ ' . $m($q['addons_total']), ''];
}
if (($q['added'] ?? 0) > 0) {
    $rows[] = ['Amount added to your balance', $m($q['added']), 'cs-routerow--total'];
    if (! empty($q['upfront'])) {
        $rows[] = ['Paid in full up front', $m($q['down']), ''];
    } else {
        $rows[] = ['Down payment (' . round(((float) $q['rate']) * 100) . '%)', $m($q['down']), ''];
        $rows[] = ['Then paid in instalments', $m($q['financed']), ''];
        foreach ((array) ($q['terms'] ?? []) as $t) {
            $rows[] = ['&nbsp;&nbsp;' . (int) $t['term'] . ' months', $m($t['monthly']) . ' a month', ''];
        }
    }
    if (($q['plan_waived'] ?? 0) > 0) {
        $rows[] = ['Plan balance waived', '− ' . $m($q['plan_waived']), 'cs-routerow--credit'];
    } else {
        $rows[] = ['Plan balance (unchanged)', $m($q['plan_balance']), ''];
    }
    $rows[] = ['Total balance after approval', $m($q['total_after']), 'cs-routerow--total'];
}
?>
<?php foreach ($rows as [$label, $value, $class]): ?>
    <div class="cs-routerow <?= $class ?>"><span><?= str_contains($label, '&nbsp;') ? $label : esc($label) ?></span><span><?= $value ?></span></div>
<?php endforeach; ?>
<?php if (($q['added'] ?? 0) > 0): ?>
    <p class="cs-muted small mt-2 mb-3">
        <?= ! empty($q['upfront'])
            ? 'This small amount is paid in full within ' . (int) $q['down_days'] . ' days of approval, or the request is cancelled. Nothing is charged now.'
            : 'Added to your balance when your branch approves. The down payment is due within ' . (int) $q['down_days'] . ' days of approval, or the request is cancelled. Nothing is charged now.' ?>
    </p>
<?php else: ?>
    <p class="cs-muted small mt-2 mb-3">Nothing is added to your balance. You keep paying your plan contribution of <?= $m($q['plan_monthly'] ?? 0) ?> a month.</p>
<?php endif; ?>
