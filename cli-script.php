#!/usr/bin/php5
<?php

if(isset($_SERVER['HTTP_HOST'])) {
	echo "cli-script.php can't be run from a web request, you have to run it on the command-line.";
	die();
}

/**
 * File similar to main.php designed for command-line scripts
 * 
 * This file lets you execute Sapphire requests from the command-line.  The URL is passed as the first argument to the scripts.
 * 
 * @package sapphire
 * @subpackage core
 */

/**
 * Process arguments and load them into the $_GET and $_REQUEST arrays
 * For example,
 * sake my/url somearg otherarg key=val --otherkey=val third=val&fourth=val
 *
 * Will result int he following get data:
 *   args => array('somearg', 'otherarg'),
 *   key => val
 *   otherkey => val
 *   third => val
 *   fourth => val
 */
if(isset($_SERVER['argv'][2])) {
    $args = array_slice($_SERVER['argv'],2);
    $_GET = array();
    foreach($args as $arg) {
       if(strpos($arg,'=') == false) {
           $_GET['args'][] = $arg;
       } else {
           $newItems = parse_str( (substr($arg,0,2) == '--') ? substr($arg,2) : $arg );
           $_GET = array_merge($_GET, $newItems);
       }
    }
	$_REQUEST = $_GET;
}

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
chdir(dirname($_SERVER['SCRIPT_FILENAME']));

/**
 * Include Sapphire's core code
 */
require_once("core/Core.php");

header("Content-type: text/html; charset=\"utf-8\"");
if(function_exists('mb_http_output')) {
	mb_http_output('UTF-8');
	mb_internal_encoding('UTF-8');
}

// figure out the server configuration
if( preg_match( '/(test\.totallydigital\.co\.nz|dev\.totallydigital\.co\.nz\/test)(.*)/', $_SERVER['SCRIPT_FILENAME'], $nameMatch ) ) {
	$_SERVER['SCRIPT_NAME'] = $nameMatch[2];
	$_SERVER['HTTP_HOST'] = $nameMatch[1];
	$envType = 'test';
} elseif( preg_match( '/dev\.totallydigital\.co\.nz(.*)/', $_SERVER['SCRIPT_FILENAME'], $nameMatch ) ) {
	$_SERVER['SCRIPT_NAME'] = $nameMatch[1];
	$envType = 'dev';
} elseif( preg_match( '/\/sites\/[^\/]+\/www(.*)/', $_SERVER['SCRIPT_FILENAME'], $nameMatch ) ) {
	$_SERVER['SCRIPT_NAME'] = $nameMatch[1];	
	$envType = 'live';
} elseif( preg_match( '/\/sites\/[^\/]+(.*)/', $_SERVER['SCRIPT_FILENAME'], $nameMatch ) ) {
	$_SERVER['SCRIPT_NAME'] = $nameMatch[1];
} elseif(isset($_SERVER['SCRIPT_NAME'])) {
	$envType = 'live';
} else {
	echo "Error: could not determine server configuration {$_SERVER['SCRIPT_FILENAME']}\n";
	exit();	
}

// set request method (doesn't allow POST through cli)
$_SERVER['REQUEST_METHOD'] = "GET";	

if($_REQUEST && get_magic_quotes_gpc()) {
	stripslashes_recursively($_REQUEST);
}

if(isset($_REQUEST['trace'])) apd_set_pprof_trace();

require_once("core/ManifestBuilder.php");
require_once("core/ClassInfo.php");
require_once('core/Object.php');
require_once('core/control/Director.php');
require_once('filesystem/Filesystem.php');
require_once("core/Session.php");

Session::start();

$envFiles = array('../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
	if(file_exists($envFile)) {
		include($envFile);
		break;
	}
}

// Find the URL of this script
if(isset($_FILE_TO_URL_MAPPING)) {
	$fullPath = $testPath = $_SERVER['SCRIPT_FILENAME'];
	while($testPath && $testPath != "/") {
		if(isset($_FILE_TO_URL_MAPPING[$testPath])) {
			$url = $_FILE_TO_URL_MAPPING[$testPath] . substr($fullPath,strlen($testPath));
			$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST);
			$_SERVER['SCRIPT_NAME'] = parse_url($url, PHP_URL_PATH);
			$_SERVER['REQUEST_PORT'] = parse_url($url, PHP_URL_PORT);
			break;
		}
		$testPath = dirname($testPath);
	}
}


if(ManifestBuilder::staleManifest()){
	ManifestBuilder::compileManifest();
}		

require_once(MANIFEST_FILE);

if(isset($_GET['debugmanifest'])) Debug::show(file_get_contents(MANIFEST_FILE));

//if(!isset(Director::$environment_type) && $envType) Director::set_environment_type($envType);

// Load error handlers
Debug::loadErrorHandlers();

// Connect to database
require_once("core/model/DB.php");

DB::connect($databaseConfig);


// Get the request URL
$url = $_SERVER['argv'][1];
$_SERVER['REQUEST_URI'] = "/$url";

// Direct away - this is the "main" function, that hands control to the apporopriate controllerx
Director::direct($url);

?>
