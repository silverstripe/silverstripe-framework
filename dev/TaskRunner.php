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
		echo "<ul>";
		foreach($tasks as $task) {
			echo "<li><a href=\"$task\">$task</a></li>";
		}
		echo "</ul>";
	}
	
	function runTask($request) {
		echo "<h1>Running task...</h1>";
		$TaskName = $request->param('TaskName');
		if (class_exists($TaskName)) {
			$task = new $TaskName();
			if (!$task->isDisabled()) $task->run($request);
		}
	}
	
}

?>