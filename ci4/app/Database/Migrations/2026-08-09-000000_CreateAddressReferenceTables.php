<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Local cache of the Philippine Standard Geographic Code (PSGC) reference data
 * used by the registration wizard's Province / Town-City / Barangay dropdowns.
 *
 * Previously the wizard fetched these live from the third-party psgc.cloud API on
 * every page load. A dead or blocked upstream meant the required address fields
 * could not be filled and registration was blocked. These tables let the app serve
 * the same data from its own database; the upstream API is only consulted to seed
 * a level that is not yet cached.
 *
 * Safe to re-run (guarded).
 */
class CreateAddressReferenceTables extends Migration
{
    public function up(): void
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('address_provinces')) {
            $this->forge->addField([
                'code' => ['type' => 'VARCHAR', 'constraint' => 20],
                'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            ]);
            $this->forge->addKey('code', true);
            $this->forge->addKey('name');
            $this->forge->createTable('address_provinces', true);
        }

        if (! $db->tableExists('address_cities')) {
            $this->forge->addField([
                'code' => ['type' => 'VARCHAR', 'constraint' => 20],
                'province_code' => ['type' => 'VARCHAR', 'constraint' => 20],
                'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            ]);
            $this->forge->addKey('code', true);
            $this->forge->addKey('province_code');
            $this->forge->createTable('address_cities', true);
        }

        if (! $db->tableExists('address_barangays')) {
            $this->forge->addField([
                'code' => ['type' => 'VARCHAR', 'constraint' => 20],
                'city_code' => ['type' => 'VARCHAR', 'constraint' => 20],
                'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            ]);
            $this->forge->addKey('code', true);
            $this->forge->addKey('city_code');
            $this->forge->createTable('address_barangays', true);
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('address_barangays')) {
            $this->forge->dropTable('address_barangays', true);
        }
        if ($db->tableExists('address_cities')) {
            $this->forge->dropTable('address_cities', true);
        }
        if ($db->tableExists('address_provinces')) {
            $this->forge->dropTable('address_provinces', true);
        }
    }
}
