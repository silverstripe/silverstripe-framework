<?php
/**
 * @package sapphire
 * @subpackage dev
 */
class TaskRunner extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'$TaskName' => 'runTask'
	);
	
	function index() {
		$tasks = ClassInfo::subclassesFor('BuildTask');
		if(Director::is_cli()) {
			echo "Tasks available:\n\n";
			foreach($tasks as $task) echo " * $task: sake dev/tasks/$task\n";
		} else {
			echo "<h1>Tasks available</h1>\n";
			echo "<ul>";
			foreach($tasks as $task) {
				echo "<li><a href=\"$task\">$task</a></li>\n";
			}
			echo "</ul>";
		}
	}
	
	function runTask($request) {
		$TaskName = $request->param('TaskName');
		if (class_exists($TaskName) && is_subclass_of($TaskName, 'BuildTask')) {
			if(Director::is_cli()) echo "Running task '$TaskName'...\n\n";
			else echo "<h1>Running task '$TaskName'...</h1>\n";

			$task = new $TaskName();
			if (!$task->isDisabled()) $task->run($request);
		} else {
			echo "Build task '$TaskName' not found.";
			if(class_exists($TaskName)) echo "  It isn't a subclass of BuildTask.";
			echo "\n";
		}
	}
	
}

?>