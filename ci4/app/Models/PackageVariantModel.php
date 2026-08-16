<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageVariantModel extends Model
{
    protected $table            = 'package_variants';
    protected $primaryKey       = 'variant_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'package_id',
        'variant_name',
        'description',
        'base_price',
        'is_default',
        'sort_order',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveVariants(int $packageId): array
    {
        return $this->where('package_id', $packageId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('variant_id', 'ASC')
            ->findAll();
    }

    public function getDefaultVariant(int $packageId): ?array
    {
        return $this->where('package_id', $packageId)
            ->where('is_default', 1)
            ->where('status', 'active')
            ->first();
    }
}