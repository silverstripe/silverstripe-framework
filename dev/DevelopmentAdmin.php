<?php

/**
 * Base class for URL access to development tools. Currently supports the
 * TestRunner and TaskRunner.
 *
 * @todo documentation for how to add new unit tests and tasks
 */
class DevelopmentAdmin extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'$Action' => '$Action'
	);
	
	function index() {
		return <<<HTML
			<h1>sapphire development tools</h1>
			<ul>
				<li><a href="dev/tests">/dev/tests: Run all unit tests</a></li>
				<li><a href="dev/tasks">/dev/tasks: See a list of build tasks to run</a></li>
				<li><a href="db/build?flush=1">/db/build?flush=1: Rebuild the database</a></li>
			</ul>
HTML;
	}
	
	function tests() {
		if(isset($this->urlParams['NestedAction'])) {
			Director::redirect("TestRunner/only/" . $this->urlParams['NestedAction']);
		} else {
			Director::redirect("TestRunner/");
		}
	}
	
	function tasks($request) {
		return new TaskRunner();
	}
	
}

?>