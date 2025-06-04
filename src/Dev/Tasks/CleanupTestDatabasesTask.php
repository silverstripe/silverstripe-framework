<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\Connect\TempDatabase;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)
 * Task is restricted to users with administrator rights or running through CLI.
 */
class CleanupTestDatabasesTask extends BuildTask
{

    private static $segment = 'CleanupTestDatabasesTask';

    protected $title = 'Deletes all temporary test databases';

    protected $description = 'Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)';

    public function run($request)
    {
        if (!$this->canView()) {
            $response = Security::permissionFailure();
            if ($response) {
                $response->output();
            }
            die;
        }
        TempDatabase::create()->deleteAll();
    }

    /**
     * @deprecated 5.4.0 Will be replaced with canRunInBrowser() in a future major release
     */
    public function canView(): bool
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice(
                '5.4.0',
                'Will be replaced with canRunInBrowser() in a future major release'
            );
        });
        return Permission::check('ADMIN') || Director::is_cli();
    }
}
