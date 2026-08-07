<?php
    $recordId = (int) $record['import_record_id'];
    $status = (string) ($record['record_status'] ?? 'ready');
    $decision = (string) ($record['admin_decision'] ?? 'pending');
    $fullName = trim((string) ($record['first_name'] ?? '') . ' ' . (string) ($record['last_name'] ?? '')) ?: 'Unnamed record';

    $chipClass = match ($status) {
        'ready' => 'ci-chip--ready',
        'needs_attention' => 'ci-chip--needs_attention',
        'duplicate' => 'ci-chip--duplicate',
        'skip' => 'ci-chip--skip',
        default => 'ci-chip--ready',
    };

    $blocking = $record['has_blocking_errors'] ?? false;
    $matchCandidates = $record['match']['candidates'] ?? [];
    $matchStatus = (string) ($record['match']['status'] ?? 'ready');
    $matchInformational = $record['match']['informational'] ?? [];
    $validationErrors = $record['validation_errors'] ?? [];
    $beneficiaries = $record['beneficiaries'] ?? [];
    $optional = $record['optional'] ?? [];
    $plan = $record['plan'] ?? [];
    $addressProvince = $record['address_province'] ?? '';
?>

<article class="ci-record ci-record--<?= esc($status) ?>" data-record-id="<?= $recordId ?>" data-status="<?= esc($status) ?>" data-decision="<?= esc($decision) ?>">
    <header class="ci-record__head">
        <div class="ci-record__index">#<?= (int) ($record['source_index'] ?? 0) ?></div>
        <div class="ci-record__name">
            <span class="ci-record__name-text"><?= esc($fullName) ?></span>
            <div class="ci-record__sub">DOB: <?= esc((string) ($record['date_of_birth'] ?? '-')) ?: '-' ?> · Coordinator: <?= esc((string) ($record['coordinator'] ?? '-')) ?: '-' ?></div>
        </div>

        <span class="ci-chip <?= $chipClass ?> ci-status-chip"><?= esc(str_replace('_', ' ', ucwords($status, '_'))) ?></span>
        <?php if ($decision !== 'pending'): ?>
            <span class="ci-chip ci-chip--decided"><?= esc(str_replace('_', ' ', ucwords($decision, '_'))) ?></span>
        <?php endif; ?>

        <div class="ci-record__actions">
            <select class="ci-decision so-form-select" style="width:auto;min-width:170px;" aria-label="Decision">
                <option value="pending" <?= $decision === 'pending' ? 'selected' : '' ?>>— Choose action —</option>
                <option value="create_new" <?= $decision === 'create_new' ? 'selected' : '' ?>>Create new account</option>
                <option value="link_existing" <?= $decision === 'link_existing' ? 'selected' : '' ?>>Link to existing client</option>
                <option value="skip" <?= $decision === 'skip' ? 'selected' : '' ?>>Skip / don't import</option>
            </select>
            <button type="button" class="ci-toggle ci-save"><i class="mdi mdi-content-save-outline"></i> Save</button>
            <button type="button" class="ci-toggle ci-toggle-raw"><i class="mdi mdi-file-search-outline"></i> Source</button>
        </div>
    </header>

    <div class="ci-record__body">

        <!-- Client details -->
        <div class="ci-panel">
            <div class="ci-panel__title"><i class="mdi mdi-account-details-outline"></i> Client details</div>
            <div class="ci-form-grid">
                <div class="ci-field">
                    <label>First name</label>
                    <input type="text" name="first_name" value="<?= esc($record['first_name'] ?? '') ?>" <?= $blocking ? 'class="ci-invalid"' : '' ?>>
                </div>
                <div class="ci-field">
                    <label>Middle name</label>
                    <input type="text" name="middle_name" value="<?= esc($record['middle_name'] ?? '') ?>">
                </div>
                <div class="ci-field">
                    <label>Last name</label>
                    <input type="text" name="last_name" value="<?= esc($record['last_name'] ?? '') ?>" <?= $blocking ? 'class="ci-invalid"' : '' ?>>
                </div>
                <div class="ci-field">
                    <label>Name extension</label>
                    <input type="text" name="name_extension" value="<?= esc($record['name_extension'] ?? '') ?>" placeholder="Jr. / Sr. / III">
                </div>
                <div class="ci-field">
                    <label>Date of birth</label>
                    <input type="text" name="date_of_birth" value="<?= esc($record['date_of_birth'] ?? '') ?>" placeholder="MM-DD-YYYY">
                </div>
                <div class="ci-field">
                    <label>Application date</label>
                    <input type="text" name="application_date" value="<?= esc($record['application_date'] ?? '') ?>" placeholder="MM-DD-YYYY">
                </div>
                <div class="ci-field ci-field--span2">
                    <label>Coordinator</label>
                    <input type="text" name="coordinator" value="<?= esc($record['coordinator'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top:12px;">
                <label class="so-form-label">Address (raw)</label>
                <textarea name="address_raw" class="so-form-textarea" style="min-height:52px;"><?= esc($record['address_raw'] ?? '') ?></textarea>
                <div class="ci-form-grid" style="margin-top:10px;">
                    <div class="ci-field"><label>No.</label><input type="text" name="address_no" value="<?= esc($record['address_no'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Street</label><input type="text" name="address_street" value="<?= esc($record['address_street'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Barangay</label><input type="text" name="address_barangay" value="<?= esc($record['address_barangay'] ?? '') ?>"></div>
                    <div class="ci-field"><label>City / Municipality</label><input type="text" name="address_city" value="<?= esc($record['address_city'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Province (not saved to DB)</label><input type="text" name="address_province" value="<?= esc($addressProvince) ?>"></div>
                </div>
            </div>

            <div class="ci-raw" hidden><?= esc((string) ($record['extracted_text'] ?? '')) ?></div>
        </div>

        <!-- Beneficiaries -->
        <div class="ci-panel">
            <div class="ci-panel__title"><i class="mdi mdi-account-group-outline"></i> Beneficiaries</div>
            <div style="overflow-x:auto;">
                <table class="ci-benef-table">
                    <thead>
                        <tr>
                            <th style="width:22px;"></th>
                            <th>First</th>
                            <th>Middle</th>
                            <th>Last</th>
                            <th style="width:56px;">Ext</th>
                            <th>Birthday</th>
                            <th>Relation</th>
                            <th style="width:30px;"></th>
                        </tr>
                    </thead>
                    <tbody class="ci-benef-rows">
                        <?php if ($beneficiaries === []): ?>
                            <tr class="ci-benef-empty"><td colspan="8" class="ci-none">No beneficiaries — add at least one before committing.</td></tr>
                        <?php else: ?>
                            <?php foreach ($beneficiaries as $i => $ben): ?>
                                <tr class="ci-benef-row">
                                    <td class="ci-benef-idx"><?= $i + 1 ?></td>
                                    <td><input type="text" name="beneficiaries[<?= $i ?>][first_name]" value="<?= esc($ben['first_name'] ?? '') ?>"></td>
                                    <td><input type="text" name="beneficiaries[<?= $i ?>][middle_name]" value="<?= esc($ben['middle_name'] ?? '') ?>"></td>
                                    <td><input type="text" name="beneficiaries[<?= $i ?>][last_name]" value="<?= esc($ben['last_name'] ?? '') ?>"></td>
                                    <td><input type="text" name="beneficiaries[<?= $i ?>][name_extension]" value="<?= esc($ben['name_extension'] ?? '') ?>"></td>
                                    <td><input type="text" name="beneficiaries[<?= $i ?>][birthday_raw]" value="<?= esc($ben['birthday_raw'] ?? $ben['date_of_birth'] ?? '') ?>" placeholder="MM-DD-YYYY"></td>
                                    <td><input type="text" name="beneficiaries[<?= $i ?>][relationship]" value="<?= esc($ben['relationship'] ?? '') ?>"></td>
                                    <td><button type="button" class="ci-benef-remove" title="Remove"><i class="mdi mdi-close"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="ci-toggle ci-benef-add" style="margin-top:10px;"><i class="mdi mdi-plus"></i> Add beneficiary</button>
        </div>

        <!-- Validation -->
        <div class="ci-panel">
            <div class="ci-panel__title"><i class="mdi mdi-alert-circle-outline"></i> Validation</div>
            <ul class="ci-issues">
                <?php $hasIssues = false; ?>
                <?php foreach ($validationErrors as $issue): $hasIssues = true; ?>
                    <li class="ci-issue ci-issue--<?= ($issue['level'] ?? 'warning') === 'error' ? 'error' : 'warning' ?>">
                        <i class="mdi <?= ($issue['level'] ?? '') === 'error' ? 'mdi-close-circle-outline' : 'mdi-alert-outline' ?>"></i>
                        <span><?= esc($issue['message'] ?? '') ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (! $hasIssues): ?>
                    <li class="ci-none"><i class="mdi mdi-check-circle-outline"></i> No validation issues.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Match panel -->
        <div class="ci-panel">
            <div class="ci-panel__title"><i class="mdi mdi-account-search-outline"></i> Duplicate match</div>
            <?php if ($matchCandidates === []): ?>
                <div class="ci-none">No similar existing client found.</div>
            <?php else: ?>
                <div class="ci-match-list">
                    <?php foreach ($matchCandidates as $candidate): ?>
                        <?php $score = (float) ($candidate['score'] ?? 0); ?>
                        <label class="ci-match">
                            <input type="radio" name="link_target" value="<?= (int) ($candidate['id'] ?? 0) ?>"
                                   data-source="<?= esc($candidate['source'] ?? '') ?>"
                                   data-name="<?= esc($candidate['full_name'] ?? '') ?>"
                                   <?= (int) ($record['linked_plan_holder_id'] ?? 0) === (int) ($candidate['id'] ?? 0) ? 'checked' : '' ?>>
                            <span class="ci-match__body">
                                <span class="ci-match__name"><?= esc($candidate['full_name'] ?? '-') ?></span>
                                <span class="ci-match__meta">
                                    <?= $candidate['source'] === 'batch' ? 'Earlier in this document' : 'Existing registered client' ?>
                                    <?php if (! empty($candidate['date_of_birth'])): ?> · DOB <?= esc($candidate['date_of_birth']) ?><?php endif; ?>
                                </span>
                                <div class="ci-match__reason"><i class="mdi mdi-information-outline"></i> <?= esc($candidate['reason'] ?? '') ?></div>
                                <div style="display:flex;align-items:center;">
                                    <div class="ci-score"><div class="ci-score__bar" style="width:<?= (int) round($score * 100) ?>%;"></div></div>
                                    <span class="ci-score__label"><?= number_format($score * 100, 0) ?>%</span>
                                </div>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="ci-none" style="padding-top:8px;">
                    Choosing "Link to existing client" above + picking a candidate records the linkage without creating a new account.
                </div>
            <?php endif; ?>

            <?php if ($matchInformational !== []): ?>
                <div class="ci-info-list">
                    <?php foreach ($matchInformational as $note): ?>
                        <div class="ci-info-note">
                            <i class="mdi mdi-information-outline"></i>
                            <span><?= esc($note['text'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Optional / plan -->
        <div class="ci-panel" style="grid-column:1 / -1;">
            <div class="ci-panel__title"><i class="mdi mdi-cog-outline"></i> Optional &amp; membership details <button type="button" class="ci-toggle ci-toggle-optional" style="margin-left:auto;padding:3px 10px;">Show</button></div>
            <div class="ci-optional-body" hidden>
                <div class="ci-form-grid">
                    <div class="ci-field"><label>Contact number</label><input type="text" name="contact_number" value="<?= esc($optional['contact_number'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Email</label><input type="text" name="email" value="<?= esc($optional['email'] ?? '') ?>"></div>
                    <div class="ci-field">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">—</option>
                            <option value="Male" <?= ($optional['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($optional['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="ci-field">
                        <label>Civil status</label>
                        <select name="civil_status">
                            <option value="">—</option>
                            <?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $cs): ?>
                                <option value="<?= $cs ?>" <?= ($optional['civil_status'] ?? '') === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ci-field"><label>Citizenship</label><input type="text" name="citizenship" value="<?= esc($optional['citizenship'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Place of birth</label><input type="text" name="place_of_birth" value="<?= esc($optional['place_of_birth'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Senior citizen ID</label><input type="text" name="senior_citizen_id" value="<?= esc($optional['senior_citizen_id'] ?? '') ?>"></div>
                    <div class="ci-field"><label>ID control no.</label><input type="text" name="id_control_no" value="<?= esc($optional['id_control_no'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Emergency contact name</label><input type="text" name="emergency_contact_name" value="<?= esc($optional['emergency_contact_name'] ?? '') ?>"></div>
                    <div class="ci-field"><label>Emergency contact no.</label><input type="text" name="emergency_contact_number" value="<?= esc($optional['emergency_contact_number'] ?? '') ?>"></div>
                    <div class="ci-field ci-field--span2"><label>Emergency contact address</label><input type="text" name="emergency_contact_address" value="<?= esc($optional['emergency_contact_address'] ?? '') ?>"></div>

                    <div class="ci-field">
                        <label>Plan status</label>
                        <select name="plan_status">
                            <?php foreach (['active', 'inactive', 'completed'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= ($plan['plan_status'] ?? 'active') === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ci-field"><label>Monthly fee</label><input type="number" step="0.01" name="monthly_fee" value="<?= esc($plan['monthly_fee'] ?? 240) ?>"></div>
                    <div class="ci-field ci-field--span2">
                        <label>Package</label>
                        <select name="package_id">
                            <?php foreach ($packages ?? [] as $pkg): ?>
                                <option value="<?= (int) $pkg['package_id'] ?>" <?= (int) ($plan['package_id'] ?? 1) === (int) $pkg['package_id'] ? 'selected' : '' ?>><?= esc($pkg['package_name'] ?? ('Package #' . $pkg['package_id'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="ci-link-row" style="margin-top:12px;">
                <span class="ci-cred"><i class="mdi mdi-key-outline"></i> <?= esc($record['temp_username'] ?? '…') ?> · <span class="ci-temp-email"><?= esc($record['temp_email'] ?? '…') ?></span></span>
                <span class="ci-none">Temporary password is generated when you commit.</span>
            </div>
        </div>

    </div>
</article>
