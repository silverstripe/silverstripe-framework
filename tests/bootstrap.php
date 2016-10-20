<?php

use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;

if(!defined('BASE_PATH')) {
	echo "BASE_PATH hasn't been defined. This probably means that framework/Core/Constants.php hasn't been " .
		"included by Composer's autoloader.\n" .
		"Make sure the you are running your tests via vendor/bin/phpunit and your autoloader is up to date.\n";
	exit(1);
}


/**
 * This bootstraps the SilverStripe system so that phpunit can be run directly on SilverStripe tests.
 */

// Make sure display_errors is on
ini_set('display_errors', 1);

// Fake the script name and base
global $_SERVER;
if (!$_SERVER) $_SERVER = array();

$frameworkPath = dirname(dirname(__FILE__));
$frameworkDir = basename($frameworkPath);

$_SERVER['SCRIPT_FILENAME'] = $frameworkPath . DIRECTORY_SEPARATOR . 'cli-script.php';
$_SERVER['SCRIPT_NAME'] = '.' . DIRECTORY_SEPARATOR . $frameworkDir . DIRECTORY_SEPARATOR . 'cli-script.php';

// Copied from cli-script.php, to enable same behaviour through phpunit runner.
if(isset($_SERVER['argv'][2])) {
	$args = array_slice($_SERVER['argv'],2);
	if(!isset($_GET)) $_GET = array();
	if(!isset($_REQUEST)) $_REQUEST = array();
	foreach($args as $arg) {
		if(strpos($arg,'=') == false) {
			$_GET['args'][] = $arg;
		} else {
			$newItems = array();
			parse_str( (substr($arg,0,2) == '--') ? substr($arg,2) : $arg, $newItems );
			$_GET = array_merge($_GET, $newItems);
		}
	}
	$_REQUEST = array_merge($_REQUEST, $_GET);
}

// Bootstrap mysite / _ss_environment.php from serve-bootstrap.php
require __DIR__ .'/behat/serve-bootstrap.php';

// Connect to database
require_once $frameworkPath . '/Core/Core.php';
require_once $frameworkPath . '/tests/FakeController.php';

global $databaseConfig;
DB::connect($databaseConfig);

// Now set a fake REQUEST_URI
$_SERVER['REQUEST_URI'] = BASE_URL;

// Fake a session
$_SESSION = null;

// Prepare manifest autoloader
$controller = new FakeController();

SapphireTest::use_test_manifest();

SapphireTest::set_is_running_test(true);

// Remove the error handler so that PHPUnit can add its own
restore_error_handler();

