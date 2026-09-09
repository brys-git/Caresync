<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Per-route pricing options for a service_list entry (e.g. Balik
 * Probinsya's Manila->Mindoro / Batangas->Mindoro routes). Modeled after
 * PackageVersionModel rather than adding fixed route columns to
 * service_list, so more routes can be added later without a schema change.
 */
class ServiceRouteModel extends Model
{
    protected $table            = 'service_routes';
    protected $primaryKey       = 'route_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'service_list_id',
        'route_name',
        'price',
        'sort_order',
    ];
}
