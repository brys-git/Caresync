<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateServiceLogsColumns extends Migration
{
    public function up()
    {
        $db = db_connect();

        if (! $db->tableExists('service_logs')) {
            return;
        }

        $fields = $db->getFieldNames('service_logs');
        $columns = [];

        if (! in_array('old_status', $fields, true)) {
            $columns['old_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => '',
                'after' => 'service_id',
            ];
        }

        if (! in_array('new_status', $fields, true)) {
            $columns['new_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => '',
                'after' => in_array('old_status', $fields, true) ? 'old_status' : 'service_id',
            ];
        }

        if (! in_array('logged_at', $fields, true)) {
            $columns['logged_at'] = [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => in_array('new_status', $fields, true) ? 'new_status' : (in_array('old_status', $fields, true) ? 'old_status' : 'service_id'),
            ];
        }

        if (! empty($columns)) {
            $this->forge->addColumn('service_logs', $columns);
        }

        if (in_array('changed_by', $fields, true)) {
            $db->query('ALTER TABLE service_logs MODIFY COLUMN changed_by INT(11) NULL');
        } else {
            $this->forge->addColumn('service_logs', [
                'changed_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => in_array('new_status', $fields, true) ? 'new_status' : (in_array('old_status', $fields, true) ? 'old_status' : 'service_id'),
                ],
            ]);
        }
    }

    public function down()
    {
        $db = db_connect();

        if (! $db->tableExists('service_logs')) {
            return;
        }

        $fields = $db->getFieldNames('service_logs');
        $columns = [];

        if (in_array('old_status', $fields, true)) {
            $columns[] = 'old_status';
        }

        if (in_array('new_status', $fields, true)) {
            $columns[] = 'new_status';
        }

        if (in_array('logged_at', $fields, true)) {
            $columns[] = 'logged_at';
        }

        if (! empty($columns)) {
            $this->forge->dropColumn('service_logs', $columns);
        }
    }
}
