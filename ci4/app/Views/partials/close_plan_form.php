<?php
/**
 * Branch Admin: close a plan. A required reason, a confirmation, POST + CSRF; the server checks the branch again.
 * Expects: $plan_holder_id, $reasons (code => label), optional $back ('requests'), $can_close ({ok, message}).
 */
$can = $can_close ?? ['ok' => true, 'message' => ''];
?>
<?php if (! $can['ok']): ?>
    <p class="text-danger small mb-0"><i class="ti ti-lock" aria-hidden="true"></i> <?= esc((string) $can['message']) ?></p>
<?php else: ?>
    <form method="post" action="<?= site_url('branch-admin/client-management/close/' . (int) $plan_holder_id) ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <?php if (! empty($back)): ?><input type="hidden" name="back" value="<?= esc((string) $back, 'attr') ?>"><?php endif; ?>
        <div class="col-md-4">
            <label class="form-label small" for="reason_code_<?= (int) $plan_holder_id ?>">Reason <span class="text-danger">*</span></label>
            <select id="reason_code_<?= (int) $plan_holder_id ?>" name="reason_code" class="form-select form-select-sm" required>
                <option value="">Select a reason</option>
                <?php foreach ($reasons as $code => $label): ?>
                    <option value="<?= esc($code, 'attr') ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small" for="note_<?= (int) $plan_holder_id ?>">Note (needed for "Other")</label>
            <input id="note_<?= (int) $plan_holder_id ?>" name="note" class="form-control form-control-sm" maxlength="200">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-danger btn-sm" data-cs-confirm="Close this plan? No more contributions will be due and the client is taken out of the collection lists. Every record is kept.">Close plan</button>
        </div>
    </form>
<?php endif; ?>
