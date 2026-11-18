<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * service_application_notes   a note Staff (or a Branch Admin) leaves on a request for the
 *                             Branch Admin to see. Internal: never shown to the client.
 * service_applications.claimant_beneficiary_id   when a request is filed at the branch for a
 *                             registered beneficiary, which beneficiary (picked from the list,
 *                             not typed). NULL = the plan holder themself, or an older request.
 */
class CreateApplicationNotesAndClaimant extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('service_application_notes')) {
            $this->forge->addField([
                'note_id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'application_id' => ['type' => 'INT', 'constraint' => 11],
                'user_id'        => ['type' => 'INT', 'constraint' => 11],
                'note'           => ['type' => 'TEXT'],
                'created_at'     => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addKey('note_id', true);
            $this->forge->addKey('application_id');
            $this->forge->addForeignKey('application_id', 'service_applications', 'application_id', 'RESTRICT', 'RESTRICT');
            $this->forge->createTable('service_application_notes', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->fieldExists('claimant_beneficiary_id', 'service_applications')) {
            $this->forge->addColumn('service_applications', [
                'claimant_beneficiary_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('service_application_notes', true);
        if ($this->db->fieldExists('claimant_beneficiary_id', 'service_applications')) {
            $this->forge->dropColumn('service_applications', 'claimant_beneficiary_id');
        }
    }
}
