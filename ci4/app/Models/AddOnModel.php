<?php

namespace App\Models;

use CodeIgniter\Model;

class AddOnModel extends Model
{
    protected $table            = 'add_ons';
    protected $primaryKey       = 'addon_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'addon_name',
        'description',
        'base_price',
        'min_price',
        'max_price',
        'category',
        'is_active',
        'sort_order',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveAddOns(string $category = 'optional'): array
    {
        return $this->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('addon_id', 'ASC')
            ->findAll();
    }
}