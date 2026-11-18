<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Claim vs avail, and which rules a record was made under.
 *
 * request_type   'claim' = the plan's own package, 'avail' = any other package.
 *                NULL on existing rows (they are not guessed or rewritten).
 * rule_version   'legacy' = made with the old ServiceBalanceService::calculateCoverage()
 *                formula (contributions x 10, minimum 5,000); 'v2' = the Kaagapay claim/avail
 *                rules. Existing rows get 'legacy' from the column default and are never
 *                recalculated; new records are written as 'v2' by ClaimAvailService.
 * rejection_reason  shown to the client on My claims.
 */
class AddClaimAvailFieldsToApplications extends Migration
{
    public function up()
    {
        $apps = [];
        if (! $this->db->fieldExists('request_type', 'service_applications')) {
            $apps['request_type'] = ['type' => 'ENUM', 'constraint' => ['claim', 'avail'], 'null' => true];
        }
        if (! $this->db->fieldExists('rule_version', 'service_applications')) {
            $apps['rule_version'] = ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'legacy'];
        }
        if (! $this->db->fieldExists('rejection_reason', 'service_applications')) {
            $apps['rejection_reason'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
        }
        if ($apps !== []) {
            $this->forge->addColumn('service_applications', $apps);
        }

        if (! $this->db->fieldExists('rule_version', 'service_balances')) {
            $this->forge->addColumn('service_balances', [
                'rule_version' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'legacy'],
            ]);
        }
    }

    public function down()
    {
        foreach (['request_type', 'rule_version', 'rejection_reason'] as $c) {
            if ($this->db->fieldExists($c, 'service_applications')) {
                $this->forge->dropColumn('service_applications', $c);
            }
        }
        if ($this->db->fieldExists('rule_version', 'service_balances')) {
            $this->forge->dropColumn('service_balances', 'rule_version');
        }
    }
}
