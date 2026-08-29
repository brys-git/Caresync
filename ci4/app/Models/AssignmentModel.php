<?php

namespace App\Models;

use CodeIgniter\Model;

class AssignmentModel extends Model
{
    protected $table            = 'assignments';
    protected $primaryKey       = 'assignment_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'service_id',
        'staff_id',
        'assigned_date',
    ];
}
