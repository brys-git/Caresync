<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Damayan Burial Program — Business Logic Support
 *
 * Adds fields to track:
 * 1. Member death status and contribution waiver (on plan_holders)
 * 2. Damayan benefit activation and application (on services)
 * 3. Damayan eligibility flag (on service_applications)
 *
 * These values are stored SEPARATELY per the business rules:
 * - Contribution tracking: monthly_fee, months_paid, remaining_balance (existing)
 * - Damayan benefit: damayan_benefit_credit (PHP 14,500 fixed)
 * - Waiver: waived_contribution_amount (the remaining_balance at death)
 */
class AddDamayanBenefitTables extends Migration
{
    public function up()
    {
        $this->forge->addColumn('plan_holders', [
            'is_deceased' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'status',
            ],
            'date_of_death' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'is_deceased',
            ],
            'damayan_benefit_activated' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'date_of_death',
            ],
            'damayan_benefit_activation_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'damayan_benefit_activated',
            ],
            'waived_contribution_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'damayan_benefit_activation_date',
            ],
            'waiver_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'waived_contribution_amount',
            ],
            'waiver_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'waiver_date',
            ],
        ]);

        $this->forge->addColumn('services', [
            'damayan_eligible' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'total_cost',
            ],
            'damayan_benefit_credit' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'damayan_eligible',
            ],
            'upgrade_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'damayan_benefit_credit',
            ],
            'final_amount_due' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'upgrade_amount',
            ],
        ]);

        $this->forge->addColumn('service_applications', [
            'damayan_eligible' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'package_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('plan_holders', [
            'is_deceased',
            'date_of_death',
            'damayan_benefit_activated',
            'damayan_benefit_activation_date',
            'waived_contribution_amount',
            'waiver_date',
            'waiver_reason',
        ]);

        $this->forge->dropColumn('services', [
            'damayan_eligible',
            'damayan_benefit_credit',
            'upgrade_amount',
            'final_amount_due',
        ]);

        $this->forge->dropColumn('service_applications', [
            'damayan_eligible',
        ]);
    }
}
