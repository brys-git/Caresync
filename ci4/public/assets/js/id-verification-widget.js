/**
 * Reusable Government ID Verification widget - upload-or-camera-capture,
 * preview, verify (AJAX), and a VERIFIED/NEEDS REVIEW/FAILED result
 * display. Used by every CareSync registration flow that needs this step
 * (client/plan_registration.php first; users/create.php next) so the
 * upload/camera/matching UX only has to be built once. If the
 * verification provider or endpoint shape ever changes, only
 * GovernmentIdVerificationService/IdVerificationController (server side)
 * need to change - this widget just posts an image + a bit of form data
 * and renders whatever status/message comes back.
 *
 * Usage:
 *   initIdVerificationWidget({
 *     endpoint: '/api/id-verification/verify',
 *     csrfName: 'csrf_test_name', csrfValue: '...',
 *     getIdentity: () => ({ first_name, middle_name, last_name, date_of_birth }),
 *     resultFieldId: 'government_id_verification_id', // hidden field the
 *       AJAX response's id/token gets written into
 *     resultFieldKey: 'verification_id' | 'pending_token', // which key
 *       of the JSON response to write into resultFieldId
 *     initialStatus: null|'verified'|'needs_review'|'failed', // to show a
 *       "previous attempt on file" banner on page load, if any
 *   });
 */
function initIdVerificationWidget(config) {
    const idTypeSelect = document.getElementById('id_type');
    const idFileInput = document.getElementById('idFileInput');
    const btnChooseUpload = document.getElementById('btnChooseUpload');
    const btnChooseCamera = document.getElementById('btnChooseCamera');
    const cameraPanel = document.getElementById('cameraPanel');
    const cameraVideo = document.getElementById('cameraVideo');
    const cameraError = document.getElementById('cameraError');
    const btnCapture = document.getElementById('btnCapture');
    const btnCancelCamera = document.getElementById('btnCancelCamera');
    const captureCanvas = document.getElementById('captureCanvas');
    const idPreviewWrap = document.getElementById('idPreviewWrap');
    const idPreviewImg = document.getElementById('idPreviewImg');
    const btnRetake = document.getElementById('btnRetake');
    const btnVerifyId = document.getElementById('btnVerifyId');
    const verifyHint = document.getElementById('verifyHint');
    const verificationResult = document.getElementById('verificationResult');
    const resultField = document.getElementById(config.resultFieldId);

    if (!idTypeSelect || !btnVerifyId) {
        return null;
    }

    let selectedBlob = null;
    let cameraStream = null;
    let attempted = !!config.initialStatus;
    let lastStatus = config.initialStatus || null;

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function resetPreview() {
        selectedBlob = null;
        idPreviewWrap.classList.add('d-none');
        idPreviewImg.src = '';
        btnVerifyId.disabled = true;
        verifyHint.textContent = 'Choose or capture an image first.';
        verifyHint.classList.remove('text-danger');
    }

    function setPreview(blob) {
        selectedBlob = blob;
        idPreviewImg.src = URL.createObjectURL(blob);
        idPreviewWrap.classList.remove('d-none');
        cameraPanel.classList.add('d-none');
        stopCamera();
        btnVerifyId.disabled = false;
        verifyHint.textContent = 'Ready to verify.';
        verifyHint.classList.remove('text-danger');
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(function (track) { track.stop(); });
            cameraStream = null;
        }
    }

    function showResult(status, message) {
        const map = {
            verified: { cls: 'alert-success', icon: 'ti-circle-check', title: 'Government ID Verified' },
            needs_review: { cls: 'alert-warning', icon: 'ti-alert-triangle', title: 'Verification Needs Review' },
            failed: { cls: 'alert-danger', icon: 'ti-circle-x', title: 'Verification Failed' },
        };
        const info = map[status] || map.needs_review;
        verificationResult.className = 'alert ' + info.cls;
        verificationResult.innerHTML = '<i class="ti ' + info.icon + ' me-1"></i><strong>' + info.title + '</strong><div class="small mt-1">' + esc(message || '') + '</div>';
        verificationResult.classList.remove('d-none');
        attempted = true;
        lastStatus = status;
        verifyHint.classList.remove('text-danger');
        verifyHint.textContent = 'You can retake and verify again if needed.';
    }

    if (config.initialStatus) {
        showResult(config.initialStatus, 'A previous verification attempt is on file. You can retake or re-verify if needed.');
    }

    btnChooseUpload.addEventListener('click', function () {
        idFileInput.click();
    });

    idFileInput.addEventListener('change', function () {
        if (idFileInput.files && idFileInput.files[0]) {
            setPreview(idFileInput.files[0]);
        }
    });

    btnChooseCamera.addEventListener('click', function () {
        cameraError.classList.add('d-none');
        cameraPanel.classList.remove('d-none');
        idPreviewWrap.classList.add('d-none');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            cameraError.textContent = 'Camera is not available on this browser/device. Please upload a file instead.';
            cameraError.classList.remove('d-none');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function (stream) {
                cameraStream = stream;
                cameraVideo.srcObject = stream;
            })
            .catch(function () {
                cameraError.textContent = 'Camera access was denied or is unavailable. Please upload a file instead.';
                cameraError.classList.remove('d-none');
                cameraPanel.classList.add('d-none');
            });
    });

    btnCancelCamera.addEventListener('click', function () {
        stopCamera();
        cameraPanel.classList.add('d-none');
    });

    btnCapture.addEventListener('click', function () {
        if (!cameraVideo.videoWidth) {
            return;
        }
        captureCanvas.width = cameraVideo.videoWidth;
        captureCanvas.height = cameraVideo.videoHeight;
        captureCanvas.getContext('2d').drawImage(cameraVideo, 0, 0);
        captureCanvas.toBlob(function (blob) {
            if (blob) {
                setPreview(blob);
            }
        }, 'image/jpeg', 0.92);
    });

    btnRetake.addEventListener('click', function () {
        resetPreview();
        idFileInput.value = '';
    });

    btnVerifyId.addEventListener('click', function () {
        if (!selectedBlob) {
            return;
        }
        if (!idTypeSelect.value) {
            idTypeSelect.focus();
            return;
        }

        btnVerifyId.disabled = true;
        btnVerifyId.textContent = 'Verifying...';
        verificationResult.classList.add('d-none');

        const identity = config.getIdentity();
        const formData = new FormData();
        formData.append('id_image', selectedBlob, 'id_image.jpg');
        formData.append('id_type', idTypeSelect.value);
        formData.append('first_name', identity.first_name || '');
        formData.append('middle_name', identity.middle_name || '');
        formData.append('last_name', identity.last_name || '');
        formData.append('date_of_birth', identity.date_of_birth || '');
        formData.append(config.csrfName, config.csrfValue);

        fetch(config.endpoint, { method: 'POST', body: formData })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                btnVerifyId.disabled = false;
                btnVerifyId.textContent = 'Verify ID';

                if (!result.ok && !result.data.status) {
                    showResult('needs_review', result.data.error || 'Unable to verify your ID right now. Please try again.');
                    return;
                }

                if (resultField) {
                    resultField.value = result.data[config.resultFieldKey] || '';
                }
                showResult(result.data.status, result.data.message);
            })
            .catch(function () {
                btnVerifyId.disabled = false;
                btnVerifyId.textContent = 'Verify ID';
                showResult('needs_review', 'Unable to reach the verification service. Please check your connection and try again.');
            });
    });

    return {
        wasAttempted: function () { return attempted; },
        getStatus: function () { return lastStatus; },
    };
}
