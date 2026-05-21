<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Client Management</h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link active" 
                        id="holders-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#holders-panel" 
                        type="button" 
                        role="tab" 
                        aria-controls="holders-panel" 
                        aria-selected="true"
                    >
                        Branch Plan Holders
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link" 
                        id="register-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#register-panel" 
                        type="button" 
                        role="tab" 
                        aria-controls="register-panel" 
                        aria-selected="false"
                    >
                        Register Plan Holder
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="link-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#link-panel"
                        type="button"
                        role="tab"
                        aria-controls="link-panel"
                        aria-selected="false"
                    >
                        Link Existing Account
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content pt-3" id="clientTabsContent">
                <!-- TAB 1: Branch Plan Holders -->
                <div 
                    class="tab-pane fade show active" 
                    id="holders-panel" 
                    role="tabpanel" 
                    aria-labelledby="holders-tab" 
                    tabindex="0"
                >
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">View and manage all registered plan holders in your branch</h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No plan holders found for this branch.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td>
                                            <a 
                                                href="<?= base_url('branch-admin/client/view/' . $client['plan_holder_id']) ?>" 
                                                class="text-decoration-none fw-semibold"
                                            >
                                                <?= esc($client['first_name'] . ' ' . $client['last_name']) ?>
                                            </a>
                                        </td>
                                        <td><?= esc((string) ($client['email'] ?? '-')) ?></td>
                                        <td><?= esc((string) ($client['contact_number'] ?? '-')) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= $client['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= esc(ucfirst((string) $client['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a 
                                                href="<?= base_url('branch-admin/client/edit/' . $client['plan_holder_id']) ?>" 
                                                class="btn btn-sm btn-outline-primary"
                                                title="Edit client"
                                            >
                                                Edit
                                            </a>
                                            <a 
                                                href="<?= base_url('branch-admin/client/view/' . $client['plan_holder_id']) ?>" 
                                                class="btn btn-sm btn-primary"
                                                title="View full details"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: Register Plan Holder -->
                <div 
                    class="tab-pane fade" 
                    id="register-panel" 
                    role="tabpanel" 
                    aria-labelledby="register-tab" 
                    tabindex="0"
                >
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Register a new plan holder in your branch</h5>
                    </div>

                    <div class="alert alert-info mb-3">
                        <strong>Heads up!</strong> You can register a walk-in client or link an existing user account to a new plan holder.
                    </div>

                    <!-- Quick Form - Minimal fields for registration -->
                    <form method="post" action="<?= base_url('branch-admin/client/store') ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="client_account_mode" value="new">

                        <div class="row g-3">
                            <!-- Basic Personal Info -->
                            <div class="col-md-6">
                                <label for="quick_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="quick_first_name" 
                                    name="first_name" 
                                    required
                                >
                            </div>
                            <div class="col-md-6">
                                <label for="quick_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="quick_last_name" 
                                    name="last_name" 
                                    required
                                >
                            </div>
                            <div class="col-md-6">
                                <label for="quick_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="quick_email" 
                                    name="email" 
                                    required
                                >
                            </div>
                            <div class="col-md-6">
                                <label for="quick_contact" class="form-label">Contact Number</label>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    id="quick_contact" 
                                    name="contact_number"
                                >
                            </div>

                            <!-- City/Address -->
                            <div class="col-md-6">
                                <label for="quick_city" class="form-label">City/Municipality</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="quick_city" 
                                    name="address_city"
                                >
                            </div>
                            <div class="col-md-6">
                                <label for="quick_barangay" class="form-label">Barangay</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="quick_barangay" 
                                    name="address_barangay"
                                >
                            </div>

                            <!-- Membership Program -->
                            <div class="col-md-6">
                                <label class="form-label">Program</label>
                                <div class="form-control-plaintext fw-semibold">
                                    <?= esc((string) (($program['name'] ?? '') ?: 'Damayan Burial Program')) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Fee</label>
                                <div class="form-control-plaintext fw-semibold">
                                    P<?= number_format((float) ($program['monthly_fee'] ?? 240), 2) ?>
                                </div>
                            </div>

                            <!-- Hidden Fields for unused optional fields -->
                            <input type="hidden" name="middle_name" value="">
                            <input type="hidden" name="date_of_birth" value="">
                            <input type="hidden" name="place_of_birth" value="">
                            <input type="hidden" name="age" value="">
                            <input type="hidden" name="gender" value="">
                            <input type="hidden" name="civil_status" value="">
                            <input type="hidden" name="citizenship" value="">
                            <input type="hidden" name="height" value="">
                            <input type="hidden" name="weight" value="">
                            <input type="hidden" name="address_no" value="">
                            <input type="hidden" name="address_street" value="">
                            <input type="hidden" name="spouse_name" value="">
                            <input type="hidden" name="spouse_birthdate" value="">
                            <input type="hidden" name="spouse_occupation" value="">
                            <input type="hidden" name="senior_citizen_id" value="">
                            <input type="hidden" name="organization_affiliation" value="">
                        </div>

                        <div class="d-flex gap-2 mt-4 justify-content-end">
                            <a href="<?= base_url('branch-admin/client') ?>" class="btn btn-outline-secondary">Clear</a>
                            <a 
                                href="<?= base_url('branch-admin/client/create') ?>" 
                                class="btn btn-outline-info me-2"
                                title="Open full registration form"
                            >
                                Full Form
                            </a>
                            <button type="submit" class="btn btn-success">Quick Register</button>
                        </div>

                        <div class="alert alert-light mt-3">
                            <small class="text-muted">
                                <strong>Quick Register:</strong> Minimal fields for fast registration. <br>
                                <strong>Full Form:</strong> Complete form for detailed information including personal details, address, beneficiaries, and more.
                            </small>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: Link Existing Account -->
                <div
                    class="tab-pane fade"
                    id="link-panel"
                    role="tabpanel"
                    aria-labelledby="link-tab"
                    tabindex="0"
                >
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Link Existing User Account to Plan Holder</h5>
                    </div>

                    <div class="alert alert-info mb-3">
                        Enter an existing client email to link the account and register it under the Damayan Burial Program.
                    </div>

                    <form method="post" action="<?= base_url('branch-admin/client/store') ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="client_account_mode" value="existing">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="link_email" class="form-label">Existing Account Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="link_email" name="email" required>
                                <small class="text-muted">Must be an existing account that is not yet linked as a plan holder.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Program</label>
                                <div class="form-control-plaintext fw-semibold">
                                    <?= esc((string) (($program['name'] ?? '') ?: 'Damayan Burial Program')) ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Monthly Fee</label>
                                <div class="form-control-plaintext fw-semibold">
                                    P<?= number_format((float) ($program['monthly_fee'] ?? 240), 2) ?>
                                </div>
                            </div>

                            <input type="hidden" name="first_name" value="">
                            <input type="hidden" name="middle_name" value="">
                            <input type="hidden" name="last_name" value="">
                            <input type="hidden" name="contact_number" value="">
                            <input type="hidden" name="date_of_birth" value="">
                            <input type="hidden" name="place_of_birth" value="">
                            <input type="hidden" name="age" value="">
                            <input type="hidden" name="gender" value="">
                            <input type="hidden" name="civil_status" value="">
                            <input type="hidden" name="citizenship" value="">
                            <input type="hidden" name="height" value="">
                            <input type="hidden" name="weight" value="">
                            <input type="hidden" name="address_no" value="">
                            <input type="hidden" name="address_street" value="">
                            <input type="hidden" name="address_barangay" value="">
                            <input type="hidden" name="address_city" value="">
                            <input type="hidden" name="spouse_name" value="">
                            <input type="hidden" name="spouse_birthdate" value="">
                            <input type="hidden" name="spouse_occupation" value="">
                            <input type="hidden" name="senior_citizen_id" value="">
                            <input type="hidden" name="organization_affiliation" value="">
                        </div>

                        <div class="d-flex gap-2 mt-4 justify-content-end">
                            <button type="submit" class="btn btn-primary">Link Account and Register Plan Holder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

