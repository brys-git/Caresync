<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ID Control No. (KKM-<BRANCH>-<YEAR>-<5 digits>): one counter per branch code and year, locked while a number
 * is handed out, and a unique index on plan_holders.id_control_no so a number can never be used twice.
 *
 * Existing data is never deleted or renumbered. The only write is turning blank '' values into NULL (a blank
 * is "no number", and the unique index would otherwise treat two blanks as a clash). If two plan holders
 * already share the same typed number the migration stops BEFORE changing anything and says which.
 */
class CreateIdControlSequences extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('id_control_sequences')) {
            $this->db->query('CREATE TABLE id_control_sequences (
                branch_code CHAR(3) NOT NULL,
                year SMALLINT UNSIGNED NOT NULL,
                last_seq INT UNSIGNED NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (branch_code, year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }

        $dupes = $this->db->query("SELECT id_control_no, COUNT(*) AS n FROM plan_holders WHERE id_control_no IS NOT NULL AND TRIM(id_control_no) <> '' GROUP BY id_control_no HAVING COUNT(*) > 1")->getResultArray();
        if ($dupes !== []) {
            throw new \RuntimeException('Cannot make ID Control No. unique: these numbers are used by more than one plan holder: ' . implode(', ', array_map(static fn ($d) => $d['id_control_no'] . ' (x' . $d['n'] . ')', $dupes)) . '. Fix them first, then migrate again.');
        }

        $this->db->query("UPDATE plan_holders SET id_control_no = NULL WHERE id_control_no IS NOT NULL AND TRIM(id_control_no) = ''");

        $idx = $this->db->query("SHOW INDEX FROM plan_holders WHERE Key_name = 'uq_plan_holders_id_control_no'")->getResultArray();
        if ($idx === []) {
            $this->db->query('ALTER TABLE plan_holders ADD UNIQUE INDEX uq_plan_holders_id_control_no (id_control_no)');
        }
    }

    public function down()
    {
        $idx = $this->db->query("SHOW INDEX FROM plan_holders WHERE Key_name = 'uq_plan_holders_id_control_no'")->getResultArray();
        if ($idx !== []) {
            $this->db->query('ALTER TABLE plan_holders DROP INDEX uq_plan_holders_id_control_no');
        }
        $this->db->query('DROP TABLE IF EXISTS id_control_sequences');
    }
}
