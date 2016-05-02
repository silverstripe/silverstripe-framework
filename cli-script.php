<?php

/**
 * File similar to main.php designed for command-line scripts
 *
 * This file lets you execute SilverStripe requests from the command-line.  The URL is passed as the first argument to
 * the scripts.
 *
 * @package framework
 * @subpackage core
 */

/**
 * Ensure that people can't access this from a web-server
 */
if(isset($_SERVER['HTTP_HOST'])) {
	echo "cli-script.php can't be run from a web request, you have to run it on the command-line.";
	die();
}

/**
 * Identify the cli-script.php file and change to its container directory, so that require_once() works
 */
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
chdir(dirname($_SERVER['SCRIPT_FILENAME']));

/**
 * Process arguments and load them into the $_GET and $_REQUEST arrays
 * For example,
 * sake my/url somearg otherarg key=val --otherkey=val third=val&fourth=val
 *
 * Will result in the following get data:
 *   args => array('somearg', 'otherarg'),
 *   key => val
 *   otherkey => val
 *   third => val
 *   fourth => val
 */
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

// Set 'url' GET parameter
if(isset($_SERVER['argv'][1])) {
	$_REQUEST['url'] = $_SERVER['argv'][1];
	$_GET['url'] = $_SERVER['argv'][1];
}

/**
 * Include SilverStripe's core code
 */
require_once("core/Core.php");

global $databaseConfig;

// We don't have a session in cli-script, but this prevents errors
$_SESSION = null;

require_once("model/DB.php");


// Connect to database
if(!isset($databaseConfig) || !isset($databaseConfig['database']) || !$databaseConfig['database']) {
	echo "\nPlease configure your database connection details.  You can do this by creating a file
called _ss_environment.php in either of the following locations:\n\n";
	echo " - " .  BASE_PATH  . DIRECTORY_SEPARATOR . "_ss_environment.php\n - ";
	echo dirname(BASE_PATH) . DIRECTORY_SEPARATOR . "_ss_environment.php\n\n";
	echo <<<ENVCONTENT

Put the following content into this file:
--------------------------------------------------
<?php

/* Change this from 'dev' to 'live' for a production environment. */
define('SS_ENVIRONMENT_TYPE', 'dev');

/* This defines a default database user */
define('SS_DATABASE_SERVER', 'localhost');
define('SS_DATABASE_USERNAME', '<user>');
define('SS_DATABASE_PASSWORD', '<password>');
define('SS_DATABASE_NAME', '<database>');
--------------------------------------------------

Once you have done that, run 'composer install' or './framework/sake dev/build' to create
an empty database.

For more information, please read this page in our docs:
http://docs.silverstripe.org/en/getting_started/environment_management/


ENVCONTENT;
	exit(1);
}
DB::connect($databaseConfig);


// Get the request URL from the querystring arguments
$url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;
if(!$url) {
	echo 'Please specify an argument to cli-script.php/sake. For more information, visit'
		. ' http://docs.silverstripe.org/en/developer_guides/cli' . "\n";
	die();
}

$_SERVER['REQUEST_URI'] = BASE_URL . '/' . $url;

// Direct away - this is the "main" function, that hands control to the apporopriate controller
DataModel::set_inst(new DataModel());
Director::direct($url, DataModel::inst());


