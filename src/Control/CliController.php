<?php

namespace SilverStripe\Control;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Base class invoked from CLI rather than the webserver (Cron jobs, handling email bounces).
 * You can call subclasses of CliController directly, which will trigger a
 * call to {@link process()} on every sub-subclass. For instance, calling
 * "sake DailyTask" from the commandline will call {@link process()} on every subclass
 * of DailyTask.
 *
 * @deprecated 5.4.0 Will be replaced with symfony/console commands in a future major release
 */
abstract class CliController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Deprecation::notice('5.4.0', 'Will be replaced with symfony/console commands in a future major release', Deprecation::SCOPE_CLASS);
    }

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
