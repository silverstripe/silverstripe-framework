<?php

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use SilverStripe\Core\TempFolder;

/**
 * This file is the Framework constants bootstrap. It will prepare some basic common constants.
 *
 * It takes care of:
 *  - Normalisation of $_SERVER values
 *  - Initialisation of necessary constants (mostly paths)
 *
 * Initialized constants:
 * - BASE_URL: Full URL to the webroot, e.g. "http://my-host.com/my-webroot" (no trailing slash).
 * - BASE_PATH: Absolute path to the webroot, e.g. "/var/www/my-webroot" (no trailing slash).
 *   See Director::baseFolder(). Can be overwritten by Config::modify()->set(Director::class, 'alternate_base_folder', ).
 * - TEMP_FOLDER: Absolute path to temporary folder, used for manifest and template caches. Example: "/var/tmp"
 *   See getTempFolder(). No trailing slash.
 * - ASSETS_DIR: Dir for assets folder. e.g. "assets"
 * - ASSETS_PATH: Full path to assets folder. e.g. "/var/www/my-webroot/assets"
 * - THEMES_DIR: Path relative to webroot, e.g. "themes"
 * - THEMES_PATH: Absolute filepath, e.g. "/var/www/my-webroot/themes"
 * - FRAMEWORK_DIR: Path relative to webroot, e.g. "framework"
 * - FRAMEWORK_PATH:Absolute filepath, e.g. "/var/www/my-webroot/framework"
 * - THIRDPARTY_DIR: Path relative to webroot, e.g. "framework/thirdparty"
 * - THIRDPARTY_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/thirdparty"
 */

require_once __DIR__ . '/functions.php';

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT CONFIG

/**
 * Define system paths
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', call_user_func(function () {
        // Determine BASE_PATH based on the composer autoloader
        foreach (debug_backtrace() as $backtraceItem) {
            if (isset($backtraceItem['file']) && preg_match(
                '#^(?<base>.*)(/|\\\\)vendor(/|\\\\)composer(/|\\\\)autoload_real.php#',
                $backtraceItem['file'],
                $matches
            )) {
                return realpath($matches['base']) ?: DIRECTORY_SEPARATOR;
            }
        }

        // Determine BASE_PATH by assuming that this file is framework/src/Core/Constants.php
        //  we can then determine the base path
        $candidateBasePath = rtrim(dirname(dirname(dirname(__DIR__))), DIRECTORY_SEPARATOR);
        // We can't have an empty BASE_PATH.  Making it / means that double-slashes occur in places but that's benign.
        // This likely only happens on chrooted environemnts
        return $candidateBasePath ?: DIRECTORY_SEPARATOR;
    }));
}

// Allow a first class env var to be set that disables .env file loading
if (!getenv('SS_IGNORE_DOT_ENV')) {
    call_user_func(function () {
        foreach ([BASE_PATH, dirname(BASE_PATH)] as $path) {
            try {
                (new Dotenv($path))->load();
            } catch (InvalidPathException $e) {
                // no .env found - no big deal
                continue;
            }
            break;
        }
    });
}


if (!defined('BASE_URL')) {
    define('BASE_URL', call_user_func(function () {
        // Prefer explicitly provided SS_BASE_URL
        $base = getenv('SS_BASE_URL');
        if ($base) {
            $urlParts = parse_url($base);
            // If the protocol is defined, we need 'host'
            if (array_key_exists('host', $urlParts)) {
                return rtrim($urlParts['host'], '/');
            // If the protocol is NOT defined, we need 'path'
            } elseif (array_key_exists('path', $urlParts)) {
                return rtrim($urlParts['path'], '/');
            }
        }

        // Determine the base URL by comparing SCRIPT_NAME to SCRIPT_FILENAME and getting common elements
        // This tends not to work on CLI
        $path = realpath($_SERVER['SCRIPT_FILENAME']);
        if (substr($path, 0, strlen(BASE_PATH)) == BASE_PATH) {
            $urlSegmentToRemove = substr($path, strlen(BASE_PATH));
            if (substr($_SERVER['SCRIPT_NAME'], -strlen($urlSegmentToRemove)) == $urlSegmentToRemove) {
                $baseURL = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($urlSegmentToRemove));
                // ltrim('.'), normalise slashes to '/', and rtrim('/')
                return rtrim(str_replace('\\', '/', ltrim($baseURL, '.')), '/');
            }
        }

        // Assume no base_url
        return '';
    }));
}

define('THEMES_DIR', 'themes');
define('THEMES_PATH', BASE_PATH . DIRECTORY_SEPARATOR . THEMES_DIR);

// Relies on this being in a subdir of the framework.
// If it isn't, or is symlinked to a folder with a different name, you must define FRAMEWORK_DIR

define('FRAMEWORK_PATH', realpath(__DIR__ . '/../../'));
if (strpos(FRAMEWORK_PATH, BASE_PATH) !== 0) {
    throw new Exception("Path error: FRAMEWORK_PATH " . FRAMEWORK_PATH . " not within BASE_PATH " . BASE_PATH);
}
define('FRAMEWORK_DIR', trim(substr(FRAMEWORK_PATH, strlen(BASE_PATH)), DIRECTORY_SEPARATOR));
define('THIRDPARTY_DIR', FRAMEWORK_DIR ? (FRAMEWORK_DIR . '/thirdparty') : 'thirdparty');
define('THIRDPARTY_PATH', FRAMEWORK_PATH . DIRECTORY_SEPARATOR . 'thirdparty');

if (!defined('ASSETS_DIR')) {
    define('ASSETS_DIR', 'assets');
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . ASSETS_DIR);
}

// Custom include path - deprecated
if (defined('CUSTOM_INCLUDE_PATH')) {
    set_include_path(CUSTOM_INCLUDE_PATH . PATH_SEPARATOR   . get_include_path());
}

// Define the temporary folder if it wasn't defined yet
if (!defined('TEMP_FOLDER')) {
    define('TEMP_FOLDER', TempFolder::getTempFolder(BASE_PATH));
}
