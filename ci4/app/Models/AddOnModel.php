<?php

namespace App\Models;

use CodeIgniter\Model;

class AddonModel extends Model
{
    protected $table            = 'addons';
    protected $primaryKey       = 'addon_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'item_code',
        'addon_name',
        'short_description',
        'description',
        'pricing_method',
        'price',
        'min_price',
        'max_price',
        'image_path',
        'availability_status',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];
}