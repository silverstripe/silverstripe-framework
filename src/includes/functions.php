<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\i18n;

///////////////////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS

/**
 * Creates a class instance by the "singleton" design pattern.
 * It will always return the same instance for this class,
 * which can be used for performance reasons and as a simple
 * way to access instance methods which don't rely on instance
 * data (e.g. the custom SilverStripe static handling).
 *
 * @param string $className
 * @return mixed
 */
function singleton($className)
{
    if ($className === Config::class) {
        throw new InvalidArgumentException("Don't pass Config to singleton()");
    }
    if (!isset($className)) {
        throw new InvalidArgumentException("singleton() Called without a class");
    }
    if (!is_string($className)) {
        throw new InvalidArgumentException(
            "singleton() passed bad class_name: " . var_export($className, true)
        );
    }
    return Injector::inst()->get($className);
}

function project()
{
    global $project;
    return $project;
}

/**
 * This is the main translator function. Returns the string defined by $entity according to the
 * currently set locale.
 *
 * Also supports pluralisation of strings. Pass in a `count` argument, as well as a
 * default value with `|` pipe-delimited options for each plural form.
 *
 * @param string $entity Entity that identifies the string. It must be in the form
 * "Namespace.Entity" where Namespace will be usually the class name where this
 * string is used and Entity identifies the string inside the namespace.
 * @param mixed $arg,... Additional arguments are parsed as such:
 *  - Next string argument is a default. Pass in a `|` pipe-delimeted value with `{count}`
 *    to do pluralisation.
 *  - Any other string argument after default is context for i18nTextCollector
 *  - Any array argument in any order is an injection parameter list. Pass in a `count`
 *    injection parameter to pluralise.
 * @return string
 */
function _t($entity, $arg = null)
{
    // Pass args directly to handle deprecation
    return call_user_func_array([i18n::class, '_t'], func_get_args());
}

/**
 * Increase the memory limit to the given level if it's currently too low.
 * Only increases up to the maximum defined in {@link set_increase_memory_limit_max()},
 * and defaults to the 'memory_limit' setting in the PHP configuration.
 *
 * @param string|int $memoryLimit A memory limit string, such as "64M".  If omitted, unlimited memory will be set.
 * @return Boolean TRUE indicates a successful change, FALSE a denied change.
 */
function increase_memory_limit_to($memoryLimit = -1)
{
    $curLimit = ini_get('memory_limit');

    // Can't go higher than infinite
    if ($curLimit == -1) {
        return true;
    }

    // Check hard maximums
    $max = get_increase_memory_limit_max();

    if ($max && $max != -1 && translate_memstring($memoryLimit) > translate_memstring($max)) {
        return false;
    }

    // Increase the memory limit if it's too low
    if ($memoryLimit == -1 || translate_memstring($memoryLimit) > translate_memstring($curLimit)) {
        ini_set('memory_limit', $memoryLimit);
    }

    return true;
}

$_increase_memory_limit_max = ini_get('memory_limit');

/**
 * Set the maximum allowed value for {@link increase_memory_limit_to()}.
 * The same result can also be achieved through 'suhosin.memory_limit'
 * if PHP is running with the Suhosin system.
 *
 * @param string $memoryLimit Memory limit string
 */
function set_increase_memory_limit_max($memoryLimit)
{
    global $_increase_memory_limit_max;
    $_increase_memory_limit_max = $memoryLimit;
}

/**
 * @return string Memory limit string
 */
function get_increase_memory_limit_max()
{
    global $_increase_memory_limit_max;
    return $_increase_memory_limit_max;
}

/**
 * Increases the XDebug parameter max_nesting_level, which limits how deep recursion can go.
 * Only does anything if (a) xdebug is installed and (b) the new limit is higher than the existing limit
 *
 * @param int $limit - The new limit to increase to
 */
function increase_xdebug_nesting_level_to($limit)
{
    if (function_exists('xdebug_enable')) {
        $current = ini_get('xdebug.max_nesting_level');
        if ((int)$current < $limit) {
            ini_set('xdebug.max_nesting_level', $limit);
        }
    }
}

/**
 * Turn a memory string, such as 512M into an actual number of bytes.
 *
 * @param string $memString A memory limit string, such as "64M"
 * @return float
 */
function translate_memstring($memString)
{
    switch (strtolower(substr($memString, -1))) {
        case "k":
            return round(substr($memString, 0, -1) * 1024);
        case "m":
            return round(substr($memString, 0, -1) * 1024 * 1024);
        case "g":
            return round(substr($memString, 0, -1) * 1024 * 1024 * 1024);
        default:
            return round($memString);
    }
}

/**
 * Increase the time limit of this script. By default, the time will be unlimited.
 * Only works if 'safe_mode' is off in the PHP configuration.
 * Only values up to {@link get_increase_time_limit_max()} are allowed.
 *
 * @param int $timeLimit The time limit in seconds.  If omitted, no time limit will be set.
 * @return Boolean TRUE indicates a successful change, FALSE a denied change.
 */
function increase_time_limit_to($timeLimit = null)
{
    $max = get_increase_time_limit_max();
    if ($max != -1 && $max != null && $timeLimit > $max) {
        return false;
    }

    if (!ini_get('safe_mode')) {
        if (!$timeLimit) {
            set_time_limit(0);
            return true;
        } else {
            $currTimeLimit = ini_get('max_execution_time');
            // Only increase if its smaller
            if ($currTimeLimit && $currTimeLimit < $timeLimit) {
                set_time_limit($timeLimit);
            }
            return true;
        }
    } else {
        return false;
    }
}

/**
 * Set the maximum allowed value for {@link increase_timeLimit_to()};
 *
 * @param int $timeLimit Limit in seconds
 */
function set_increase_time_limit_max($timeLimit)
{
    global $_increase_time_limit_max;
    $_increase_time_limit_max = $timeLimit;
}

/**
 * @return Int Limit in seconds
 */
function get_increase_time_limit_max()
{
    global $_increase_time_limit_max;
    return $_increase_time_limit_max;
}


/**
 * Returns the temporary folder path that silverstripe should use for its cache files.
 *
 * @param string $base The base path to use for determining the temporary path
 * @return string Path to temp
 */
function getTempFolder($base = null)
{
    $parent = getTempParentFolder($base);

    // The actual temp folder is a subfolder of getTempParentFolder(), named by username
    $subfolder = $parent . DIRECTORY_SEPARATOR . getTempFolderUsername();

    if (!@file_exists($subfolder)) {
        mkdir($subfolder);
    }

    return $subfolder;
}

/**
 * Returns as best a representation of the current username as we can glean.
 *
 * @return string
 */
function getTempFolderUsername()
{
    $user = getenv('APACHE_RUN_USER');
    if (!$user) {
        $user = getenv('USER');
    }
    if (!$user) {
        $user = getenv('USERNAME');
    }
    if (!$user && function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
        $userDetails = posix_getpwuid(posix_getuid());
        $user = $userDetails['name'];
    }
    if (!$user) {
        $user = 'unknown';
    }
    $user = preg_replace('/[^A-Za-z0-9_\-]/', '', $user);
    return $user;
}

/**
 * Return the parent folder of the temp folder.
 * The temp folder will be a subfolder of this, named by username.
 * This structure prevents permission problems.
 *
 * @param string $base
 * @return string
 * @throws Exception
 */
function getTempParentFolder($base = null)
{
    if (!$base && defined('BASE_PATH')) {
        $base = BASE_PATH;
    }

    // first, try finding a silverstripe-cache dir built off the base path
    $tempPath = $base . DIRECTORY_SEPARATOR . 'silverstripe-cache';
    if (@file_exists($tempPath)) {
        if ((fileperms($tempPath) & 0777) != 0777) {
            @chmod($tempPath, 0777);
        }
        return $tempPath;
    }

    // failing the above, try finding a namespaced silverstripe-cache dir in the system temp
    $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
        'silverstripe-cache-php' . preg_replace('/[^\w-\.+]+/', '-', PHP_VERSION) .
        str_replace(array(' ', '/', ':', '\\'), '-', $base);
    if (!@file_exists($tempPath)) {
        $oldUMask = umask(0);
        @mkdir($tempPath, 0777);
        umask($oldUMask);

    // if the folder already exists, correct perms
    } else {
        if ((fileperms($tempPath) & 0777) != 0777) {
            @chmod($tempPath, 0777);
        }
    }

    $worked = @file_exists($tempPath) && @is_writable($tempPath);

    // failing to use the system path, attempt to create a local silverstripe-cache dir
    if (!$worked) {
        $tempPath = $base . DIRECTORY_SEPARATOR . 'silverstripe-cache';
        if (!@file_exists($tempPath)) {
            $oldUMask = umask(0);
            @mkdir($tempPath, 0777);
            umask($oldUMask);
        }

        $worked = @file_exists($tempPath) && @is_writable($tempPath);
    }

    if (!$worked) {
        throw new Exception(
            'Permission problem gaining access to a temp folder. ' .
            'Please create a folder named silverstripe-cache in the base folder ' .
            'of the installation and ensure it has the correct permissions'
        );
    }

    return $tempPath;
}
