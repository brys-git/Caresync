<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rename the legacy per-service add_ons table (add_on_id, service_id,
 * item_name, price) to service_add_ons, then create the new catalog
 * add_ons table used by the Services & Packages module (addon_id,
 * addon_name, base_price, min_price, max_price, ...).
 */
class RenameOldAddOnsAndCreateCatalog extends Migration
{
    public function up()
    {
        // 1. Preserve the legacy table under a clearer name
        if ($this->db->tableExists('add_ons') && ! $this->db->tableExists('service_add_ons')) {
            $this->forge->renameTable('add_ons', 'service_add_ons');
        }

        // 2. Create the new catalog table (only if it does not already exist)
        if (! $this->db->tableExists('add_ons')) {
            $this->forge->addField([
                'addon_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'addon_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'base_price' => [
                    'type'    => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ],
                'min_price' => [
                    'type'    => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'   => true,
                ],
                'max_price' => [
                    'type'    => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'   => true,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'optional',
                ],
                'is_active' => [
                    'type'    => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('addon_id', true);
            $this->forge->createTable('add_ons', true);
        }
    }

    public function down()
    {
        // Reverse: drop the catalog table and restore the legacy name
        if ($this->db->tableExists('add_ons') && ! $this->db->fieldExists('service_id', 'add_ons')) {
            $this->forge->dropTable('add_ons', true);
        }

        if ($this->db->tableExists('service_add_ons') && ! $this->db->tableExists('add_ons')) {
            $this->forge->renameTable('service_add_ons', 'add_ons');
        }
    }
}
