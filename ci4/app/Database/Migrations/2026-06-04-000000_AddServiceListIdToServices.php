<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceListIdToServices extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! $db->tableExists('services')) {
            return;
        }

        $fields = $db->getFieldNames('services');
        if (in_array('service_list_id', $fields, true)) {
            return;
        }

        $this->forge->addColumn('services', [
            'service_list_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'null' => true,
                'after' => 'branch_id',
            ],
        ]);
    }

    public function down()
    {
        $db = $this->db;
        if (! $db->tableExists('services')) {
            return;
        }

        $fields = $db->getFieldNames('services');
        if (! in_array('service_list_id', $fields, true)) {
            return;
        }

        $this->forge->dropColumn('services', 'service_list_id');
    }
}
