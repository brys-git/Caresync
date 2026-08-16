<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center" style="padding: 60px 20px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #dcfce7; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <i class="mdi mdi-check-circle" style="font-size: 2.5rem; color: #16a34a;"></i>
            </div>
            <h2 style="font-weight: 800; margin-bottom: 12px;">Application Submitted!</h2>
            <p style="color: #64748b; font-size: 1rem; margin-bottom: 8px;">
                Your application for <strong><?= esc((string) ($item_name ?? 'the selected service')) ?></strong> has been submitted successfully.
            </p>
            <p style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 30px;">
                Your branch admin will review your application shortly. You will receive a notification once a decision has been made.
            </p>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 30px; text-align: left;">
                <h6 style="font-weight: 700; margin-bottom: 10px;">What happens next?</h6>
                <ul style="color: #64748b; font-size: 0.88rem; margin: 0; padding-left: 20px;">
                    <li style="margin-bottom: 6px;">Your branch admin will review your application</li>
                    <li style="margin-bottom: 6px;">You will be notified once a decision is made</li>
                    <li style="margin-bottom: 6px;">If approved, the service will be scheduled</li>
                    <li>You can track your application status in the Services page</li>
                </ul>
            </div>

            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= site_url('/client/service') ?>" class="btn btn-primary" style="background: #1e3a5f; border-color: #1e3a5f;">
                    <i class="mdi mdi-arrow-left me-1"></i> Back to Services
                </a>
                <a href="<?= site_url('/client/dashboard') ?>" class="btn btn-outline-secondary">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
