<?php
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
 *   See Director::baseFolder(). Can be overwritten by Config::inst()->update('Director', 'alternate_base_folder', ).
 * - TEMP_FOLDER: Absolute path to temporary folder, used for manifest and template caches. Example: "/var/tmp"
 *   See getTempFolder(). No trailing slash.
 * - MODULES_DIR: Not used at the moment
 * - MODULES_PATH: Not used at the moment
 * - THEMES_DIR: Path relative to webroot, e.g. "themes"
 * - THEMES_PATH: Absolute filepath, e.g. "/var/www/my-webroot/themes"
 * - FRAMEWORK_DIR: Path relative to webroot, e.g. "framework"
 * - FRAMEWORK_PATH:Absolute filepath, e.g. "/var/www/my-webroot/framework"
 * - FRAMEWORK_ADMIN_DIR: Path relative to webroot, e.g. "framework/admin"
 * - FRAMEWORK_ADMIN_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/admin"
 * - THIRDPARTY_DIR: Path relative to webroot, e.g. "framework/thirdparty"
 * - THIRDPARTY_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/thirdparty"
 * - TRUSTED_PROXY: true or false, depending on whether the X-Forwarded-* HTTP
 *   headers from the given client are trustworthy (e.g. from a reverse proxy).
 */

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT CONFIG

/**
 * Validate whether the request comes directly from a trusted server or not
 * This is necessary to validate whether or not the values of X-Forwarded-
 * or Client-IP HTTP headers can be trusted
 */
if (!defined('TRUSTED_PROXY')) {
    $trusted = true; // will be false by default in a future release

    if (getenv('BlockUntrustedProxyHeaders') // Legacy setting (reverted from documentation)
        || getenv('BlockUntrustedIPs') // Documented setting
        || getenv('SS_TRUSTED_PROXY_IPS')
    ) {
        $trusted = false;

        if (getenv('SS_TRUSTED_PROXY_IPS') !== 'none') {
            if (getenv('SS_TRUSTED_PROXY_IPS') === '*') {
                $trusted = true;
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                if (!class_exists('SilverStripe\\Control\\Util\\IPUtils')) {
                    require_once 'Control/IPUtils.php';
                };
                $trusted = SilverStripe\Control\Util\IPUtils::checkIP($_SERVER['REMOTE_ADDR'], explode(',', getenv('SS_TRUSTED_PROXY_IPS')));
            }
        }
    }

    /**
     * Declare whether or not the connecting server is a trusted proxy
     */
    define('TRUSTED_PROXY', $trusted);
}

/**
 * Define system paths
 */
// Determine BASE_PATH based on the composer autoloader
if (!defined('BASE_PATH')) {
    foreach (debug_backtrace() as $backtraceItem) {
        if (preg_match('#^(.*)\/vendor/composer/autoload_real.php#', $backtraceItem['file'], $matches)) {
            define('BASE_PATH', $matches[1]);
            break;
        }
    }
}

// Determine BASE_PATH by assuming that this file is framework/src/Core/Constants.php
if (!defined('BASE_PATH')) {
    //  we can then determine the base path
    $candidateBasePath = rtrim(dirname(dirname(dirname(dirname(__FILE__)))), DIRECTORY_SEPARATOR);
    // We can't have an empty BASE_PATH.  Making it / means that double-slashes occur in places but that's benign.
    // This likely only happens on chrooted environemnts
    if ($candidateBasePath == '') {
        $candidateBasePath = DIRECTORY_SEPARATOR;
    }
    define('BASE_PATH', $candidateBasePath);
}

// Allow a first class env var to be set that disables .env file loading
if (!getenv('SS_IGNORE_DOT_ENV')) {
    foreach (array(
                 BASE_PATH,
                 dirname(BASE_PATH),
             ) as $path) {
        try {
            (new \Dotenv\Dotenv($path))->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            // no .env found - no big deal
            continue;
        }
        break;
    }
}

if (!defined('BASE_URL')) {
    // Determine the base URL by comparing SCRIPT_NAME to SCRIPT_FILENAME and getting common elements
    $path = realpath($_SERVER['SCRIPT_FILENAME']);
    if (substr($path, 0, strlen(BASE_PATH)) == BASE_PATH) {
        $urlSegmentToRemove = substr($path, strlen(BASE_PATH));
        if (substr($_SERVER['SCRIPT_NAME'], -strlen($urlSegmentToRemove)) == $urlSegmentToRemove) {
            $baseURL = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($urlSegmentToRemove));
            define('BASE_URL', rtrim($baseURL, DIRECTORY_SEPARATOR));
        }
    }

    // If that didn't work, failover to the old syntax.  Hopefully this isn't necessary, and maybe
    // if can be phased out?
    if (!defined('BASE_URL')) {
        $dir = (strpos($_SERVER['SCRIPT_NAME'], 'index.php') !== false)
            ? dirname($_SERVER['SCRIPT_NAME'])
            : dirname(dirname($_SERVER['SCRIPT_NAME']));
        define('BASE_URL', rtrim($dir, DIRECTORY_SEPARATOR));
    }
}
define('MODULES_DIR', 'modules');
define('MODULES_PATH', BASE_PATH . '/' . MODULES_DIR);
define('THEMES_DIR', 'themes');
define('THEMES_PATH', BASE_PATH . '/' . THEMES_DIR);
// Relies on this being in a subdir of the framework.
// If it isn't, or is symlinked to a folder with a different name, you must define FRAMEWORK_DIR

define('FRAMEWORK_PATH', realpath(__DIR__ . '/../../'));
if (strpos(FRAMEWORK_PATH, BASE_PATH) === 0) {
    define('FRAMEWORK_DIR', trim(substr(FRAMEWORK_PATH, strlen(BASE_PATH)), DIRECTORY_SEPARATOR));
    $frameworkDirSlashSuffix = FRAMEWORK_DIR ? FRAMEWORK_DIR . '/' : '';
} else {
    throw new Exception("Path error: FRAMEWORK_PATH " . FRAMEWORK_PATH . " not within BASE_PATH " . BASE_PATH);
}

define('FRAMEWORK_ADMIN_DIR', $frameworkDirSlashSuffix . 'admin');
define('FRAMEWORK_ADMIN_PATH', FRAMEWORK_PATH . '/admin');

define('THIRDPARTY_DIR', $frameworkDirSlashSuffix . 'thirdparty');
define('THIRDPARTY_PATH', FRAMEWORK_PATH . '/thirdparty');

define('ADMIN_THIRDPARTY_DIR', FRAMEWORK_ADMIN_DIR . '/thirdparty');
define('ADMIN_THIRDPARTY_PATH', BASE_PATH . '/' . ADMIN_THIRDPARTY_DIR);

if (!defined('ASSETS_DIR')) {
    define('ASSETS_DIR', 'assets');
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', BASE_PATH . '/' . ASSETS_DIR);
}

// Custom include path - deprecated
if (defined('CUSTOM_INCLUDE_PATH')) {
    set_include_path(CUSTOM_INCLUDE_PATH . PATH_SEPARATOR   . get_include_path());
}

/**
 * Define the temporary folder if it wasn't defined yet
 */
require_once 'Core/TempPath.php';

if (!defined('TEMP_FOLDER')) {
    define('TEMP_FOLDER', getTempFolder(BASE_PATH));
}
