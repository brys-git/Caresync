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
        'registered_by',
        'coordinator',
        'application_date',
        'address_no',
        'address_street',
        'address_barangay',
        'barangay_code',
        'address_city',
        'city_municipality_code',
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
        'emergency_contact_relationship',
        'emergency_contact_number',
        'emergency_contact_address',
        'branch_id',
        'status',
        'is_linked_account',
        'unique_identifier',
    ];

    // id_control_no is NOT an allowed field: nobody types it. It is handed out by IdControlService
    // the moment a plan holder becomes active (registration approved, or created already active).
    protected $afterInsert = ['numberOnApproval'];
    protected $afterUpdate = ['numberOnApproval'];

    /** @param array{id:mixed, data:array<string,mixed>} $event */
    protected function numberOnApproval(array $event): array
    {
        if (($event['data']['status'] ?? null) === 'active') {
            $ids = $event['id'] ?? [];
            foreach ((array) $ids as $id) {
                if ((int) $id > 0) {
                    (new \App\Services\IdControlService())->assign((int) $id);
                }
            }
        }

        return $event;
    }
}
