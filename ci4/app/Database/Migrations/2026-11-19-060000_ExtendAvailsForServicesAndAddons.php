<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Single services (e.g. Balik Probinsya) and paid add-ons (e.g. Burial Attire on a claim) are
 * treated as an avail until Kaagapay confirms how they are paid. They use the same table:
 *
 *   package_avails.kind          'package' (default, as before) | 'service' | 'addons'
 *   package_avails.service_list_id   the service, for kind 'service'
 *   package_avails.addons_amount / addons_label   the add-ons included in package_price
 *   package_avails.package_id    may now be empty (a single service has no package)
 *
 * A small amount (see Config\CareSync::availMinFinancedAmount) is paid in full up front: down payment
 * = the whole price, term 0, no instalments - term_months already accepts 0.
 */
class ExtendAvailsForServicesAndAddons extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('package_avails')) {
            return;
        }

        $add = [];
        if (! $this->db->fieldExists('kind', 'package_avails')) {
            $add['kind'] = ['type' => 'ENUM', 'constraint' => ['package', 'service', 'addons'], 'default' => 'package', 'null' => false];
        }
        if (! $this->db->fieldExists('service_list_id', 'package_avails')) {
            $add['service_list_id'] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }
        if (! $this->db->fieldExists('addons_amount', 'package_avails')) {
            $add['addons_amount'] = ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false];
        }
        if (! $this->db->fieldExists('addons_label', 'package_avails')) {
            $add['addons_label'] = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true];
        }
        if ($add !== []) {
            $this->forge->addColumn('package_avails', $add);
        }

        $this->db->query('ALTER TABLE package_avails MODIFY package_id INT(11) NULL');
    }

    public function down()
    {
        if (! $this->db->tableExists('package_avails')) {
            return;
        }
        foreach (['kind', 'service_list_id', 'addons_amount', 'addons_label'] as $c) {
            if ($this->db->fieldExists($c, 'package_avails')) {
                $this->forge->dropColumn('package_avails', $c);
            }
        }
        // package_id stays nullable: setting it NOT NULL again could fail on a row that has none.
    }
}
