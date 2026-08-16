<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Balik Probinsya and Damayan pricing columns to service_applications.
 */
class AddBalikProbinsyaColumns extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('service_type', 'service_applications')) {
            $fields['service_type'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'service_list_id',
            ];
        }

        if (! $this->db->fieldExists('origin', 'service_applications')) {
            $fields['origin'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'service_type',
            ];
        }

        if (! $this->db->fieldExists('destination', 'service_applications')) {
            $fields['destination'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'origin',
            ];
        }

        if (! $this->db->fieldExists('estimated_cost', 'service_applications')) {
            $fields['estimated_cost'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'destination',
            ];
        }

        if (! $this->db->fieldExists('damayan_credit', 'service_applications')) {
            $fields['damayan_credit'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'estimated_cost',
            ];
        }

        if (! $this->db->fieldExists('final_amount', 'service_applications')) {
            $fields['final_amount'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'damayan_credit',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('service_applications', $fields);
        }
    }

    public function down()
    {
        $dropFields = [];
        foreach (['service_type', 'origin', 'destination', 'estimated_cost', 'damayan_credit', 'final_amount'] as $field) {
            if ($this->db->fieldExists($field, 'service_applications')) {
                $dropFields[] = $field;
            }
        }

        if ($dropFields !== []) {
            $this->forge->dropColumn('service_applications', $dropFields);
        }
    }
}
