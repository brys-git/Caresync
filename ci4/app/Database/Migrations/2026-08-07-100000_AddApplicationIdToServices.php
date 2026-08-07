<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationIdToServices extends Migration
{
    public function up(): void
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('services') && ! $db->fieldExists('application_id', 'services')) {
            $db->query('ALTER TABLE `services` ADD COLUMN `application_id` INT(11) UNSIGNED NULL AFTER `branch_id`');
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('services') && $db->fieldExists('application_id', 'services')) {
            $db->query('ALTER TABLE `services` DROP COLUMN `application_id`');
        }
    }
}
