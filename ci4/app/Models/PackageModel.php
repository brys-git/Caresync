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
        'package_type',
        'parent_package_id',
        'description',
        'base_price',
        'is_customizable',
        'is_available',
        'status',
    ];

    public function getActivePackages(string $packageType = 'funeral'): array
    {
        return $this->where('package_type', $packageType)
            ->where('status', 'active')
            ->where('is_available', 1)
            ->orderBy('package_name', 'ASC')
            ->findAll();
    }

    public function getPackageWithVariants(int $packageId): ?array
    {
        $pkg = $this->find($packageId);
        if (! $pkg) {
            return null;
        }

        $variantModel = new PackageVariantModel();
        $inclusionModel = new PackageInclusionModel();

        $pkg['variants'] = $variantModel->getActiveVariants($packageId);
        $pkg['inclusions'] = $inclusionModel->getActiveInclusions($packageId);

        return $pkg;
    }
}
