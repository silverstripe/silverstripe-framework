<?php

/**
 * Main file that handles every page request.
 *
 * The main.php does a number of set-up activities for the request.
 * 
 *  - Includes the first one of the following files that it finds: (root)/_ss_environment.php, (root)/../_ss_environment.php, or (root)/../../_ss_environment.php
 *  - Gets an up-to-date manifest from {@link ManifestBuilder}
 *  - Sets up error handlers with {@link Debug::loadErrorHandlers()}
 *  - Calls {@link DB::connect()}, passing it the global variable $databaseConfig that should be defined in an _config.php
 *  - Sets up the default director rules using {@link Director::addRules()}
 * 
 * After that, it calls {@link Director::direct()}, which is responsible for doing most of the real work.
 *
 * Finally, main.php will use {@link Profiler} to show a profile if the querystring variable "debug_profile" is set.
 *
 * CONFIGURING THE WEBSERVER
 *
 * To use Sapphire, every request that doesn't point directly to a file should be rewritten to sapphire/main.php?url=(url).
 * For example, http://www.example.com/about-us/rss would be rewritten to http://www.example.com/sapphire/main.php?url=about-us/rss
 *
 * It's important that requests that point directly to a file aren't rewritten; otherwise, visitors won't be able to download
 * any CSS, JS, image files, or other downloads.
 *
 * On Apache, RewriteEngine can be used to do this.
 *
 * @package sapphire
 * @subpackage core
 * @see Director::direct()
 */

/**
 * Include _ss_environment.php file
 */
$envFiles = array('../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
        if(@file_exists($envFile)) {
                include($envFile);
                break;
        }
}

/**
 * Include Sapphire's core code
 */
require_once("core/Core.php");

header("Content-type: text/html; charset=\"utf-8\"");
if(function_exists('mb_http_output')) {
	mb_http_output('UTF-8');
	mb_internal_encoding('UTF-8');
}

if(get_magic_quotes_gpc()) {
	if($_REQUEST) stripslashes_recursively($_REQUEST);
	if($_GET) stripslashes_recursively($_GET);
	if($_POST) stripslashes_recursively($_POST);
}
if(isset($_REQUEST['trace'])) {
	apd_set_pprof_trace();
}

// Ensure we have enough memory
$memString = ini_get("memory_limit");
switch(strtolower(substr($memString,-1))) {
	case "k":
		$memory = round(substr($memString,0,-1)*1024);
		break;
	case "m":
		$memory = round(substr($memString,0,-1)*1024*1024);
		break;
	case "g":
		$memory = round(substr($memString,0,-1)*1024*1024*1024);
		break;
	default:
		$memory = round($memString);
}
// Check we have at least 32M
if($memory < (32 * 1024 * 1024)) {
	// Increase memory limit
	ini_set('memory_limit', '32M');
}


require_once("core/ManifestBuilder.php");
require_once("core/ClassInfo.php");
require_once('core/Object.php');
require_once('core/control/Director.php');
require_once('filesystem/Filesystem.php');
require_once("core/Session.php");

 // If this is a dev site, enable php error reporting
if(Director::isDev()) {
	error_reporting(E_ALL);
}

Session::start();

if(isset($_GET['url'])) {
	$url = $_GET['url'];
	
// Lighttpd uses this
} else {
	list($url, $query) = explode('?', $_SERVER['REQUEST_URI'], 2);
	parse_str($query, $_GET);
	if($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
}

if(ManifestBuilder::staleManifest()){
	ManifestBuilder::compileManifest();
}

require_once(MANIFEST_FILE);

if(isset($_GET['debugmanifest'])) Debug::show(file_get_contents(MANIFEST_FILE));

if(isset($_GET['debug_profile'])) Profiler::init();
if(isset($_GET['debug_profile'])) Profiler::mark('all_execution');

if(isset($_GET['debug_profile'])) Profiler::mark('main.php init');

// Default director
Director::addRules(10, array(
	'Security/$Action/$ID' => 'Security',
	'db/$Action' => 'DatabaseAdmin',
	'$Controller/$Action/$ID/$OtherID' => '*',
	'images/$Action/$Class/$ID/$Field' => 'Image_Uploader',
	'' => 'RootURLController',
	'sitemap.xml' => 'GoogleSitemap',
));

Director::addRules(1, array(
	'$URLSegment/$Action/$ID/$OtherID' => 'ModelAsController',
));

// Load error handlers
Debug::loadErrorHandlers();

// Connect to database
require_once("core/model/DB.php");

if(isset($_GET['debug_profile'])) Profiler::mark('DB::connect');
DB::connect($databaseConfig);
if(isset($_GET['debug_profile'])) Profiler::unmark('DB::connect');


// Get the request URL
$baseURL = dirname(dirname($_SERVER['SCRIPT_NAME']));



if(substr($url,0,strlen($baseURL)) == $baseURL) $url = substr($url,strlen($baseURL));

// Direct away - this is the "main" function, that hands control to the appropriate controller
if(isset($_GET['debug_profile'])) Profiler::unmark('main.php init');

Director::direct($url);

if(isset($_GET['debug_profile'])) {
	Profiler::unmark('all_execution');
	Profiler::show(isset($_GET['profile_trace']));
}

?>
