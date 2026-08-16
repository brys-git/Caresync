<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanHolderModel extends Model
{
    protected $table            = 'plan_holders';
    protected $primaryKey       = 'plan_holder_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'user_id',
        'id_control_no',
        'coordinator',
        'coordinator_user_id',
        'id_document_path',
        'id_type',
        'id_number',
        'id_match_score',
        'id_verification_status',
        'id_verified_at',
        'id_verified_by',
        'application_date',
        'address_no',
        'address_street',
        'address_province',
        'address_barangay',
        'address_city',
        'date_of_birth',
        'place_of_birth',
        'age',
        'gender',
        'civil_status',
        'citizenship',
        'height',
        'weight',
        'spouse_name',
        'spouse_birthdate',
        'spouse_occupation',
        'senior_citizen_id',
        'organization_affiliation',
        'emergency_contact_name',
        'emergency_contact_number',
        'emergency_contact_address',
        'branch_id',
        'status',
        'is_linked_account',
        'unique_identifier',
        'is_deceased',
        'date_of_death',
        'damayan_benefit_activated',
        'damayan_benefit_activation_date',
        'waived_contribution_amount',
        'waiver_date',
        'waiver_reason',
    ];
}
