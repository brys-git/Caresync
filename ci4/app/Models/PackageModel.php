<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageModel extends Model
{
    protected $table            = 'packages';
    protected $primaryKey       = 'package_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'package_name',
        'description',
        'base_price',
        'is_customizable',
        'is_available',
        'is_damayan_entitlement',
        'image_path',
        'item_code',
        'category',
        'variant',
        'short_description',
        'availability_status',
        'stock_quantity',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'status',
    ];
}
