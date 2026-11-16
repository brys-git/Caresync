<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A Branch Admin can file a casket claim for a plan holder. These columns
 * record who filed it and who is actually making the claim. All nullable /
 * defaulted, so every existing claim stays exactly as it is.
 */
class AddFiledOnBehalfToServiceApplications extends Migration
{
    public function up()
    {
        $columns = [
            'filed_by_user_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'filed_on_behalf'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'claimant_name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'claimant_relationship' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ];

        $missing = [];
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'service_applications')) {
                $missing[$name] = $definition;
            }
        }

        if ($missing !== []) {
            $this->forge->addColumn('service_applications', $missing);
        }
    }

    public function down()
    {
        foreach (['filed_by_user_id', 'filed_on_behalf', 'claimant_name', 'claimant_relationship'] as $name) {
            if ($this->db->fieldExists($name, 'service_applications')) {
                $this->forge->dropColumn('service_applications', $name);
            }
        }
    }
}
