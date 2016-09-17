<?php

use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;


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

// DATABASE BOOTSTRAP

if (!defined('SS_ENVIRONMENT_TYPE')) {
	define('SS_ENVIRONMENT_TYPE', 'dev');
}

if (!defined('SS_DATABASE_CLASS') && !defined('SS_DATABASE_USERNAME')) {
	// The default settings let us define the database config via environment vars
	// Database connection, including PDO and legacy ORM support
	switch(getenv('DB')) {
	case "PGSQL";
		define('SS_DATABASE_CLASS', getenv('PDO') ? 'PostgrePDODatabase' : 'PostgreSQLDatabase');
		define('SS_DATABASE_USERNAME', 'postgres');
		define('SS_DATABASE_PASSWORD', '');
		break;

	case "SQLITE":
		define('SS_DATABASE_CLASS', getenv('PDO') ? 'SQLite3PDODatabase' : 'SQLite3Database');
		define('SS_DATABASE_USERNAME', 'root');
		define('SS_DATABASE_PASSWORD', '');
		define('SS_SQLITE_DATABASE_PATH', ':memory:');
		break;

	default:
		define('SS_DATABASE_CLASS', getenv('PDO') ? 'MySQLPDODatabase' : 'MySQLDatabase');
		define('SS_DATABASE_USERNAME', 'root');
		define('SS_DATABASE_PASSWORD', '');
	}

	define('SS_DATABASE_SERVER', '127.0.0.1');
	define('SS_DATABASE_CHOOSE_NAME', true);
}

// Ensure Director::protocolAndHost() works
if (empty($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

// Asset folder
if(!file_exists(BASE_PATH . '/assets')) {
	mkdir(BASE_PATH . '/assets', 02775);
}

// Default database settings
global $project;
$project = 'mysite';

global $database;
$database = '';

require_once(__DIR__ . '/../conf/ConfigureFromEnv.php');

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

