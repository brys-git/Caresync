<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRejectionReasonToServiceApplications extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE service_applications ADD COLUMN rejection_reason TEXT NULL AFTER application_notes");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE service_applications DROP COLUMN rejection_reason");
    }
}
