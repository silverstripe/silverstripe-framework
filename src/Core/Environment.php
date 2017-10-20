<?php

namespace SilverStripe\Core;

/**
 * Consolidates access and modification of PHP global variables and settings.
 * This class should be used sparingly, and only if information cannot be obtained
 * from a current {@link HTTPRequest} object.
 *
 * Acts as the primary store for environment variables, including those loaded
 * from .env files. Applications should use Environment::getEnv() instead of php's
 * `getenv` in order to include `.env` configuration, as the system's actual
 * environment variables are treated immutably.
 */
class Environment
{
    /**
     * Set maximum limit allowed for increaseMemoryLimit
     *
     * @var float|null
     */
    protected static $memoryLimitMax = null;

    /**
     * Set maximum limited allowed for increaseTimeLimit
     *
     * @var int|null
     */
    protected static $timeLimitMax = null;

    /**
     * Local overrides for all $_ENV var protected from cross-process operations
     *
     * @var array
     */
    protected static $env = [];

    /**
     * Extract env vars prior to modification
     *
     * @return array List of all super globals
     */
    public static function getVariables()
    {
        // Suppress return by-ref
        return array_merge($GLOBALS, [ 'env' => static::$env ]);
    }

    /**
     * Restore a backed up or modified list of vars to $globals
     *
     * @param array $vars
     */
    public static function setVariables(array $vars)
    {
        foreach ($vars as $varName => $varValue) {
            if ($varName === 'env') {
                continue;
            }
            $GLOBALS[$varName] = $varValue;
        }
        if (array_key_exists('env', $vars)) {
            static::$env = $vars['env'];
        }
    }

    /**
     * Increase the memory limit to the given level if it's currently too low.
     * Only increases up to the maximum defined in {@link setMemoryLimitMax()},
     * and defaults to the 'memory_limit' setting in the PHP configuration.
     *
     * @param string|float|int $memoryLimit A memory limit string, such as "64M".  If omitted, unlimited memory will be set.
     * @return bool true indicates a successful change, false a denied change.
     */
    public static function increaseMemoryLimitTo($memoryLimit = -1)
    {
        $memoryLimit = Convert::memstring2bytes($memoryLimit);
        $curLimit = Convert::memstring2bytes(ini_get('memory_limit'));

        // Can't go higher than infinite
        if ($curLimit < 0) {
            return true;
        }

        // Check hard maximums
        $max = static::getMemoryLimitMax();
        if ($max > 0 && ($memoryLimit < 0 || $memoryLimit > $max)) {
            $memoryLimit = $max;
        }

        // Increase the memory limit if it's too low
        if ($memoryLimit < 0) {
            ini_set('memory_limit', '-1');
        } elseif ($memoryLimit > $curLimit) {
            ini_set('memory_limit', Convert::bytes2memstring($memoryLimit));
        }

        return true;
    }

    /**
     * Set the maximum allowed value for {@link increaseMemoryLimitTo()}.
     * The same result can also be achieved through 'suhosin.memory_limit'
     * if PHP is running with the Suhosin system.
     *
     * @param string|float $memoryLimit Memory limit string or float value
     */
    static function setMemoryLimitMax($memoryLimit)
    {
        if (isset($memoryLimit) && !is_numeric($memoryLimit)) {
            $memoryLimit = Convert::memstring2bytes($memoryLimit);
        }
        static::$memoryLimitMax = $memoryLimit;
    }

    /**
     * @return int Memory limit in bytes
     */
    public static function getMemoryLimitMax()
    {
        if (static::$memoryLimitMax === null) {
            return Convert::memstring2bytes(ini_get('memory_limit'));
        }
        return static::$memoryLimitMax;
    }

    /**
     * Increase the time limit of this script. By default, the time will be unlimited.
     * Only works if 'safe_mode' is off in the PHP configuration.
     * Only values up to {@link getTimeLimitMax()} are allowed.
     *
     * @param int $timeLimit The time limit in seconds.  If omitted, no time limit will be set.
     * @return Boolean TRUE indicates a successful change, FALSE a denied change.
     */
    public static function increaseTimeLimitTo($timeLimit = null)
    {
        // Check vs max limit
        $max = static::getTimeLimitMax();
        if ($max > 0 && $timeLimit > $max) {
            return false;
        }

        if (!$timeLimit) {
            set_time_limit(0);
        } else {
            $currTimeLimit = ini_get('max_execution_time');
            // Only increase if its smaller
            if ($currTimeLimit > 0 && $currTimeLimit < $timeLimit) {
                set_time_limit($timeLimit);
            }
        }
        return true;
    }

    /**
     * Set the maximum allowed value for {@link increaseTimeLimitTo()};
     *
     * @param int $timeLimit Limit in seconds
     */
    public static function setTimeLimitMax($timeLimit)
    {
        static::$timeLimitMax = $timeLimit;
    }

    /**
     * @return Int Limit in seconds
     */
    public static function getTimeLimitMax()
    {
        return static::$timeLimitMax;
    }

    /**
     * Get value of environment variable
     *
     * @param string $name
     * @return mixed Value of the environment variable, or false if not set
     */
    public static function getEnv($name)
    {
        switch (true) {
            case array_key_exists($name, static::$env):
                return static::$env[$name];
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                return getenv($name);
        }
    }

    /**
     * Set environment variable using php.ini syntax.
     * Acts as a process-isolated version of putenv()
     * Note: This will be parsed via parse_ini_string() which handles quoted values
     *
     * @param string $string Setting to assign in KEY=VALUE or KEY="VALUE" syntax
     */
    public static function putEnv($string)
    {
        // Parse name-value pairs
        $envVars = parse_ini_string($string) ?: [];
        foreach ($envVars as $name => $value) {
            self::setEnv($name, $value);
        }
    }

    /**
     * Set environment variable via $name / $value pair
     *
     * @param string $name
     * @param string $value
     */
    public static function setEnv($name, $value)
    {
        static::$env[$name] = $value;
    }
}
