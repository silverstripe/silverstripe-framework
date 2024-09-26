<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\Connect\TempDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)
 * Task is restricted to users with administrator rights or running through CLI.
 */
class CleanupTestDatabasesTask extends BuildTask
{
    protected static string $commandName = 'CleanupTestDatabasesTask';

    protected string $title = 'Deletes all temporary test databases';

    protected static string $description = 'Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)';

    private static array $permissions_for_browser_execution = [
        'ALL_DEV_ADMIN' => false,
        'BUILDTASK_CAN_RUN' => false,
    ];

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        TempDatabase::create()->deleteAll();
        return Command::SUCCESS;
    }
}
