<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Packages & Services Management module.
 *
 * Extends the existing catalog tables instead of replacing them, so the
 * client service flow, plans, claims and reports keep working:
 *
 *  packages      + item_code, category, variant, short_description,
 *                  availability_status, stock_quantity (optional), audit
 *  service_list  + item_code, category, short_description, availability_status,
 *                  eligibility_rules, terms, notes, audit
 *  service_routes+ origin, destination (route_name / price stay and are kept in
 *                  sync, because the client flow reads those)
 *  addons          new table
 *  package_inclusion_items  the checklist of standard inclusions
 *
 * availability_status (available | unavailable | archived) is the source of
 * truth for admins; is_available / status are kept in step with it by the
 * module because every existing client query filters on them.
 */
class PackagesServicesAddonsManagement extends Migration
{
    public function up()
    {
        $this->addColumns('packages', [
            'item_code'           => "VARCHAR(20) NULL AFTER package_id",
            'category'            => "ENUM('wood','metal') NULL",
            'variant'             => "ENUM('regular','oversized','half_glass','full_glass') NULL",
            'short_description'   => "VARCHAR(255) NULL",
            'availability_status' => "ENUM('available','unavailable','archived') NOT NULL DEFAULT 'available'",
            'stock_quantity'      => "INT NULL",
            'created_by'          => "INT NULL",
            'created_at'          => "DATETIME NULL",
            'updated_by'          => "INT NULL",
            'updated_at'          => "DATETIME NULL",
        ]);

        $this->addColumns('service_list', [
            'item_code'           => "VARCHAR(20) NULL AFTER service_list_id",
            'category'            => "VARCHAR(100) NULL",
            'short_description'   => "VARCHAR(255) NULL",
            'availability_status' => "ENUM('available','unavailable','archived') NOT NULL DEFAULT 'available'",
            'eligibility_rules'   => "TEXT NULL",
            'terms'               => "TEXT NULL",
            'notes'               => "TEXT NULL",
            'created_by'          => "INT NULL",
            'updated_by'          => "INT NULL",
            'updated_at'          => "DATETIME NULL",
        ]);

        $this->addColumns('service_routes', [
            'origin'      => "VARCHAR(100) NULL",
            'destination' => "VARCHAR(100) NULL",
        ]);

        $this->forge->addField([
            'addon_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'item_code'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'addon_name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'short_description'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'         => ['type' => 'TEXT', 'null' => true],
            'pricing_method'      => ['type' => 'ENUM', 'constraint' => ['fixed', 'range'], 'default' => 'fixed'],
            'price'               => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'min_price'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'max_price'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'image_path'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'availability_status' => ['type' => 'ENUM', 'constraint' => ['available', 'unavailable', 'archived'], 'default' => 'available'],
            'created_by'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_by'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('addon_id', true);
        $this->forge->addUniqueKey('item_code');
        $this->forge->createTable('addons', true);

        $this->forge->addField([
            'inclusion_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'item_name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_standard'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('inclusion_item_id', true);
        $this->forge->createTable('package_inclusion_items', true);

        $this->backfillPackages();
        $this->backfillServices();
        $this->backfillRoutes();
        $this->seedInclusionItems();
        $this->seedAddons();

        $this->addUnique('packages', 'uniq_package_item_code', 'item_code');
        $this->addUnique('service_list', 'uniq_service_item_code', 'item_code');
        $this->addUnique('service_routes', 'uniq_service_route_pair', 'service_list_id, origin, destination');
    }

    public function down()
    {
        $this->forge->dropTable('addons', true);
        $this->forge->dropTable('package_inclusion_items', true);

        foreach (['uniq_service_route_pair' => 'service_routes', 'uniq_service_item_code' => 'service_list', 'uniq_package_item_code' => 'packages'] as $index => $table) {
            if ($this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'")->getNumRows() > 0) {
                $this->db->query("ALTER TABLE {$table} DROP INDEX {$index}");
            }
        }

        $drop = [
            'packages'       => ['item_code', 'category', 'variant', 'short_description', 'availability_status', 'stock_quantity', 'created_by', 'created_at', 'updated_by', 'updated_at'],
            'service_list'   => ['item_code', 'category', 'short_description', 'availability_status', 'eligibility_rules', 'terms', 'notes', 'created_by', 'updated_by', 'updated_at'],
            'service_routes' => ['origin', 'destination'],
        ];
        foreach ($drop as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->db->fieldExists($column, $table)) {
                    $this->forge->dropColumn($table, $column);
                }
            }
        }
    }

    private function addColumns(string $table, array $columns): void
    {
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, $table)) {
                $this->db->query("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}");
            }
        }
    }

    private function addUnique(string $table, string $name, string $columns): void
    {
        if ($this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$name}'")->getNumRows() === 0) {
            $this->db->query("ALTER TABLE {$table} ADD UNIQUE KEY {$name} ({$columns})");
        }
    }

    private function backfillPackages(): void
    {
        $rows = $this->db->table('packages')->orderBy('package_id', 'ASC')->get()->getResultArray();
        $n = 0;
        foreach ($rows as $row) {
            $name = strtolower((string) $row['package_name']);
            $update = ['item_code' => $row['item_code'] ?: sprintf('PKG-%04d', ++$n)];

            if (! $row['item_code']) {
                $update['availability_status'] = $row['status'] === 'inactive' ? 'archived' : ((int) $row['is_available'] === 1 ? 'available' : 'unavailable');
                $update['short_description'] = mb_substr((string) strtok((string) $row['description'], "\n"), 0, 255);
                if (str_contains($name, 'casket')) {
                    $update['category'] = str_contains($name, 'metal') ? 'metal' : 'wood';
                    $update['variant'] = str_contains($name, 'oversized') ? 'oversized'
                        : (str_contains($name, 'half-glass') ? 'half_glass' : (str_contains($name, 'full-glass') ? 'full_glass' : 'regular'));
                }
            }
            $this->db->table('packages')->where('package_id', $row['package_id'])->update($update);
        }
    }

    private function backfillServices(): void
    {
        $rows = $this->db->table('service_list')->orderBy('service_list_id', 'ASC')->get()->getResultArray();
        $n = 0;
        foreach ($rows as $row) {
            $update = ['item_code' => $row['item_code'] ?: sprintf('SRV-%04d', ++$n)];
            if (! $row['item_code']) {
                $update['availability_status'] = $row['status'] === 'inactive' ? 'unavailable' : ((int) $row['is_available'] === 1 ? 'available' : 'unavailable');
                $update['short_description'] = mb_substr((string) $row['description'], 0, 255);
                if (stripos((string) $row['service_name'], 'Balik Probinsya') !== false) {
                    $update['category'] = 'Provincial Transport';
                    $update['eligibility_rules'] = 'Eligible plan holders receive casket coverage according to the applicable KAAGAPAY policy. The service fee stays the same; the casket value is not subtracted from it.';
                }
            }
            $this->db->table('service_list')->where('service_list_id', $row['service_list_id'])->update($update);
        }
    }

    private function backfillRoutes(): void
    {
        $rows = $this->db->table('service_routes')->where('origin', null)->get()->getResultArray();
        foreach ($rows as $row) {
            $parts = preg_split('/\s+to\s+/i', (string) $row['route_name'], 2);
            $this->db->table('service_routes')->where('route_id', $row['route_id'])->update([
                'origin'      => trim($parts[0] ?? '') ?: $row['route_name'],
                'destination' => trim($parts[1] ?? '') ?: '-',
            ]);
        }
    }

    private function seedInclusionItems(): void
    {
        if ($this->db->table('package_inclusion_items')->countAllResults() > 0) {
            return;
        }

        $items = [
            ['Casket', 'The casket for this package.'],
            ['Flowers', 'Floral arrangement for the viewing.'],
            ['Tarpaulin', 'Memorial tarpaulin/streamer.'],
            ['Viewing setup', 'Lights, curtains and registry stand for the viewing area.'],
            ['Embalming', 'Professional embalming service.'],
            ['Body preparation', 'Preparation and dressing of the deceased.'],
            ['Death certificate assistance', 'Help securing the death certificate.'],
            ['Burial permit assistance', 'Help securing the burial permit.'],
        ];
        foreach ($items as $i => [$name, $desc]) {
            $this->db->table('package_inclusion_items')->insert(['item_name' => $name, 'description' => $desc, 'is_standard' => 1, 'sort_order' => $i + 1]);
        }
    }

    private function seedAddons(): void
    {
        if ($this->db->table('addons')->countAllResults() > 0) {
            return;
        }

        $this->db->table('addons')->insert([
            'item_code'           => 'ADD-0001',
            'addon_name'          => 'Burial Attire',
            'short_description'   => 'Optional burial clothing for the deceased.',
            'description'         => 'Optional burial clothing for the deceased. Staff confirm the actual price within the range before it is added to the total.',
            'pricing_method'      => 'range',
            'min_price'           => 1500,
            'max_price'           => 2000,
            'availability_status' => 'available',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }
}
