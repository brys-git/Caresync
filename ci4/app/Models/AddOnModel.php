<?php

namespace App\Models;

use CodeIgniter\Model;

class AddOnModel extends Model
{
    protected $table            = 'add_ons';
    protected $primaryKey       = 'add_on_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'service_id',
        'item_name',
        'price',
    ];
}
