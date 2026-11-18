<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Every approved claim or avail is linked to its service row, so "is there still an open request"
 * works without any manual link (the old rule had to go through service_balances).
 *
 * service_applications.service_id   the services row created when the request was approved
 * service_applications.decided_by   who approved or rejected it
 * service_applications.decided_at   when
 *
 * All nullable: every existing row stays exactly as it is.
 */
class LinkApplicationsToServices extends Migration
{
    public function up()
    {
        $add = [];
        if (! $this->db->fieldExists('service_id', 'service_applications')) {
            $add['service_id'] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }
        if (! $this->db->fieldExists('decided_by', 'service_applications')) {
            $add['decided_by'] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }
        if (! $this->db->fieldExists('decided_at', 'service_applications')) {
            $add['decided_at'] = ['type' => 'DATETIME', 'null' => true];
        }
        if ($add !== []) {
            $this->forge->addColumn('service_applications', $add);
        }

        $idx = $this->db->query("SELECT COUNT(*) AS n FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'service_applications' AND index_name = 'idx_sa_service'")->getRowArray();
        if ((int) ($idx['n'] ?? 0) === 0) {
            $this->db->query('ALTER TABLE service_applications ADD INDEX idx_sa_service (service_id)');
        }
    }

    public function down()
    {
        $idx = $this->db->query("SELECT COUNT(*) AS n FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'service_applications' AND index_name = 'idx_sa_service'")->getRowArray();
        if ((int) ($idx['n'] ?? 0) > 0) {
            $this->db->query('ALTER TABLE service_applications DROP INDEX idx_sa_service');
        }
        foreach (['service_id', 'decided_by', 'decided_at'] as $c) {
            if ($this->db->fieldExists($c, 'service_applications')) {
                $this->forge->dropColumn('service_applications', $c);
            }
        }
    }
}
