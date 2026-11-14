<?php
/**
 * The one confirmation dialog for the whole app. Included once in the shell
 * layout and in the layouts that do not use it (sign-in card). public/assets/js/
 * caresync.js opens it for anything marked with data-cs-confirm:
 *
 *   data-cs-confirm="Message"          required - the question
 *   data-cs-confirm-title="Title"      optional - default "Please confirm"
 *   data-cs-confirm-ok="Button text"   optional - default "Confirm"
 *   data-cs-confirm-danger             optional - red style (also applied
 *                                      automatically when the button is btn-danger)
 *
 * It is a native <dialog> opened with showModal(), so focus stays inside it and
 * Esc cancels. If a page has no copy of this markup the script builds the same
 * thing itself.
 */
?>
<dialog class="cs-confirm" id="cs-confirm" aria-labelledby="cs-confirm-title" aria-describedby="cs-confirm-message">
    <form method="dialog" class="cs-confirm__box">
        <div class="cs-confirm__icon" data-cs-confirm-icon aria-hidden="true"><i class="ti ti-help-circle"></i></div>
        <h2 class="cs-confirm__title" id="cs-confirm-title" data-cs-confirm-title>Please confirm</h2>
        <p class="cs-confirm__message" id="cs-confirm-message" data-cs-confirm-message></p>
        <div class="cs-confirm__actions">
            <button type="button" class="btn btn-ghost" data-cs-confirm-cancel>Cancel</button>
            <button type="button" class="btn btn-primary" data-cs-confirm-accept>Confirm</button>
        </div>
    </form>
</dialog>
