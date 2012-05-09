<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.3.2 or higher, preferably PHP 5.3.4+.                    **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

/**
 * @package framework
 * @subpackage core
 */

if (version_compare(phpversion(), '5.3.2', '<')) {
	header("HTTP/1.1 500 Server Error");
	echo str_replace('$PHPVersion', phpversion(), file_get_contents("dev/install/php5-required.html"));
	die();
}

/**
 * Main file that handles every page request.
 *
 * The main.php does a number of set-up activities for the request.
 * 
 *  - Includes the first one of the following files that it finds: (root)/_ss_environment.php, 
 *    (root)/../_ss_environment.php, or (root)/../../_ss_environment.php
 *  - Gets an up-to-date manifest from {@link ManifestBuilder}
 *  - Sets up error handlers with {@link Debug::loadErrorHandlers()}
 *  - Calls {@link DB::connect()}, passing it the global variable $databaseConfig that should 
 *    be defined in an _config.php
 *  - Sets up the default director rules using {@link Director::addRules()}
 * 
 * After that, it calls {@link Director::direct()}, which is responsible for doing most of the 
 * real work.
 *
 * Finally, main.php will use {@link Profiler} to show a profile if the querystring variable 
 * "debug_profile" is set.
 *
 * CONFIGURING THE WEBSERVER
 *
 * To use SilverStripe, every request that doesn't point directly to a file should be rewritten to
 * framework/main.php?url=(url).  For example, http://www.example.com/about-us/rss would be rewritten 
 * to http://www.example.com/framework/main.php?url=about-us/rss
 *
 * It's important that requests that point directly to a file aren't rewritten; otherwise, visitors 
 * won't be able to download any CSS, JS, image files, or other downloads.
 *
 * On Apache, RewriteEngine can be used to do this.
 *
 * @package framework
 * @subpackage core
 * @see Director::direct()
 */


/**
 * Include SilverStripe's core code
 */
require_once('core/Core.php');

// IIS will sometimes generate this.
if(!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
}

// Apache rewrite rules use this
if (isset($_GET['url'])) {
	$url = $_GET['url'];
	// IIS includes get variables in url
	$i = strpos($url, '?');
	if($i !== false) {
		$url = substr($url, 0, $i);
	}
	
// Lighttpd uses this
} else {
	if(strpos($_SERVER['REQUEST_URI'],'?') !== false) {
		list($url, $query) = explode('?', $_SERVER['REQUEST_URI'], 2);
		parse_str($query, $_GET);
		if ($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
	} else {
		$url = $_SERVER["REQUEST_URI"];
	}
}

// Remove base folders from the URL if webroot is hosted in a subfolder
if (substr(strtolower($url), 0, strlen(BASE_URL)) == strtolower(BASE_URL)) $url = substr($url, strlen(BASE_URL));

if (isset($_GET['debug_profile'])) {
	Profiler::init();
	Profiler::mark('all_execution');
	Profiler::mark('main.php init');
}

// Connect to database
require_once('model/DB.php');

// Redirect to the installer if no database is selected
if(!isset($databaseConfig) || !isset($databaseConfig['database']) || !$databaseConfig['database']) {
	if(!file_exists(BASE_PATH . '/install.php')) {
		die('SilverStripe Framework requires a $databaseConfig defined.');
	}
	$s = (isset($_SERVER['SSL']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) ? 's' : '';
	$installURL = "http$s://" . $_SERVER['HTTP_HOST'] . BASE_URL . '/install.php';
	
	// The above dirname() will equate to "\" on Windows when installing directly from http://localhost (not using
	// a sub-directory), this really messes things up in some browsers. Let's get rid of the backslashes
	$installURL = str_replace('\\', '', $installURL);
	
	header("Location: $installURL");
	die();
}

if (isset($_GET['debug_profile'])) Profiler::mark('DB::connect');
DB::connect($databaseConfig);
if (isset($_GET['debug_profile'])) Profiler::unmark('DB::connect');

if (isset($_GET['debug_profile'])) Profiler::unmark('main.php init');


// Direct away - this is the "main" function, that hands control to the appropriate controller
DataModel::set_inst(new DataModel());
Director::direct($url, DataModel::inst());

if (isset($_GET['debug_profile'])) {
	Profiler::unmark('all_execution');
	if(!Director::isLive()) {
		Profiler::show(isset($_GET['profile_trace']));
	}
}
