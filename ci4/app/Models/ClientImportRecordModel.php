<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientImportRecordModel extends Model
{
    protected $table            = 'client_import_records';
    protected $primaryKey       = 'import_record_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'import_batch_id',
        'source_index',
        'coordinator',
        'application_date',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'date_of_birth',
        'address_raw',
        'address_no',
        'address_street',
        'address_barangay',
        'address_city',
        'mapped_data',
        'beneficiaries_json',
        'extracted_text',
        'validation_errors_json',
        'match_candidates_json',
        'duplicate_key',
        'record_status',
        'admin_decision',
        'linked_user_id',
        'linked_plan_holder_id',
        'created_user_id',
        'created_plan_holder_id',
        'created_plan_id',
        'temp_username',
        'temp_email',
        'temp_password_hash',
        'temp_password_plain',
        'committed_at',
        'committed_by',
    ];
}
