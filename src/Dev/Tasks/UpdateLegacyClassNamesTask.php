<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DatabaseAdmin;

/**
 * Iterates through a list of class mappings defined at
 * `\SilverStripe\ORM\DatabaseAdmin::$classname_value_remapping`,
 * then renames all references of the old class names to the new ones.
 */
class UpdateLegacyClassNamesTask extends BuildTask
{
    private static $segment = 'UpdateLegacyClassNamesTask';

    protected $title = 'Update Legacy Class Names';

    public function getDescription()
    {
        return <<<TXT
Iterates through a list of class mappings defined at
`\SilverStripe\ORM\DatabaseAdmin::\$classname_value_remapping`,
then renames all references of the old class names to the new ones.
See https://docs.silverstripe.org/en/4/upgrading/upgrading_project/.
TXT;
    }

    public function run($request)
    {
        /** @var DatabaseAdmin $databaseAdmin */
        $databaseAdmin = Injector::inst()->get(DatabaseAdmin::class);
        $databaseAdmin->updateAllLegacyClassNames();
    }
}
