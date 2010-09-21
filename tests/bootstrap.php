<?php
// Simulate an execution from sapphire/cli-script.php, Core.php has too many
// hardcoded assumptions about folder depth of the executing script.

// Overrides paths relative to this file (in sapphire/tests/FullTestSuite.php)
$_SERVER['SCRIPT_FILENAME'] = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'cli-script.php'; 
$_SERVER['SCRIPT_NAME'] = '.' . DIRECTORY_SEPARATOR . 'sapphire' . DIRECTORY_SEPARATOR . 'cli-script.php'; 

define('BASE_PATH', rtrim(dirname(dirname(dirname(__FILE__)))), DIRECTORY_SEPARATOR);

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

if(!class_exists('Object')) {
	require_once("sapphire/core/Core.php");
	global $databaseConfig;
	DB::connect($databaseConfig);
}

$_SERVER['REQUEST_URI'] = BASE_URL . '/dev';

ManifestBuilder::load_test_manifest();