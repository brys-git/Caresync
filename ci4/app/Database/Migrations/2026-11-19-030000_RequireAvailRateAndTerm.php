<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * An avail record must always carry the down-payment rate, the amounts and the term it was
 * approved with. These columns had a default of 0, which would let an insert that forgot them
 * slip through as "0% down, 0 instalment". Now they are NOT NULL with no default, so an insert
 * without them fails (on servers running in strict SQL mode). ClaimAvailService::assertAvailTerms()
 * is the second guard, for servers that are not in strict mode.
 */
class RequireAvailRateAndTerm extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('package_avails')) {
            return;
        }

        $this->db->query('ALTER TABLE package_avails
            MODIFY down_payment_rate   DECIMAL(5,4)  NOT NULL,
            MODIFY down_payment_amount DECIMAL(10,2) NOT NULL,
            MODIFY financed_amount     DECIMAL(10,2) NOT NULL,
            MODIFY monthly_instalment  DECIMAL(10,2) NOT NULL');
    }

    public function down()
    {
        if (! $this->db->tableExists('package_avails')) {
            return;
        }

        $this->db->query('ALTER TABLE package_avails
            MODIFY down_payment_rate   DECIMAL(5,4)  NOT NULL DEFAULT 0,
            MODIFY down_payment_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            MODIFY financed_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
            MODIFY monthly_instalment  DECIMAL(10,2) NOT NULL DEFAULT 0');
    }
}
