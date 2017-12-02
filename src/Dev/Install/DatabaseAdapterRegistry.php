<?php

namespace SilverStripe\Dev\Install;

use InvalidArgumentException;
use SilverStripe\Dev\Deprecation;

/**
 * This class keeps track of the available database adapters
 * and provides a meaning of registering community built
 * adapters in to the installer process.
 *
 * @author Tom Rix
 */
class DatabaseAdapterRegistry
{

    /**
     * Default database connector registration fields
     *
     * @var array
     */
    private static $default_fields = array(
        'server' => array(
            'title' => 'Database server',
            'envVar' => 'SS_DATABASE_SERVER',
            'default' => 'localhost'
        ),
        'username' => array(
            'title' => 'Database username',
            'envVar' => 'SS_DATABASE_USERNAME',
            'default' => 'root'
        ),
        'password' => array(
            'title' => 'Database password',
            'envVar' => 'SS_DATABASE_PASSWORD',
            'default' => 'password'
        ),
        'database' => array(
            'title' => 'Database name',
            'default' => 'SS_mysite',
            'attributes' => array(
                "onchange" => "this.value = this.value.replace(/[\/\\:*?&quot;<>|. \t]+/g,'');"
            )
        ),
    );

    /**
     * Internal array of registered database adapters
     *
     * @var array
     */
    private static $adapters = array();

    /**
     * Add new adapter to the registry
     *
     * @param array $config Associative array of configuration details. This must include:
     *  - title
     *  - class
     *  - helperClass
     *  - supported
     * This SHOULD include:
     *  - fields
     *  - helperPath (if helperClass can't be autoloaded via psr-4/-0)
     *  - missingExtensionText
     *  - module OR missingModuleText
     */
    public static function register($config)
    {
        // Validate config
        $missing = array_diff(['title', 'class', 'helperClass', 'supported'], array_keys($config));
        if ($missing) {
            throw new InvalidArgumentException(
                "Missing database helper config keys: '" . implode("', '", $missing) . "'"
            );
        }

        // Guess missing module text if not given
        if (empty($config['missingModuleText'])) {
            if (empty($config['module'])) {
                $moduleText = 'Module for database connector ' . $config['title'] . 'is missing.';
            } else {
                $moduleText = "The SilverStripe module '" . $config['module'] . "' is missing.";
            }
            $config['missingModuleText'] = $moduleText
                . ' Please install it via composer or from http://addons.silverstripe.org/.';
        }

        // Set missing text
        if (empty($config['missingExtensionText'])) {
            $config['missingExtensionText'] = 'The PHP extension is missing, please enable or install it.';
        }

        // set default fields if none are defined already
        if (!isset($config['fields'])) {
            $config['fields'] = self::$default_fields;
        }

        self::$adapters[$config['class']] = $config;
    }

    /**
     * Unregisters a database connector by classname
     *
     * @param string $class
     */
    public static function unregister($class)
    {
        unset(self::$adapters[$class]);
    }

    /**
     * Detects all _register_database.php files and invokes them.
     * Searches through vendor/*\/* folders only,
     * does not support "legacy" folder location in webroot
     */
    public static function autodiscover()
    {
        // Search through all composer packages in vendor
        foreach (glob(BASE_PATH . '/vendor/*', GLOB_ONLYDIR) as $vendor) {
            foreach (glob($vendor . '/*', GLOB_ONLYDIR) as $directory) {
                if (file_exists($directory . '/_register_database.php')) {
                    include_once($directory . '/_register_database.php');
                }
            }
        }
    }

    /**
     * Detects all _configure_database.php files and invokes them
     * Called by ConfigureFromEnv.php.
     * Searches through vendor/ folder only,
     * does not support "legacy" folder location in webroot
     *
     * @param array $config Config to update. If not provided fall back to global $databaseConfig.
     * In 5.0.0 this will be mandatory and the global will be removed.
     */
    public static function autoconfigure(&$config = null)
    {
        if (!isset($config)) {
            Deprecation::notice('5.0', 'Configuration via global is deprecated');
            global $databaseConfig;
        } else {
            $databaseConfig = $config;
        }
        // Search through all composer packages in vendor, updating $databaseConfig
        foreach (glob(BASE_PATH . '/vendor/*', GLOB_ONLYDIR) as $vendor) {
            foreach (glob($vendor . '/*', GLOB_ONLYDIR) as $directory) {
                if (file_exists($directory . '/_configure_database.php')) {
                    include_once($directory . '/_configure_database.php');
                }
            }
        }
        // Update modified variable
        $config = $databaseConfig;
    }

    /**
     * Return all registered adapters
     *
     * @return array
     */
    public static function get_adapters()
    {
        return self::$adapters;
    }

    /**
     * Returns registry data for a class
     *
     * @param string $class
     * @return array List of adapter properties
     */
    public static function get_adapter($class)
    {
        if (isset(self::$adapters[$class])) {
            return self::$adapters[$class];
        }
        return null;
    }

    /**
     * Retrieves default field configuration
     *
     * @return array
     */
    public static function get_default_fields()
    {
        return self::$default_fields;
    }

    /**
     * Build configuration helper for a given class
     *
     * @param string $databaseClass Name of class
     * @return DatabaseConfigurationHelper|null Instance of helper, or null if cannot be loaded
     */
    public static function getDatabaseConfigurationHelper($databaseClass)
    {
        $adapters = static::get_adapters();
        if (empty($adapters[$databaseClass]) || empty($adapters[$databaseClass]['helperClass'])) {
            return null;
        }

        // Load if path given
        if (isset($adapters[$databaseClass]['helperPath'])) {
            include_once $adapters[$databaseClass]['helperPath'];
        }

        // Construct
        $class = $adapters[$databaseClass]['helperClass'];
        return (class_exists($class)) ? new $class() : null;
    }
}
