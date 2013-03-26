<?php
/**
 * Base class invoked from CLI rather than the webserver (Cron jobs, handling email bounces).
 * You can call subclasses of CliController directly, which will trigger a
 * call to {@link process()} on every sub-subclass. For instance, calling
 * "sake DailyTask" from the commandline will call {@link process()} on every subclass
 * of DailyTask.
 * 
 * @package framework
 * @subpackage cron
 */
abstract class CliController extends Controller {
	
	private static $allowed_actions = array(
		'index'
	);

	public function init() {
		parent::init();
		// Unless called from the command line, all CliControllers need ADMIN privileges
		if(!Director::is_cli() && !Permission::check("ADMIN")) {
			return Security::permissionFailure();
		}
	}

	public function index() {
		foreach(ClassInfo::subclassesFor($this->class) as $subclass) {
			echo $subclass . "\n";
			$task = new $subclass();
			$task->init();
			$task->process();
		}
	}

	/**
	 * Overload this method to contain the task logic.
	 */
	public function process() {}

}
