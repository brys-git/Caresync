<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php // Flash messages are surfaced as toasts by layouts/_shell.php. ?>
<?php
$labels = ['packages' => 'Funeral Packages', 'services' => 'Services', 'addons' => 'Optional Add-ons'];
$addLabel = ['packages' => 'Add Package', 'services' => 'Add Service', 'addons' => 'Add Add-on'];
$peso = static fn ($n): string => '&#8369;' . number_format((float) $n, 2);
$placeholder = base_url('assets/images/no-image.png');
$img = static fn (?string $p, string $alt): string => '<img src="' . esc(cs_image_url($p)) . '" alt="' . esc($alt) . '" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" onerror="this.onerror=null;this.src=\'' . esc($placeholder) . '\'">';
$tabUrl = static fn (string $t, string $show = ''): string => site_url('/admin/catalog?tab=' . $t . ($show ? '&show=' . $show : ''));
?>

<div class="mb-3 text-end">
    <a href="<?= site_url('/admin/catalog/new/' . $tab) ?>" class="btn btn-primary"><i class="ti ti-plus" aria-hidden="true"></i> <?= esc($addLabel[$tab]) ?></a>
</div>

<ul class="nav nav-tabs mb-4">
    <?php foreach ($labels as $key => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="<?= $tabUrl($key) ?>"><?= esc($label) ?></a></li>
    <?php endforeach; ?>
</ul>

<section class="cs-panel">
    <div class="cs-panel__body">
        <form class="cs-filters" method="get">
            <input type="hidden" name="tab" value="<?= esc($tab) ?>">
            <div class="cs-filters__field">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="<?= esc($search) ?>" placeholder="Name, code or description">
            </div>
            <div class="cs-filters__field">
                <label class="form-label">Show</label>
                <select name="show" class="form-select">
                    <option value="" <?= $show === '' ? 'selected' : '' ?>>Available &amp; unavailable</option>
                    <option value="archived" <?= $show === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="cs-filters__actions"><button class="btn btn-primary" type="submit">Filter</button></div>
        </form>
    </div>

    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-package', 'title' => $show === 'archived' ? 'No archived items' : 'No items found']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Code</th>
                            <th>Name</th>
                            <?php if ($tab === 'packages'): ?><th>Category / Variant</th><th>Price</th><th>Stock</th><?php endif; ?>
                            <?php if ($tab === 'services'): ?><th>Category</th><th>Routes &amp; fees</th><?php endif; ?>
                            <?php if ($tab === 'addons'): ?><th>Price</th><?php endif; ?>
                            <th>Status</th>
                            <th class="cs-table__actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $id   = (int) ($r['package_id'] ?? $r['service_list_id'] ?? $r['addon_id']);
                            $name = (string) ($r['package_name'] ?? $r['service_name'] ?? $r['addon_name']);
                            $st   = (string) ($r['availability_status'] ?? 'available');
                            ?>
                            <tr>
                                <td><?= $img($r['image_path'] ?? null, $name) ?></td>
                                <td class="text-nowrap"><?= esc($r['item_code'] ?? '-') ?></td>
                                <td>
                                    <?= esc($name) ?>
                                    <?php if (! empty($r['short_description'])): ?><div class="cs-muted small"><?= esc($r['short_description']) ?></div><?php endif; ?>
                                </td>
                                <?php if ($tab === 'packages'): ?>
                                    <td><?= ! empty($r['category']) ? esc(ucfirst($r['category']) . ' / ' . ucwords(str_replace('_', '-', (string) $r['variant']))) : '-' ?></td>
                                    <td class="text-nowrap"><?= $peso($r['base_price']) ?></td>
                                    <td><?= $r['stock_quantity'] === null ? '<span class="cs-muted">not tracked</span>' : (int) $r['stock_quantity'] ?></td>
                                <?php elseif ($tab === 'services'): ?>
                                    <td><?= esc($r['category'] ?? '-') ?></td>
                                    <td>
                                        <?php foreach ($r['routes'] as $route): ?>
                                            <div class="text-nowrap"><?= esc($route['origin']) ?> &rarr; <?= esc($route['destination']) ?>: <?= $peso($route['price']) ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                <?php else: ?>
                                    <td class="text-nowrap"><?= $r['pricing_method'] === 'range' ? $peso($r['min_price']) . ' &ndash; ' . $peso($r['max_price']) : $peso($r['price']) ?></td>
                                <?php endif; ?>
                                <td><?= cs_status($st) ?></td>
                                <td class="cs-table__actions">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('/admin/catalog/edit/' . $tab . '/' . $id) ?>">Edit</a>
                                    <form method="post" action="<?= site_url('/admin/catalog/availability/' . $tab . '/' . $id) ?>" class="d-inline"
                                          data-cs-confirm="<?= $st === 'archived' ? 'Restore' : 'Archive' ?> this item? Existing client records are not changed." data-cs-confirm-title="<?= $st === 'archived' ? 'Restore item' : 'Archive item' ?>" data-cs-confirm-ok="<?= $st === 'archived' ? 'Restore' : 'Archive' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="availability_status" value="<?= $st === 'archived' ? 'available' : 'archived' ?>">
                                        <button type="submit" class="btn btn-sm <?= $st === 'archived' ? 'btn-outline-primary' : 'btn-danger' ?>"><?= $st === 'archived' ? 'Restore' : 'Archive' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
