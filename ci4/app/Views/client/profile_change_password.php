<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Update password</h2></div>
            <form method="post" action="<?= base_url('client/profile/update-password') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="cs-panel__body">
                    <?php
                    $fields = [
                        ['current_password', 'Current password', 'Enter your current password', 'For your security, we need to verify your current password', false],
                        ['new_password', 'New password', 'Enter new password', 'Must be at least 8 characters long', true],
                        ['confirm_password', 'Confirm password', 'Confirm new password', 'Passwords must match', true],
                    ];
                    ?>
                    <?php foreach ($fields as $i => [$id, $label, $placeholder, $hint, $minLength]): ?>
                        <?php if ($id === 'new_password'): ?><hr class="cs-hairline my-4"><?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= $id ?>"><?= esc($label) ?> *</label>
                            <div class="input-group">
                                <input
                                    id="<?= $id ?>"
                                    name="<?= $id ?>"
                                    type="password"
                                    class="form-control <?= old($id) ? 'is-valid' : '' ?>"
                                    value="<?= esc(old($id, '')) ?>"
                                    required
                                    placeholder="<?= esc($placeholder, 'attr') ?>"
                                    <?= $minLength ? 'minlength="8"' : '' ?>>
                                <button class="btn btn-ghost cs-input-toggle" type="button" data-toggle-password="<?= $id ?>" aria-label="Show or hide <?= esc(strtolower($label), 'attr') ?>">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <small class="cs-muted d-block mt-1"><?= esc($hint) ?></small>
                        </div>
                    <?php endforeach; ?>

                    <?= view('components/alert', [
                        'type' => 'info',
                        'dismissible' => false,
                        'class' => 'mb-0',
                        'title' => 'Password requirements:',
                        'message' => 'At least 8 characters long, cannot contain your username, and a mix of letters, numbers, and symbols is recommended.',
                    ]) ?>
                </div>
                <div class="cs-panel__foot d-flex justify-content-end gap-2">
                    <a href="<?= base_url('client/profile') ?>" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Update password</button>
                </div>
            </form>
        </section>

        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title"><i class="ti ti-shield-check me-1"></i> Security tips</h2></div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php foreach ([
                    'Use a unique password you don\'t use elsewhere',
                    'Change your password regularly',
                    'Never share your password with anyone',
                    'Use a password manager to store your credentials safely',
                ] as $tip): ?>
                    <div class="cs-linkrow"><i class="ti ti-check text-success" aria-hidden="true"></i><span><?= esc($tip) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var field = document.getElementById(btn.getAttribute('data-toggle-password'));
        field.type = field.type === 'password' ? 'text' : 'password';
    });
});
</script>
<?= $this->endSection() ?>
