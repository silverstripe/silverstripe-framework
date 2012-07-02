<?php
/**
 * @package framework
 * @subpackage dev
 */
class TaskRunner extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'$TaskName' => 'runTask'
	);
	
	static $allowed_actions = array(
		'index',
		'runTask',
	);
	
	function init() {
		parent::init();

		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		$canAccess = (
			Director::isDev() 
			// We need to ensure that DevelopmentAdminTest can simulate permission failures when running
			// "dev/tasks" from CLI.
			|| (Director::is_cli() && !$isRunningTests)
			|| Permission::check("ADMIN")
		);
		if(!$canAccess) return Security::permissionFailure($this);
	}
	
	function index() {
		$tasks = $this->getTasks();

		// Web mode
		if(!Director::is_cli()) {
			$renderer = new DebugView();
			$renderer->writeHeader();
			$renderer->writeInfo("SilverStripe Development Tools: Tasks", Director::absoluteBaseURL());
			$base = Director::absoluteBaseURL();
			
			echo "<div class=\"options\">";
			echo "<ul>";
			foreach($tasks as $task) {
				echo "<li><p>";
				echo "<a href=\"{$base}dev/tasks/" . $task['class'] . "\">" . $task['title'] . "</a><br />";
				echo "<span class=\"description\">" . $task['description'] . "</span>";
				echo "</p></li>\n";
			}
			echo "</ul></div>";

			$renderer->writeFooter();
		// CLI mode
		} else {
			echo "SILVERSTRIPE DEVELOPMENT TOOLS: Tasks\n--------------------------\n\n";
			foreach($tasks as $task) {
				echo " * $task: sake dev/tasks/" . $task['class'] . "\n";
			}
		}
	}
	
	function runTask($request) {
		$taskName = $request->param('TaskName');
		if (class_exists($taskName) && is_subclass_of($taskName, 'BuildTask')) {
			$title = singleton($taskName)->getTitle();
			if(Director::is_cli()) echo "Running task '$title'...\n\n";
			elseif(!Director::is_ajax()) echo "<h1>Running task '$title'...</h1>\n";

			$task = new $taskName();
			if ($task->isEnabled()) $task->run($request);
			else echo "<p>{$title} is disabled</p>";
		} else {
			echo "Build task '$taskName' not found.";
			if(class_exists($taskName)) echo "  It isn't a subclass of BuildTask.";
			echo "\n";
		}
	}
	
	/**
	 * @return array Array of associative arrays for each task (Keys: 'class', 'title', 'description')
	 */
	protected function getTasks() {
		$availableTasks = array();
		
		$taskClasses = ClassInfo::subclassesFor('BuildTask');
		// remove the base class
		array_shift($taskClasses);
		
		if($taskClasses) foreach($taskClasses as $class) {
			if(!singleton($class)->isEnabled()) continue;
			$desc = (Director::is_cli()) ? Convert::html2raw(singleton($class)->getDescription()) : singleton($class)->getDescription();
			$availableTasks[] = array(
				'class' => $class,
				'title' => singleton($class)->getTitle(),
				'description' => $desc,
			);
		}
		
		return $availableTasks;
	}
	
}


