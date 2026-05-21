<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table            = 'plans';
    protected $primaryKey       = 'plan_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'plan_holder_id',
        'package_id',
        'monthly_fee',
        'passbook_fee',
        'start_date',
        'status',
        'months_paid',
        'remaining_balance',
        'next_due_date',
        'payment_coverage_until',
        'membership_state',
        'overdue_months',
        'total_plan_amount',
        'version_id',
    ];
}
