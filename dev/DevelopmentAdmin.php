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
	
	function init() {
		parent::init();
		
		// We allow access to this controller regardless of live-status or ADMIN permission only
		// if on CLI.  Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
		$canAccess = (
			Director::isDev() 
			|| Director::is_cli() 
			|| Permission::check("ADMIN")
		);
		if(!$canAccess) {
			return Security::permissionFailure($this,
				"This page is secured and you need administrator rights to access it. " .
				"Enter your credentials below and we will send you right along.");
		}
		
		// check for valid url mapping
		// lacking this information can cause really nasty bugs,
		// e.g. when running Director::test() from a FunctionalTest instance
		global $_FILE_TO_URL_MAPPING;
		if(Director::is_cli()) {
			if(isset($_FILE_TO_URL_MAPPING)) {
				$fullPath = $testPath = $_SERVER['SCRIPT_FILENAME'];
				while($testPath && $testPath != "/") {
					$matched = false;
					if(isset($_FILE_TO_URL_MAPPING[$testPath])) {
						$matched = true;
					    break;
					}
					$testPath = dirname($testPath);
				}
				if(!$matched) {
					echo 'Warning: You probably want to define '.
						'an entry in $_FILE_TO_URL_MAPPING that covers "' . Director::baseFolder() . '"' . "\n";
				}
			}
			else {
				echo 'Warning: You probably want to define $_FILE_TO_URL_MAPPING in '.
					'your _ss_environment.php as instructed on the "sake" page of the doc.silverstripe.com wiki' . "\n";
			}
		}
		
	}
	
	function index() {
		$actions = array(
			"build" => "Build/rebuild this environment (formerly db/build).  Call this whenever you have updated your project sources",
			"tests" => "See a list of unit tests to run",
			"tests/all" => "Run all tests",
			"jstests" => "See a list of JavaScript tests to run",
			"jstests/all" => "Run all JavaScript tests",
			"modules/add" => "Add a module, for example, 'sake dev/modules/add ecommerce'",
			"tasks" => "See a list of build tasks to run",
			"viewcode" => "Read source code in a literate programming style",
		);
		
		// Web mode
		if(!Director::is_cli()) {
			// This action is sake-only right now.
			unset($actions["modules/add"]);
			
			$renderer = new DebugView();
			$renderer->writeHeader();
			$renderer->writeInfo("Sapphire Development Tools", Director::absoluteBaseURL());
			$base = Director::baseURL();

			echo '<div class="options"><ul>';
			foreach($actions as $action => $description) {
				echo "<li><a href=\"{$base}dev/$action\"><b>/dev/$action:</b> $description</a></li>\n";
			}

			$renderer->writeFooter();
		
		// CLI mode
		} else {
			echo "SAPPHIRE DEVELOPMENT TOOLS\n--------------------------\n\n";
			echo "You can execute any of the following commands:\n\n";
			foreach($actions as $action => $description) {
				echo "  sake dev/$action: $description\n";
			}
			echo "\n\n";
		}
	}
	
	function tests($request) {
		return new TestRunner();
	}
	
	function jstests($request) {
		return new JSTestRunner();
	}
	
	function tasks() {
		return new TaskRunner();
	}
	
	function modules() {
		return new ModuleManager();
	}
	
	function viewmodel() {
		return new ModelViewer();
	}
	
	function build() {
		$renderer = new DebugView();
		$renderer->writeHeader();
		$renderer->writeInfo("Environment Builder (formerly db/build)", Director::absoluteBaseURL());
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