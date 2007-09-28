<?php

/**
 * Main file that handles every page request.
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

$envFiles = array('../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
        if(@file_exists($envFile)) {
                include($envFile);
                break;
        }
}

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
