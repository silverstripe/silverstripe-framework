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
			"reset" => "Reset this environment - truncate the database and rebuild.  This is useful after testing to start with a fresh working copy",
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
		if(Director::is_cli()) {
			$da = new DatabaseAdmin();
			$da->build();
		} else {
			$renderer = new DebugView();
			$renderer->writeHeader();
			$renderer->writeInfo("Environment Builder (formerly db/build)", Director::absoluteBaseURL());
			echo "<div style=\"margin: 0 2em\">";

			$da = new DatabaseAdmin();
			$da->build();

			echo "</div>";
			$renderer->writeFooter();
		}
	}
	
	function reset() {
		global $databaseConfig;
		$databaseName = $databaseConfig['database'];
		
		if(Director::is_cli()) {
			echo "\nPlease run dev/reset from your web browser.\n";
		} else {
			$renderer = new DebugView();
			$renderer->writeHeader();
			$renderer->writeInfo('Database reset', 'Completely truncate and rebuild the current database');
			echo '<div style="margin: 0 2em">';

			if(isset($_GET['done'])) {
				echo "<p style=\"color: green\"><b>$databaseName</b> has been completely truncated and rebuilt.</p>";
				echo "<p>Note: If you had <i>SS_DEFAULT_ADMIN_USERNAME</i> and <i>SS_DEFAULT_ADMIN_PASSWORD</i>
						defined in your <b>_ss_environment.php</b> file, a default admin Member record has been created
						with those credentials.</p>";
			} else {
				echo $this->ResetForm()->renderWith('Form');
			}

			echo '</div>';
			$renderer->writeFooter();
		}
	}
	
	function ResetForm() {
		global $databaseConfig;
		$databaseName = $databaseConfig['database'];
		
		if(!Session::get('devResetRandNumber')) {
			$rand = rand(5,500);
			Session::set('devResetRandNumber', $rand);
		} else {
			$rand = Session::get('devResetRandNumber');
		}
		
		$form = new Form(
			$this,
			'ResetForm',
			new FieldSet(
				new LiteralField('ResetWarning', "<p style=\"color: red\">WARNING: This will completely
					destroy ALL existing data in <b>$databaseName</b>! &nbsp; Press the button below to
					confirm this action.</p>"),
				new HiddenField('devResetRandNumber', '', $rand)
			),
			new FieldSet(
				new FormAction('doReset', 'Reset and completely rebuild the database')
			)
		);
		
		$form->setFormAction(Director::absoluteBaseURL() . 'dev/ResetForm');
		
		return $form;
	}
	
	function doReset($data, $form, $request) {
		if(!isset($data['devResetRandNumber'])) {
			Director::redirectBack();
			return false;
		}
		
		// Avoid accidental database resets by checking the posted number to the one in session
		if(Session::get('devResetRandNumber') != $data['devResetRandNumber']) {
			Director::redirectBack();
			return false;
		}
		
		$da = new DatabaseAdmin();
		$da->clearAllData();
		
		// If _ss_environment.php has some constants set for default admin, set these up in the request
		$_REQUEST['username'] = defined('SS_DEFAULT_ADMIN_USERNAME') ? SS_DEFAULT_ADMIN_USERNAME : null;
		$_REQUEST['password'] = defined('SS_DEFAULT_ADMIN_PASSWORD') ? SS_DEFAULT_ADMIN_PASSWORD : null;
		
		$da->build();
		
		Session::clear('devResetRandNumber');
		Director::redirect(Director::absoluteBaseURL() . 'dev/reset?done=1');
	}
	
	function errors() {
		Director::redirect("Debug_");
	}
	
	function viewcode($request) {
		return new CodeViewer();
	}
}

?>