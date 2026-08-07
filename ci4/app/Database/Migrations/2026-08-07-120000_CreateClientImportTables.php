<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Client Record Import — staging + audit tables.
 *
 * client_import_batches : one row per uploaded document.
 * client_import_records : one row per staged client extracted from a batch.
 *
 * Nothing is committed to the live schema until the admin reviews the batch.
 */
class CreateClientImportTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'import_batch_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'file_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'format' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'docx',
            ],
            'parse_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'parsed', 'failed'],
                'default'    => 'pending',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['staged', 'committed', 'discarded'],
                'default'    => 'staged',
            ],
            'total_records' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'ready_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'needs_attention_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'duplicate_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'skipped_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'committed_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'raw_text' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'summary_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'parse_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'committed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'committed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => 'current_timestamp()',
            ],
        ]);

        $this->forge->addKey('import_batch_id', true);
        $this->forge->addKey('branch_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('client_import_batches', true);

        $this->forge->addField([
            'import_record_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'import_batch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'source_index' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'coordinator' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'application_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'middle_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'name_extension' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'date_of_birth' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'address_raw' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'address_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'address_street' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'address_barangay' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'address_city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'mapped_data' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'beneficiaries_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'extracted_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'validation_errors_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'match_candidates_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'duplicate_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'record_status' => [
                'type'       => 'ENUM',
                'constraint' => ['ready', 'needs_attention', 'duplicate', 'skip'],
                'default'    => 'needs_attention',
            ],
            'admin_decision' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'create_new', 'link_existing', 'skip'],
                'default'    => 'pending',
            ],
            'linked_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'linked_plan_holder_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_plan_holder_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_plan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'temp_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'temp_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'temp_password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'temp_password_plain' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'committed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'committed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => 'current_timestamp()',
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => 'current_timestamp()',
                'on_update' => 'current_timestamp()',
            ],
        ]);

        $this->forge->addKey('import_record_id', true);
        $this->forge->addKey('import_batch_id');
        $this->forge->addKey('record_status');
        $this->forge->addKey('duplicate_key');
        $this->forge->addKey('admin_decision');
        $this->forge->createTable('client_import_records', true);

        $this->forge->addField('CONSTRAINT fk_import_records_batch FOREIGN KEY (import_batch_id) REFERENCES client_import_batches (import_batch_id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->forge->dropTable('client_import_records', true);
        $this->forge->dropTable('client_import_batches', true);
    }
}
