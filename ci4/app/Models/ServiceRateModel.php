<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceRateModel extends Model
{
    protected $table            = 'service_rates';
    protected $primaryKey       = 'rate_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'service_list_id',
        'origin',
        'destination',
        'rate',
        'description',
        'is_active',
        'sort_order',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveRates(int $serviceListId): array
    {
        return $this->where('service_list_id', $serviceListId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('rate_id', 'ASC')
            ->findAll();
    }

    public function getRate(int $serviceListId, string $origin, string $destination): ?array
    {
        return $this->where('service_list_id', $serviceListId)
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('is_active', 1)
            ->first();
    }
}