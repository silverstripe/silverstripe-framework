<?php
// Simulate an execution from framework/cli-script.php, Core.php has too many
// hardcoded assumptions about folder depth of the executing script.

// Make sure display_errors is on
ini_set('display_errors', 1);

// Fake the script name and base
global $_SERVER;
if (!$_SERVER) $_SERVER = array();

$frameworkPath = dirname(dirname(__FILE__));
$frameworkDir = basename($frameworkPath);

$_SERVER['SCRIPT_FILENAME'] = $frameworkPath . DIRECTORY_SEPARATOR . 'cli-script.php';
$_SERVER['SCRIPT_NAME'] = '.' . DIRECTORY_SEPARATOR . $frameworkDir . DIRECTORY_SEPARATOR . 'cli-script.php'; 

if(!defined('BASE_PATH')) define('BASE_PATH', dirname($frameworkPath));

// Copied from cli-script.php, to enable same behaviour through phpunit runner.
if(isset($_SERVER['argv'][2])) {
    $args = array_slice($_SERVER['argv'],2);
    $_GET = array();
    foreach($args as $arg) {
       if(strpos($arg,'=') == false) {
           $_GET['args'][] = $arg;
       } else {
           $newItems = array();
           parse_str( (substr($arg,0,2) == '--') ? substr($arg,2) : $arg, $newItems );
           $_GET = array_merge($_GET, $newItems);
       }
    }
	$_REQUEST = $_GET;
}

// Always flush the manifest for phpunit test runs
$_GET['flush'] = 1;

// Connect to database
require_once $frameworkPath . '/core/Core.php';
require_once $frameworkPath . '/tests/FakeController.php';

global $databaseConfig;
DB::connect($databaseConfig);

// Now set a fake REQUEST_URI
$_SERVER['REQUEST_URI'] = BASE_URL . '/dev';

// Fake a session 
$_SESSION = null;

global $_ALL_CLASSES;
if(isset($_ALL_CLASSES)) {
	$_ALL_CLASSES['parents']['FakeController'] = array_merge($_ALL_CLASSES['parents']['Controller'], array('Controller' => 'Controller'));
}

// Prepare manifest autoloader
$controller = new FakeController();

// Get test manifest
TestRunner::use_test_manifest();

// Remove the error handler so that PHPUnit can add its own
restore_error_handler();
