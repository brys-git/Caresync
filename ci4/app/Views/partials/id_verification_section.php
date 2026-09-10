<?php
/**
 * Shared "Government ID Verification" section - upload/camera-capture UI
 * backed by public/assets/js/id-verification-widget.js. Used by every
 * CareSync registration form that creates or links a plan holder account
 * on someone else's behalf (plan_holders/register.php, branch_admin/
 * clients/register.php, staff/clients/register.php) so this markup only
 * has to exist once.
 *
 * Expects (via $this->setData() before $this->include(), since CI4's
 * include() does not inherit the caller's local view variables):
 *   $id_types            array<string,string> value => label
 *   $pending_field_id     id of the hidden input the pending token gets
 *                          written into (default 'government_id_pending_token')
 */
$idTypes = $id_types ?? [];
$pendingFieldId = $pending_field_id ?? 'government_id_pending_token';
?>
<div class="card">
    <div class="card-body">
        <h3 class="h6 text-primary mb-1">Government ID Verification</h3>
        <p class="text-muted small mb-3">Upload or take a photo of the applicant's valid government-issued ID so it can be confirmed against the name entered.</p>

        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label" for="id_type">Government ID Type</label>
                <select id="id_type" class="form-select">
                    <option value="">Select ID type</option>
                    <?php foreach ($idTypes as $value => $label): ?>
                        <option value="<?= esc((string) $value) ?>"><?= esc((string) $label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnChooseUpload"><i class="ti ti-upload me-1"></i>Upload Government ID</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnChooseCamera"><i class="ti ti-camera me-1"></i>Take Photo</button>
            <input type="file" id="idFileInput" accept="image/jpeg,image/png,image/webp" class="d-none">
        </div>

        <div id="cameraPanel" class="d-none mb-3">
            <div class="id-verify-camera-frame mb-2">
                <video id="cameraVideo" autoplay playsinline muted></video>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="btnCapture"><i class="ti ti-camera me-1"></i>Capture</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelCamera">Cancel</button>
            </div>
            <p class="text-danger small mt-2 d-none" id="cameraError"></p>
        </div>

        <div id="idPreviewWrap" class="d-none mb-3">
            <img id="idPreviewImg" alt="ID preview" class="id-verify-preview">
            <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRetake"><i class="ti ti-refresh me-1"></i>Retake / Choose Another</button>
            </div>
        </div>

        <canvas id="captureCanvas" class="d-none"></canvas>

        <div class="d-flex align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-success" id="btnVerifyId" disabled>Verify ID</button>
            <span class="text-muted small" id="verifyHint">Choose or capture an image first.</span>
        </div>

        <div id="verificationResult" class="d-none"></div>

        <input type="hidden" name="government_id_pending_token" id="<?= esc($pendingFieldId) ?>" value="">
    </div>
</div>

<style>
    .id-verify-camera-frame { max-width: 480px; background: #000; border-radius: 8px; overflow: hidden; }
    .id-verify-camera-frame video { width: 100%; display: block; }
    .id-verify-preview { max-width: 480px; max-height: 320px; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: contain; }
</style>
