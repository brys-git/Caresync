<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceCostModel extends Model
{
    protected $table            = 'service_costs';
    protected $primaryKey       = 'cost_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'service_id',
        'description',
        'amount',
    ];
}
