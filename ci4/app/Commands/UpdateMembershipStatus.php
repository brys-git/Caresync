<?php

namespace App\Commands;

use App\Services\MembershipService;
use App\Services\OverduePolicyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class UpdateMembershipStatus extends BaseCommand
{
    protected $group       = 'Membership';
    protected $name        = 'membership:update-status';
    protected $description = 'Update membership states and apply the overdue forfeiture policy. Should run daily.';
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

        $overduePolicyService = new OverduePolicyService();

        // Panel brief, section 9: "Notify plan holder when overdue." Runs
        // before the forfeiture sweep below so a backlog account that's
        // already past both thresholds in the same run is at least notified
        // first, rather than the two checks racing in an arbitrary order.
        CLI::write('Sending overdue notifications...', 'yellow');

        try {
            $overdueResult = $overduePolicyService->notifyOverdueAccounts();

            CLI::write('Overdue notification check completed:', 'green');
            CLI::write('  - Plans checked: ' . $overdueResult['checked'], 'white');
            CLI::write('  - Plan holders notified: ' . $overdueResult['notified'], 'cyan');
        } catch (\Throwable $e) {
            CLI::error('Error: ' . $e->getMessage());
            return 1;
        }

        // Panel brief, section 8: overdue/forfeiture policy (60-day grace
        // period, then months_paid resets to 0). Runs on the same daily
        // cadence as the membership state update above, but is a distinct
        // check - see OverduePolicyService for the exact rule.
        CLI::write('Applying overdue forfeiture policy...', 'yellow');

        try {
            $forfeitureResult = $overduePolicyService->applyForfeitures();

            CLI::write('Overdue forfeiture check completed:', 'green');
            CLI::write('  - Plans checked: ' . $forfeitureResult['checked'], 'white');
            CLI::write('  - Plans forfeited: ' . $forfeitureResult['forfeited'], 'red');
        } catch (\Throwable $e) {
            CLI::error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
