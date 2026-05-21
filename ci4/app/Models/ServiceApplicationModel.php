<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceApplicationModel extends Model
{
    protected $table            = 'service_applications';
    protected $primaryKey       = 'application_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'plan_holder_id',
        'service_list_id',
        'package_id',
        'status',
        'deceased_name',
        'deceased_date_of_death',
        'deceased_address',
        'relationship_to_deceased',
        'beneficiary_name',
        'beneficiary_contact',
        'application_notes',
    ];
}
