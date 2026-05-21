<?php

namespace App\Commands;

use App\Services\MembershipService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class UpdateMembershipStatus extends BaseCommand
{
    protected $group       = 'Membership';
    protected $name        = 'membership:update-status';
    protected $description = 'Update membership states based on payment coverage dates. Should run daily.';
    protected $usage       = 'php spark membership:update-status';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params = [])
    {
        CLI::write('Starting membership status update...', 'yellow');

        try {
            $membershipService = new MembershipService();
            $result = $membershipService->updateMembershipStates();

            CLI::write('Membership status update completed:', 'green');
            CLI::write('  - Active members: ' . $result['active'], 'white');
            CLI::write('  - Delinquent members: ' . $result['delinquent'], 'yellow');
            CLI::write('  - Suspended members: ' . $result['suspended'], 'red');
            CLI::write('  - Updated: ' . $result['updated'], 'cyan');
        } catch (\Throwable $e) {
            CLI::error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
