<?php

/**
 * Base class for URL access to development tools. Currently supports the
 * ; and TaskRunner.
 *
 * @todo documentation for how to add new unit tests and tasks
 * @package framework
 * @subpackage dev
 */
class DevelopmentAdmin extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'build/defaults' => 'buildDefaults',
		'$Action' => '$Action',
		'$Action//$Action/$ID' => 'handleAction',
	);
	
	static $allowed_actions = array( 
        'index', 
        'tests', 
        'jstests', 
        'tasks', 
        'viewmodel', 
        'build', 
        'reset', 
        'viewcode' 
 	);
	
	function init() {
		parent::init();
		
		// Special case for dev/build: Defer permission checks to DatabaseAdmin->init() (see #4957)
		$requestedDevBuild = (stripos($this->request->getURL(), 'dev/build') === 0);
		
		// We allow access to this controller regardless of live-status or ADMIN permission only
		// if on CLI.  Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
		$canAccess = (
			$requestedDevBuild 
			|| Director::isDev() 
			|| Director::is_cli() 
			// Its important that we don't run this check if dev/build was requested
			|| Permission::check("ADMIN")
		);
		if(!$canAccess) return Security::permissionFailure($this);
		
		// check for valid url mapping
		// lacking this information can cause really nasty bugs,
		// e.g. when running Director::test() from a FunctionalTest instance
		global $_FILE_TO_URL_MAPPING;
		if(Director::is_cli()) {
			if(isset($_FILE_TO_URL_MAPPING)) {
				$fullPath = $testPath = BASE_PATH;
				while($testPath && $testPath != "/" && !preg_match('/^[A-Z]:\\\\$/', $testPath)) {
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
					'your _ss_environment.php as instructed on the "sake" page of the doc.silverstripe.org wiki' . "\n";
			}
		}
		
	}
	
	function index() {
		$actions = array(
			"build" => "Build/rebuild this environment.  Call this whenever you have updated your project sources",
			"buildcache" => "Rebuild the static cache, if you're using StaticPublisher",
			"tests" => "See a list of unit tests to run",
			"tests/all" => "Run all tests",
			"tests/startsession" => "Start a test session in your browser (gives you a temporary database with default content)",
			"tests/endsession" => "Ends a test session",
			"jstests" => "See a list of JavaScript tests to run",
			"jstests/all" => "Run all JavaScript tests",
			"tasks" => "See a list of build tasks to run"
		);
		
		// Web mode
		if(!Director::is_cli()) {
			// This action is sake-only right now.
			unset($actions["modules/add"]);
			
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("SilverStripe Development Tools", Director::absoluteBaseURL());
			$base = Director::baseURL();

			echo '<div class="options"><ul>';
			$evenOdd = "odd";
			foreach($actions as $action => $description) {
				echo "<li class=\"$evenOdd\"><a href=\"{$base}dev/$action\"><b>/dev/$action:</b> $description</a></li>\n";
				$evenOdd = ($evenOdd == "odd") ? "even" : "odd";
			}

			$renderer->writeFooter();
		
		// CLI mode
		} else {
			echo "SILVERSTRIPE DEVELOPMENT TOOLS\n--------------------------\n\n";
			echo "You can execute any of the following commands:\n\n";
			foreach($actions as $action => $description) {
				echo "  sake dev/$action: $description\n";
			}
			echo "\n\n";
		}
	}
	
	function tests($request) {
		return TestRunner::create();
	}
	
	function jstests($request) {
		return JSTestRunner::create();
	}
	
	function tasks() {
		return TaskRunner::create();
	}
	
	function build($request) {
		if(Director::is_cli()) {
			$da = DatabaseAdmin::create();
			return $da->handleRequest($request, $this->model);
		} else {
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("Environment Builder", Director::absoluteBaseURL());
			echo "<div class=\"build\">";
			
			$da = DatabaseAdmin::create();
			return $da->handleRequest($request, $this->model);

			echo "</div>";
			$renderer->writeFooter();
		}
	}

	/**
	 * Build the default data, calling requireDefaultRecords on all
	 * DataObject classes
	 * Should match the $url_handlers rule:
	 *		'build/defaults' => 'buildDefaults',
	 */
	function buildDefaults() {
		$da = DatabaseAdmin::create();

		if (!Director::is_cli()) {
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("Defaults Builder", Director::absoluteBaseURL());
			echo "<div style=\"margin: 0 2em\">";
		}

		$da->buildDefaults();

		if (!Director::is_cli()) {
			echo "</div>";
			$renderer->writeFooter();
		}
	}

	function reset() {
		$link = BASE_URL.'/dev/tests/startsession';
		
		return "<p>The dev/reset feature has been removed.  If you are trying to test your site " .
			"with a clean datababase, we recommend that you use " .
			"<a href=\"$link\">dev/test/startsession</a> ".
			"instead.</P>";

	}
	
	function errors() {
		$this->redirect("Debug_");
	}
}
