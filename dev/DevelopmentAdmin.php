<?php

/**
 * Base class for development tools.
 * 
 * Configured in framework/_config/dev.yml, with the config key registeredControllers being
 * used to generate the list of links for /dev.
 *
 * @todo documentation for how to add new unit tests and tasks
 * @todo do we need buildDefaults and generatesecuretoken? if so, register in the list
 * @todo cleanup errors() it's not even an allowed action, so can go
 * @todo cleanup index() html building
 * @package framework
 * @subpackage dev
 */
class DevelopmentAdmin extends Controller {

	private static $url_handlers = array(
		'' => 'index',
		'build/defaults' => 'buildDefaults',
		'generatesecuretoken' => 'generatesecuretoken',
		'$Action' => 'runRegisteredController',
	);
	
	private static $allowed_actions = array( 
		'index', 
		'buildDefaults',
		'runRegisteredController',
		'generatesecuretoken',
	);

	public function init() {
		parent::init();

		// Special case for dev/build: Defer permission checks to DatabaseAdmin->init() (see #4957)
		$requestedDevBuild = (stripos($this->getRequest()->getURL(), 'dev/build') === 0);

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
					'your _ss_environment.php as instructed on the "sake" page of the doc.silverstripe.org wiki'."\n";
			}
		}

		// Backwards compat: Default to "draft" stage, which is important
		// for tasks like dev/build which call DataObject->requireDefaultRecords(),
		// but also for other administrative tasks which have assumptions about the default stage.
		Versioned::reading_stage('Stage');
	}

	public function index() {
		// Web mode
		if(!Director::is_cli()) {
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("SilverStripe Development Tools", Director::absoluteBaseURL());
			$base = Director::baseURL();

			echo '<div class="options"><ul>';
			$evenOdd = "odd";
			foreach(self::get_links() as $action => $description) {
				echo "<li class=\"$evenOdd\"><a href=\"{$base}dev/$action\"><b>/dev/$action:</b>"
					. " $description</a></li>\n";
				$evenOdd = ($evenOdd == "odd") ? "even" : "odd";
			}

			$renderer->writeFooter();

		// CLI mode
		} else {
			echo "SILVERSTRIPE DEVELOPMENT TOOLS\n--------------------------\n\n";
			echo "You can execute any of the following commands:\n\n";
			foreach(self::get_links() as $action => $description) {
				echo "  sake dev/$action: $description\n";
			}
			echo "\n\n";
		}
	}

	public function runRegisteredController(SS_HTTPRequest $request){
		$controllerClass = null;
		
		$baseUrlPart = $request->param('Action');
		$reg = Config::inst()->get(__CLASS__, 'registered_controllers');
		if(isset($reg[$baseUrlPart])){
			$controllerClass = $reg[$baseUrlPart]['controller'];
		}
		
		if($controllerClass && class_exists($controllerClass)){
			return $controllerClass::create();
		}
		
		$msg = 'Error: no controller registered in '.__CLASS__.' for: '.$request->param('Action');
		if(Director::is_cli()){
			// in CLI we cant use httpError because of a bug with stuff being in the output already, see DevAdminControllerTest
			throw new Exception($msg);
		}else{
			$this->httpError(500, $msg);
		}
	}

	
	
	
	/*
	 * Internal methods
	 */

	/**
	 * @return array of url => description
	 */
	protected static function get_links(){
		$links = array();
		
		$reg = Config::inst()->get(__CLASS__, 'registered_controllers');
		foreach($reg as $registeredController){
			foreach($registeredController['links'] as $url => $desc){
				$links[$url] = $desc;
			}
		}
		return $links;
	}

	protected function getRegisteredController($baseUrlPart){
		$reg = Config::inst()->get(__CLASS__, 'registered_controllers');
		
		if(isset($reg[$baseUrlPart])){
			$controllerClass = $reg[$baseUrlPart]['controller'];
			return $controllerClass;
		}
		
		return null;
	}
	
	
	
	
	/*
	 * Unregistered (hidden) actions
	 */

	/**
	 * Build the default data, calling requireDefaultRecords on all
	 * DataObject classes
	 * Should match the $url_handlers rule:
	 *		'build/defaults' => 'buildDefaults',
	 */
	public function buildDefaults() {
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

	/**
	 * Generate a secure token which can be used as a crypto key.
	 * Returns the token and suggests PHP configuration to set it.
	 */
	public function generatesecuretoken() {
		$generator = Injector::inst()->create('RandomGenerator');
		$token = $generator->randomToken('sha1');
		$body = <<<TXT
Generated new token. Please add the following code to your YAML configuration:

Security:
  token: $token

TXT;
		$response = new SS_HTTPResponse($body);
		return $response->addHeader('Content-Type', 'text/plain');
	}

	public function errors() {
		$this->redirect("Debug_");
	}
}
