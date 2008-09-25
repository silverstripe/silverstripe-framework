<?php

/**
 * Base class for URL access to development tools. Currently supports the
 * ; and TaskRunner.
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
		$base = Director::baseURL();
		echo <<<HTML
			<div class="options">
			<ul>
				<li style="margin-bottom: 1em"><a href="{$base}dev/build"><b>/dev/build:</b> Build/rebuild this environment (formerly db/build).  Call this whenever you have updated your project sources</a></li>
				<li><a href="{$base}dev/tests"><b>/dev/tests:</b> See a list of unit tests to run</a></li>
				<li><a href="{$base}dev/tasks"><b>/dev/tasks:</b> See a list of build tasks to run</a></li>
				<li><a href="{$base}dev/viewcode"><b>/dev/viewcode:</b> Read source code in a literate programming style</a></li>
			</ul>
			</div>
HTML;
		$renderer->writeFooter();
	}
	
	function tests($request) {
		return new TestRunner();
	}
	
	function tasks() {
		return new TaskRunner();
	}
	
	function build() {
		$renderer = new DebugView();
		$renderer->writeHeader();
		$renderer->writeInfo("Environment <i>re</i>Builder (formerly db/build)", Director::absoluteBaseURL());
		echo "<div style=\"margin: 0 2em\">";

		$da = new DatabaseAdmin();
		$da->build();
		
		echo "</div>";
		$renderer->writeFooter();
	}
	
	function errors() {
		Director::redirect("Debug_");
	}
	
	function viewcode($request) {
		return new CodeViewer();
	}
}

?>
