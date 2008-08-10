<?php

/**
 * Base class for URL access to development tools. Currently supports the
 * TestRunner and TaskRunner.
 *
 * @todo documentation for how to add new unit tests and tasks
 * @package sapphire
 * @subpackage dev
 */
class DevelopmentAdmin extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'$Action' => '$Action',
		'$Action//$Action/$ID' => 'handleAction',
	);
	
	function index() {
		$renderer = new DebugView();
		$renderer->writeHeader();
		$renderer->writeInfo("Sapphire Development Tools", Director::absoluteBaseURL());
		echo <<<HTML
			<div class="options">
			<ul>
				<li><a href="tests">/dev/tests: See a list of unit tests to run</a></li>
				<li><a href="tasks">/dev/tasks: See a list of build tasks to run</a></li>
				<li><a href="viewcode">/dev/viewcode: Read source code in a literate programming style</a></li>
				<li><a href="../db/build?flush=1">/db/build?flush=1: Rebuild the database</a></li>
			</ul>
			</div>
HTML;
		$renderer->writeFooter();
	}
	
	function tests($request) {
		$controller = new TestRunner();
		return $controller->handleRequest($request);
	}
	
	function tasks() {
		$controller = new TaskRunner();
		return $controller->handleRequest($request);
	}
	
	function viewcode($request) {
		$controller = new CodeViewer();
		return $controller->handleRequest($request);
	}
	
}

?>