<?php
/**
 * @package framework
 * @subpackage dev
 */
class TaskRunner extends Controller {
	
	private static $url_handlers = array(
		'' => 'index',
		'$TaskName' => 'runTask'
	);
	
	private static $allowed_actions = array(
		'index',
		'runTask',
	);
	
	public function init() {
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
	
	public function index() {
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
				echo "<a href=\"{$base}dev/tasks/" . $task['segment'] . "\">" . $task['title'] . "</a><br />";
				echo "<span class=\"description\">" . $task['description'] . "</span>";
				echo "</p></li>\n";
			}
			echo "</ul></div>";

			$renderer->writeFooter();
		// CLI mode
		} else {
			echo "SILVERSTRIPE DEVELOPMENT TOOLS: Tasks\n--------------------------\n\n";
			foreach($tasks as $task) {
				echo " * $task[title]: sake dev/tasks/" . $task['segment'] . "\n";
			}
		}
	}
	
	public function runTask($request) {
		$name = $request->param('TaskName');
		$tasks = $this->getTasks();

		$title = function ($content) {
			printf(Director::is_cli() ? "%s\n\n" : '<h1>%s</h1>', $content);
		};

		$message = function ($content) {
			printf(Director::is_cli() ? "%s\n" : '<p>%s</p>', $content);
		};

		foreach ($tasks as $task) {
			if ($task['segment'] == $name) {
				$inst = Injector::inst()->create($task['class']);
				$title(sprintf('Running Task %s', $inst->getTitle()));

				if (!$inst->isEnabled()) {
					$message('The task is disabled');
					return;
				}

				$inst->run($request);
				return;
			}
		}

		$message(sprintf('The build task "%s" could not be found', $name));
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
			$desc = (Director::is_cli()) 
				? Convert::html2raw(singleton($class)->getDescription()) 
				: singleton($class)->getDescription();
				
			$availableTasks[] = array(
				'class' => $class,
				'title' => singleton($class)->getTitle(),
				'segment' => str_replace('\\', '-', $class),
				'description' => $desc,
			);
		}
		
		return $availableTasks;
	}

}


