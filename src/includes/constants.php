<?php

use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\EnvironmentLoader;
use SilverStripe\Core\TempFolder;

/**
 * This file is the Framework constants bootstrap. It will prepare some basic common constants.
 *
 * It takes care of:
 *  - Initialisation of necessary constants (mostly paths)
 *
 * Initialized constants:
 * - BASE_URL: Full URL to the webroot, e.g. "http://my-host.com/my-webroot" (no trailing slash).
 * - BASE_PATH: Absolute path to the webroot, e.g. "/var/www/my-webroot" (no trailing slash).
 *   See Director::baseFolder(). Can be overwritten by Config::modify()->set(Director::class, 'alternate_base_folder', ).
 * - TEMP_PATH: Absolute path to temporary folder, used for manifest and template caches. Example: "/var/tmp"
 *   See getTempFolder(). No trailing slash.
 * - ASSETS_DIR: Dir for assets folder. e.g. "assets"
 * - ASSETS_PATH: Full path to assets folder. e.g. "/var/www/my-webroot/assets"
 * - THEMES_DIR: Path relative to webroot, e.g. "themes"
 * - THEMES_PATH: Absolute filepath, e.g. "/var/www/my-webroot/themes"
 * - FRAMEWORK_DIR: Path relative to webroot, e.g. "framework"
 * - FRAMEWORK_PATH:Absolute filepath, e.g. "/var/www/my-webroot/framework"
 * - PUBLIC_DIR: Webroot path relative to project root, e.g. "public" or ""
 * - PUBLIC_PATH: Absolute path to webroot, e.g. "/var/www/project/public"
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

// Set public webroot dir / path
if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', is_dir(BASE_PATH . DIRECTORY_SEPARATOR . 'public') ? 'public' : '');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', PUBLIC_DIR ? BASE_PATH . DIRECTORY_SEPARATOR . PUBLIC_DIR : BASE_PATH);
}

// Allow a first class env var to be set that disables .env file loading
if (!Environment::getEnv('SS_IGNORE_DOT_ENV')) {
    call_user_func(function () {
        $loader = new EnvironmentLoader();
        foreach ([BASE_PATH, dirname(BASE_PATH)] as $path) {
            // Stop searching after first `.env` file is loaded
            $dotEnvFile = $path . DIRECTORY_SEPARATOR . '.env';
            if ($loader->loadFile($dotEnvFile)) {
                break;
            }
        }
    });
}

// Validate SS_BASE_URL is absolute
if (Environment::getEnv('SS_BASE_URL') && !preg_match('#^(\w+:)?//.*#', Environment::getEnv('SS_BASE_URL'))) {
    call_user_func(function () {
        $base = Environment::getEnv('SS_BASE_URL');
        user_error(
            "SS_BASE_URL should be an absolute url with protocol "
            . "(http://$base) or without protocol (//$base)",
            E_USER_WARNING
        );
        // Treat as protocol-less absolute url
        $base = '//' . $base;
        Environment::setEnv('SS_BASE_URL', $base);
    });
}

if (!defined('BASE_URL')) {
    define('BASE_URL', call_user_func(function () {
        // Prefer explicitly provided SS_BASE_URL
        $base = Environment::getEnv('SS_BASE_URL');
        if ($base) {
            // Strip relative path from SS_BASE_URL
            return rtrim(parse_url($base, PHP_URL_PATH), '/');
        }

        // Unless specified, use empty string for base in CLI
        if (in_array(php_sapi_name(), ['cli', 'phpdbg'])) {
            return '';
        }

        // Determine the base URL by comparing SCRIPT_NAME to SCRIPT_FILENAME and getting common elements
        // This tends not to work on CLI
        $path = Convert::slashes($_SERVER['SCRIPT_FILENAME']);
        $scriptName = Convert::slashes($_SERVER['SCRIPT_NAME'], '/');

        // Ensure script is served from public folder (otherwise error)
        if (stripos($path, PUBLIC_PATH) !== 0) {
            return '';
        }

        // Get entire url following PUBLIC_PATH
        $urlSegmentToRemove = Convert::slashes(substr($path, strlen(PUBLIC_PATH)), '/');
        if (substr($scriptName, -strlen($urlSegmentToRemove)) !== $urlSegmentToRemove) {
            return '';
        }

        // Remove this from end of SCRIPT_NAME to get url to base
        $baseURL = substr($scriptName, 0, -strlen($urlSegmentToRemove));
        $baseURL = rtrim(ltrim($baseURL, '.'), '/');

        // When htaccess redirects from /base to /base/public folder, we need to only include /public
        // in the BASE_URL if it's also present in the request
        if ($baseURL
            && PUBLIC_DIR
            && isset($_SERVER['REQUEST_URI'])
            && substr($baseURL, -strlen(PUBLIC_DIR)) === PUBLIC_DIR
        ) {
            $requestURI = $_SERVER['REQUEST_URI'];
            // Check if /base/public or /base are in the request
            foreach ([$baseURL, dirname($baseURL)] as $candidate) {
                if (stripos($requestURI, $candidate) === 0) {
                    return $candidate;
                }
            }
            // Ambiguous
            return '';
        }

        return $baseURL;
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
    call_user_func(function () {
        $paths = [
            BASE_PATH,
            (PUBLIC_DIR ? PUBLIC_DIR : null),
            ASSETS_DIR
        ];
        define('ASSETS_PATH', implode(DIRECTORY_SEPARATOR, array_filter($paths)));
    });
}

// Custom include path - deprecated
if (defined('CUSTOM_INCLUDE_PATH')) {
    set_include_path(CUSTOM_INCLUDE_PATH . PATH_SEPARATOR . get_include_path());
}

// Define the temporary folder if it wasn't defined yet
if (!defined('TEMP_PATH')) {
    if (defined('TEMP_FOLDER')) {
        define('TEMP_PATH', TEMP_FOLDER);
    } else {
        define('TEMP_PATH', TempFolder::getTempFolder(BASE_PATH));
    }
}

// Define the temporary folder for backwards compatibility
if (!defined('TEMP_FOLDER')) {
    define('TEMP_FOLDER', TEMP_PATH);
}
