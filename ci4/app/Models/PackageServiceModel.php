<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageServiceModel extends Model
{
    protected $table            = 'package_services';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'package_id',
        'service_list_id',
    ];
}
