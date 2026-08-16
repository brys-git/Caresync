<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageInclusionModel extends Model
{
    protected $table            = 'package_inclusions';
    protected $primaryKey       = 'inclusion_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'package_id',
        'item_name',
        'description',
        'category',
        'sort_order',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveInclusions(int $packageId): array
    {
        return $this->where('package_id', $packageId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('inclusion_id', 'ASC')
            ->findAll();
    }

    public function getInclusionsByCategory(int $packageId): array
    {
        $inclusions = $this->getActiveInclusions($packageId);
        $grouped = [];
        foreach ($inclusions as $inc) {
            $cat = $inc['category'] ?? 'general';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $inc;
        }
        return $grouped;
    }
}