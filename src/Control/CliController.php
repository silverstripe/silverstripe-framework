<?php

namespace SilverStripe\Control;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Base class invoked from CLI rather than the webserver (Cron jobs, handling email bounces).
 * You can call subclasses of CliController directly, which will trigger a
 * call to {@link process()} on every sub-subclass. For instance, calling
 * "sake DailyTask" from the commandline will call {@link process()} on every subclass
 * of DailyTask.
 */
abstract class CliController extends Controller
{

    private static $allowed_actions = [
        'index'
    ];

    protected function init()
    {
        parent::init();
        // Unless called from the command line, all CliControllers need ADMIN privileges
        if (!Director::is_cli() && !Permission::check("ADMIN")) {
            Security::permissionFailure();
        }
    }

    public function index()
    {
        foreach (ClassInfo::subclassesFor(static::class) as $subclass) {
            echo $subclass . "\n";
            /** @var CliController $task */
            $task = Injector::inst()->create($subclass);
            $task->doInit();
            $task->process();
        }
    }

    /**
     * Overload this method to contain the task logic.
     */
    public function process()
    {
    }
}
