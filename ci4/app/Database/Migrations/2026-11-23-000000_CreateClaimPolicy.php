<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kaagapay's claim policy (confirmed):
 *
 *   - A claim WAIVES the plan's remaining balance for the current cycle: plan_waivers records how much, per cycle.
 *   - "Who died?" is recorded on the request (service_applications.deceased_type / deceased_beneficiary_id) and who
 *     is making the claim (claimant_type: the plan holder, a registered beneficiary, or the emergency contact).
 *   - A beneficiary who died can never be claimed for again (beneficiaries.status = 'deceased').
 *   - A plan can end: 'closed_deceased' (the plan holder died and the service was completed),
 *     'closed_deceased_owing' (same, but an avail balance is still being paid) and 'closed_by_request' (the plan
 *     holder asked to stop and the Branch Admin closed it). The plan holder gets status 'deceased' or 'closed'.
 *   - plan_closure_requests: a client's request to close their plan, for their Branch Admin.
 *   - package_avails.responsible_*: who answers for an avail balance (the claimant).
 *
 * Additive: nothing existing is removed; every new column is nullable or has a default.
 */
class CreateClaimPolicy extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ---- service_applications: who died, who claims
        $cols = [];
        if (! $db->fieldExists('deceased_type', 'service_applications')) {
            $cols['deceased_type'] = ['type' => 'ENUM', 'constraint' => ['plan_holder', 'beneficiary'], 'null' => true];
        }
        if (! $db->fieldExists('deceased_beneficiary_id', 'service_applications')) {
            $cols['deceased_beneficiary_id'] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }
        if (! $db->fieldExists('claimant_type', 'service_applications')) {
            $cols['claimant_type'] = ['type' => 'ENUM', 'constraint' => ['plan_holder', 'beneficiary', 'emergency_contact'], 'null' => true];
        }
        if ($cols !== []) {
            $this->forge->addColumn('service_applications', $cols);
        }

        // ---- beneficiaries: deceased
        if (! $db->fieldExists('status', 'beneficiaries')) {
            $this->forge->addColumn('beneficiaries', [
                'status'      => ['type' => 'ENUM', 'constraint' => ['active', 'deceased'], 'null' => false, 'default' => 'active'],
                'deceased_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }

        // ---- plans / plan holders: the closed statuses (+ when, why, by whom)
        $db->query("ALTER TABLE plans MODIFY status ENUM('active','inactive','completed','closed_deceased','closed_deceased_owing','closed_by_request') NOT NULL");
        $db->query("ALTER TABLE plan_holders MODIFY status ENUM('active','inactive','deceased','closed') NULL DEFAULT 'active'");
        if (! $db->fieldExists('closed_at', 'plans')) {
            $this->forge->addColumn('plans', [
                'closed_at'     => ['type' => 'DATETIME', 'null' => true],
                'closed_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'closed_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
        }

        // ---- package_avails: the person responsible for the balance
        if (! $db->fieldExists('responsible_name', 'package_avails')) {
            $this->forge->addColumn('package_avails', [
                'responsible_name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'responsible_relationship' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'responsible_contact'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            ]);
        }

        // ---- plan_waivers: what was waived, per cycle
        if (! $db->tableExists('plan_waivers')) {
            $this->forge->addField([
                'waiver_id'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'plan_id'        => ['type' => 'INT', 'constraint' => 11],
                'cycle_number'   => ['type' => 'INT', 'constraint' => 11],
                'amount'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'reason'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'claim'],
                'application_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'waived_at'      => ['type' => 'DATETIME'],
                'approved_by'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
            $this->forge->addKey('waiver_id', true);
            $this->forge->addKey(['plan_id', 'cycle_number']);
            $this->forge->addKey('application_id');
            $this->forge->createTable('plan_waivers', true, ['ENGINE' => 'InnoDB']);
        }

        // ---- plan_closure_requests: "Request to close my plan"
        if (! $db->tableExists('plan_closure_requests')) {
            $this->forge->addField([
                'request_id'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'plan_id'        => ['type' => 'INT', 'constraint' => 11],
                'plan_holder_id' => ['type' => 'INT', 'constraint' => 11],
                'branch_id'      => ['type' => 'INT', 'constraint' => 11],
                'requested_by'   => ['type' => 'INT', 'constraint' => 11],
                'reason'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status'         => ['type' => 'ENUM', 'constraint' => ['pending', 'cancelled', 'closed'], 'default' => 'pending'],
                'created_at'     => ['type' => 'DATETIME'],
                'decided_at'     => ['type' => 'DATETIME', 'null' => true],
                'decided_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
            $this->forge->addKey('request_id', true);
            $this->forge->addKey(['branch_id', 'status']);
            $this->forge->addKey(['plan_holder_id', 'status']);
            $this->forge->createTable('plan_closure_requests', true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $db = $this->db;
        $this->forge->dropTable('plan_closure_requests', true);
        $this->forge->dropTable('plan_waivers', true);
        foreach (['responsible_name', 'responsible_relationship', 'responsible_contact'] as $c) {
            if ($db->fieldExists($c, 'package_avails')) {
                $this->forge->dropColumn('package_avails', $c);
            }
        }
        foreach (['closed_at', 'closed_reason', 'closed_by'] as $c) {
            if ($db->fieldExists($c, 'plans')) {
                $this->forge->dropColumn('plans', $c);
            }
        }
        // Only safe while no plan / holder uses the new values.
        $db->query("UPDATE plans SET status = 'inactive' WHERE status IN ('closed_deceased','closed_deceased_owing','closed_by_request')");
        $db->query("ALTER TABLE plans MODIFY status ENUM('active','inactive','completed') NOT NULL");
        $db->query("UPDATE plan_holders SET status = 'inactive' WHERE status = 'closed'");
        $db->query("ALTER TABLE plan_holders MODIFY status ENUM('active','inactive','deceased') NULL DEFAULT 'active'");
        foreach (['status', 'deceased_at'] as $c) {
            if ($db->fieldExists($c, 'beneficiaries')) {
                $this->forge->dropColumn('beneficiaries', $c);
            }
        }
        foreach (['deceased_type', 'deceased_beneficiary_id', 'claimant_type'] as $c) {
            if ($db->fieldExists($c, 'service_applications')) {
                $this->forge->dropColumn('service_applications', $c);
            }
        }
    }
}
