<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Package Variants, Inclusions, Add-ons, and Service Rates
 *
 * Supports the new Services & Packages module structure:
 * - Two main Funeral Packages: Wood Casket / Metal Casket
 * - Each with variants (Regular, Oversized, Half-Glass, Full-Glass)
 * - Package inclusions (shared list of included items)
 * - Optional Add-on: Burial Attire
 * - Service: Balik Probinsya Program with origin/destination rates
 */
class AddPackageVariantsAndInclusions extends Migration
{
    public function up()
    {
        // 1. Package Variants table - stores the specific casket options per package
        $this->forge->addField([
            'variant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'package_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'variant_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'base_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'active',
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
        $this->forge->addKey('variant_id', true);
        $this->forge->addKey('package_id');
        $this->forge->addForeignKey('package_id', 'packages', 'package_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('package_variants', true);

        // 2. Package Inclusions table - shared list of items included in packages
        $this->forge->addField([
            'inclusion_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'package_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'item_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'general',
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'active',
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
        $this->forge->addKey('inclusion_id', true);
        $this->forge->addKey('package_id');
        $this->forge->addForeignKey('package_id', 'packages', 'package_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('package_inclusions', true);

        // 3. Add-ons table - optional add-ons like Burial Attire
        $this->forge->addField([
            'addon_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'addon_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'base_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'min_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'max_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'optional',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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

        // 4. Service Rates table - for Balik Probinsya and other services with variable pricing
        $this->forge->addField([
            'rate_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'service_list_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'origin' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'destination' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addKey('rate_id', true);
        $this->forge->addKey('service_list_id');
        $this->forge->addForeignKey('service_list_id', 'service_list', 'service_list_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('service_rates', true);

        // 5. Add package_type to packages table to distinguish package categories
        $this->forge->addColumn('packages', [
            'package_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'funeral',
                'after' => 'package_name',
            ],
            'parent_package_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'package_type',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('package_variants', true);
        $this->forge->dropTable('package_inclusions', true);
        $this->forge->dropTable('add_ons', true);
        $this->forge->dropTable('service_rates', true);

        $this->forge->dropColumn('packages', ['package_type', 'parent_package_id']);
    }
}