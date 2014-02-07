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
 *  - Sets up the default director rules using {@link Director::$rules}
 * 
 * After that, it calls {@link Director::direct()}, which is responsible for doing most of the 
 * real work.
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
 * Include the defines that set BASE_PATH, etc
 */
require_once('core/Constants.php');

// IIS will sometimes generate this.
if(!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
}

/**
 * Figure out the request URL
 */
global $url;

// PHP 5.4's built-in webserver uses this
if (php_sapi_name() == 'cli-server') {
	$url = $_SERVER['REQUEST_URI'];

	// Querystring args need to be explicitly parsed
	if(strpos($url,'?') !== false) {
		list($url, $query) = explode('?',$url,2);
		parse_str($query, $_GET);
		if ($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
	}

	// Pass back to the webserver for files that exist
	if(file_exists(BASE_PATH . $url) && is_file(BASE_PATH . $url)) return false;

	// Apache rewrite rules use this
} else if (isset($_GET['url'])) {
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

/**
 * Include SilverStripe's core code
 */
require_once('core/startup/ErrorControlChain.php');
require_once('core/startup/ParameterConfirmationToken.php');

$chain = new ErrorControlChain();
$token = new ParameterConfirmationToken('flush');

$chain
	->then(function($chain) use ($token){
		// First, if $_GET['flush'] was set, but no valid token, suppress the flush
		if (isset($_GET['flush']) && !$token->tokenProvided()) {
			unset($_GET['flush']);
		}
		else {
			$chain->setSuppression(false);
		}

		// Load in core
		require_once('core/Core.php');

		// Connect to database
		require_once('model/DB.php');
		global $databaseConfig;
		if ($databaseConfig) DB::connect($databaseConfig);

		// Then if a flush was requested, redirect to it
		if ($token->parameterProvided() && !$token->tokenProvided()) {
			// First, check if we're in dev mode, or the database doesn't have any security data
			$canFlush = Director::isDev(true) || !Security::database_is_ready();

			// Otherwise, we start up the session if needed, then check for admin
			if (!$canFlush) {
				if(!isset($_SESSION) && Session::request_contains_session_id()) {
					Session::start();
				}

				if (Permission::check('ADMIN')) {
					$canFlush = true;
				}
				else {
					$loginPage = Director::absoluteURL(Config::inst()->get('Security', 'login_url'));
					$loginPage .= "?BackURL=" . urlencode($_SERVER['REQUEST_URI']);

					header('location: '.$loginPage, true, 302);
					die;
				}
			}

			// And if we can flush, reload with an authority token
			if ($canFlush) $token->reloadWithToken();
		}
	})
	// Finally if a flush was requested but there was an error while figuring out if it's allowed, do it anyway
	->thenIfErrored(function() use ($token){
		if ($token->parameterProvided() && !$token->tokenProvided()) {
			$token->reloadWithToken();
		}
	})
	->execute();

global $databaseConfig;

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

// Direct away - this is the "main" function, that hands control to the appropriate controller
DataModel::set_inst(new DataModel());
Director::direct($url, DataModel::inst());
