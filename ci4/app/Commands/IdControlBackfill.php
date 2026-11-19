<?php

namespace App\Commands;

use App\Services\IdControlService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Gives an ID Control No. to the plan holders who are already active but have none, in the order they were
 * approved (the year of each number is the year of that holder's approval).
 *
 *   php spark idcontrol:backfill          shows the list and the number each would get - changes NOTHING
 *   php spark idcontrol:backfill --run    writes the numbers
 *
 * On a host with no command line (InfinityFree) use deploy/partD_backfill.sql instead.
 */
class IdControlBackfill extends BaseCommand
{
    protected $group       = 'CareSync';
    protected $name        = 'idcontrol:backfill';
    protected $description = 'Numbers existing approved plan holders (list only unless --run is given).';
    protected $options     = ['--run' => 'Write the numbers (without it, only the list is shown).'];

    public function run(array $params)
    {
        $svc  = new IdControlService();
        $plan = $svc->backfillPlan();

        if ($plan === []) {
            CLI::write('Every active plan holder already has an ID Control No.', 'green');

            return;
        }

        CLI::write(count($plan) . ' plan holder(s) to number, oldest approval first:', 'yellow');
        $rows = [];
        foreach ($plan as $p) {
            $rows[] = [$p['proposed'], $p['name'], $p['branch'], substr($p['approved_at'], 0, 10), $p['source']];
        }
        CLI::table($rows, ['ID Control No.', 'Plan holder', 'Branch', 'Approved', 'Date taken from']);

        if (! array_key_exists('run', $params) && CLI::getOption('run') === null) {
            CLI::write('Nothing was changed. Run again with --run to write these numbers.', 'white');

            return;
        }

        $given = $svc->backfill();
        CLI::write(count($given) . ' plan holder(s) numbered.', 'green');
    }
}
