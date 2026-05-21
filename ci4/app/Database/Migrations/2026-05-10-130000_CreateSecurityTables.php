<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityTables extends Migration
{
    public function up()
    {
        // Create system settings table
        $this->forge->addField([
            'setting_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'unique' => true,
            ],
            'setting_value' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'data_type' => [
                'type' => 'ENUM',
                'constraint' => ['string', 'integer', 'boolean', 'json', 'decimal'],
                'default' => 'string',
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
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addPrimaryKey('setting_id');
        $this->forge->addKey('category');
        $this->forge->addKey('is_active');
        if (! db_connect()->tableExists('system_settings')) {
            $this->forge->createTable('system_settings');
        }

        // Create rate limits table
        $this->forge->addField([
            'limit_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'attempt_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ],
            'first_attempt' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'last_attempt' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
            'is_blocked' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'blocked_until' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('limit_id');
        $this->forge->addUniqueKey(['ip_address', 'action'], 'idx_ip_action');
        $this->forge->addKey('is_blocked');
        $this->forge->addKey('blocked_until');
        if (! db_connect()->tableExists('rate_limits')) {
            $this->forge->createTable('rate_limits');
        }

        // Create user sessions table
        $this->forge->addField([
            'session_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'session_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'unique' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'last_activity' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
        ]);

        $this->forge->addPrimaryKey('session_id');
        $this->forge->addForeignKey('user_id', 'users', 'user_id', 'CASCADE');
        $this->forge->addKey(['user_id', 'session_token'], 'idx_user_token');
        $this->forge->addKey('expires_at');
        $this->forge->addKey('is_active');
        if (! db_connect()->tableExists('user_sessions')) {
            $this->forge->createTable('user_sessions');
        }

        // Create API keys table
        $this->forge->addField([
            'key_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'api_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'unique' => true,
            ],
            'api_secret' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'last_used' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'ip_whitelist' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('key_id');
        $this->forge->addForeignKey('user_id', 'users', 'user_id', 'CASCADE');
        $this->forge->addKey('api_key', 'idx_api_key');
        $this->forge->addKey('user_id');
        $this->forge->addKey('is_active');
        if (! db_connect()->tableExists('api_keys')) {
            $this->forge->createTable('api_keys');
        }

        // Enhance users table with security columns
        $additionalColumns = [
            'failed_login_attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => true,
            ],
            'last_failed_login' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'locked_until' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'key' => true,
            ],
            'two_factor_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => true,
                'key' => true,
            ],
            'two_factor_secret' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ip_address_created' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'ip_address_last_login' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
        ];

        foreach ($additionalColumns as $colName => $colDef) {
            if (! db_connect()->fieldExists($colName, 'users')) {
                $this->forge->addColumn('users', [$colName => $colDef]);
            }
        }
    }

    public function down()
    {
        // Remove columns from users table
        $fields = [
            'failed_login_attempts',
            'last_failed_login',
            'locked_until',
            'two_factor_enabled',
            'two_factor_secret',
            'ip_address_created',
            'ip_address_last_login',
        ];

        foreach ($fields as $field) {
            if ($this->forge->fieldExists($field, 'users')) {
                $this->forge->dropColumn('users', $field);
            }
        }

        // Drop security tables
        $this->forge->dropTable('api_keys', true);
        $this->forge->dropTable('user_sessions', true);
        $this->forge->dropTable('rate_limits', true);
        $this->forge->dropTable('system_settings', true);
    }
}
