<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Service process after approval.
 *
 *   services.status        + scheduled, preparing, burial_day, closed. The existing values keep their meaning:
 *                          'pending' = approved (not scheduled yet), 'ongoing', 'completed', 'cancelled'.
 *   service_plans          one row per service: schedule (pick-up, wake, burial), family contact, the family's
 *                          confirmation when the service is completed, and the client's rating.
 *   service_task_templates one task template per package inclusion (matched by name); the task list of a service is
 *                          built from the package's inclusions (package_items) plus its add-ons.
 *   service_tasks          the checklist: one row per task, assigned to a staff member of the branch.
 *
 * Additive: nothing existing is changed or removed.
 */
class CreateServiceProcess extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE services MODIFY status ENUM('pending','scheduled','preparing','ongoing','burial_day','completed','closed','cancelled') NOT NULL");

        if (! $this->db->tableExists('service_plans')) {
            $this->forge->addField([
                'plan_row_id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'service_id'             => ['type' => 'INT', 'constraint' => 11],
                'pickup_at'              => ['type' => 'DATETIME', 'null' => true],
                'pickup_place'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'wake_start'             => ['type' => 'DATETIME', 'null' => true],
                'wake_end'               => ['type' => 'DATETIME', 'null' => true],
                'wake_address'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'burial_at'              => ['type' => 'DATETIME', 'null' => true],
                'cemetery'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'family_name'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'family_mobile'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'family_relationship'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'confirmed_name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'confirmed_relationship' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'confirmed_on'           => ['type' => 'DATE', 'null' => true],
                'confirmed_photo_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'confirmed_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'completed_at'           => ['type' => 'DATETIME', 'null' => true],
                'closed_at'              => ['type' => 'DATETIME', 'null' => true],
                'closed_by'              => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'rating'                 => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
                'feedback'               => ['type' => 'TEXT', 'null' => true],
                'rated_at'               => ['type' => 'DATETIME', 'null' => true],
                'updated_by'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
                'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('plan_row_id', true);
            $this->forge->addUniqueKey('service_id', 'uq_service_plans_service');
            $this->forge->createTable('service_plans', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->tableExists('service_task_templates')) {
            $this->forge->addField([
                'template_id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'match_text'        => ['type' => 'VARCHAR', 'constraint' => 100],
                'name'              => ['type' => 'VARCHAR', 'constraint' => 150],
                'instructions'      => ['type' => 'TEXT', 'null' => true],
                'requires_document' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'sort_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->forge->addKey('template_id', true);
            $this->forge->createTable('service_task_templates', true, ['ENGINE' => 'InnoDB']);

            // One template per standard inclusion (match_text is compared with the start of the inclusion name, lower case),
            // plus the add-ons a request can carry.
            $rows = [
                ['casket', 'Prepare and deliver the casket', 'Check the casket for this package is clean and undamaged, and have it ready at the wake address.', 0, 10],
                ['flowers', 'Arrange the flowers', 'Floral arrangement for the viewing. Deliver and set it up before the wake starts.', 0, 20],
                ['tarpaulin', 'Print and put up the tarpaulin', 'Memorial tarpaulin/streamer: confirm the name and dates with the family before printing.', 0, 30],
                ['viewing setup', 'Set up the viewing area', 'Lights, curtains and registry stand at the wake address. Take a photo when it is ready.', 0, 40],
                ['embalming', 'Embalming', 'Professional embalming. Upload the embalming certificate when it is done.', 1, 50],
                ['body preparation', 'Prepare and dress the deceased', 'Preparation and dressing of the deceased, with the clothes given by the family.', 0, 60],
                ['death certificate', 'Help secure the death certificate', 'Assist the family with the death certificate. Upload a copy when you have it.', 1, 70],
                ['burial permit', 'Help secure the burial permit', 'Assist the family with the burial permit. Upload a copy when you have it.', 1, 80],
                ['burial attire', 'Prepare the burial attire', 'Add-on: prepare the burial attire chosen by the family.', 0, 90],
            ];
            foreach ($rows as [$match, $name, $ins, $doc, $sort]) {
                $this->db->table('service_task_templates')->insert(['match_text' => $match, 'name' => $name, 'instructions' => $ins, 'requires_document' => $doc, 'sort_order' => $sort, 'is_active' => 1]);
            }
        }

        if (! $this->db->tableExists('service_tasks')) {
            $this->forge->addField([
                'task_id'           => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'service_id'        => ['type' => 'INT', 'constraint' => 11],
                'name'              => ['type' => 'VARCHAR', 'constraint' => 150],
                'instructions'      => ['type' => 'TEXT', 'null' => true],
                'sort_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'assigned_staff_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'due_at'            => ['type' => 'DATETIME', 'null' => true],
                'status'            => ['type' => 'ENUM', 'constraint' => ['todo', 'in_progress', 'done', 'problem'], 'default' => 'todo'],
                'started_at'        => ['type' => 'DATETIME', 'null' => true],
                'done_at'           => ['type' => 'DATETIME', 'null' => true],
                'note'              => ['type' => 'TEXT', 'null' => true],
                'document_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'document_name'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'requires_document' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'problem_reason'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'problem_at'        => ['type' => 'DATETIME', 'null' => true],
                'source'            => ['type' => 'ENUM', 'constraint' => ['package', 'addon', 'manual'], 'default' => 'manual'],
                'template_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_by'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at'        => ['type' => 'DATETIME', 'null' => true],
                'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('task_id', true);
            $this->forge->addKey(['service_id', 'sort_order']);
            $this->forge->addKey(['assigned_staff_id', 'status']);
            $this->forge->createTable('service_tasks', true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $this->forge->dropTable('service_tasks', true);
        $this->forge->dropTable('service_task_templates', true);
        $this->forge->dropTable('service_plans', true);
        $this->db->query("UPDATE services SET status = 'ongoing' WHERE status IN ('scheduled','preparing','burial_day')");
        $this->db->query("UPDATE services SET status = 'completed' WHERE status = 'closed'");
        $this->db->query("ALTER TABLE services MODIFY status ENUM('pending','ongoing','completed','cancelled') NOT NULL");
    }
}
