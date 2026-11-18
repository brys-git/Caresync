<?php

namespace App\Commands;

use App\Services\ClaimAvailService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cancels avails whose down payment was not paid in time ("Cancelled - no down payment").
 *
 * The site does not depend on this: the same check runs by itself whenever an avail is read
 * (ClaimAvailService::expireDownPayments), because the free host has no scheduled tasks.
 * This command is for running the check by hand, e.g. on a local copy:  php spark avail:expire
 */
class ExpireAvails extends BaseCommand
{
    protected $group       = 'CareSync';
    protected $name        = 'avail:expire';
    protected $description = 'Cancels avails whose down payment was not paid within availDownPaymentDays.';

    public function run(array $params)
    {
        ClaimAvailService::resetExpiryMemory();
        $count = (new ClaimAvailService())->expireDownPayments(null);

        CLI::write($count === 0 ? 'No avails to cancel.' : "Cancelled {$count} avail(s) with no down payment.", $count === 0 ? 'white' : 'yellow');
    }
}
