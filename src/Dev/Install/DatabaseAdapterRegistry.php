<?php

namespace SilverStripe\Dev\Install;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Flushable;

/**
 * This class keeps track of the available database adapters
 * and provides a meaning of registering community built
 * adapters in to the installer process.
 *
 * @author Tom Rix
 */
class DatabaseAdapterRegistry implements Flushable
{

    /**
     * Default database connector registration fields
     *
     * @var array
     */
    private static $default_fields = [
        'server' => [
            'title' => 'Database server',
            'envVar' => 'SS_DATABASE_SERVER',
            'default' => 'localhost'
        ],
        'username' => [
            'title' => 'Database username',
            'envVar' => 'SS_DATABASE_USERNAME',
            'default' => 'root'
        ],
        'password' => [
            'title' => 'Database password',
            'envVar' => 'SS_DATABASE_PASSWORD',
            'default' => 'password'
        ],
        'database' => [
            'title' => 'Database name',
            'default' => 'SS_mysite',
            'attributes' => [
                "onchange" => "this.value = this.value.replace(/[\/\\:*?&quot;<>|. \t]+/g,'');"
            ]
        ],
    ];

    /**
     * Internal array of registered database adapters
     *
     * @var array
     */
    private static $adapters = [];

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
        $missing = array_diff(['title', 'class', 'helperClass', 'supported'], array_keys($config ?? []));
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
            $config['fields'] = DatabaseAdapterRegistry::$default_fields;
        }

        DatabaseAdapterRegistry::$adapters[$config['class']] = $config;
    }

    /**
     * Unregisters a database connector by classname
     *
     * @param string $class
     */
    public static function unregister($class)
    {
        unset(DatabaseAdapterRegistry::$adapters[$class]);
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
        $databaseConfig = $config;

        foreach (static::getConfigureDatabasePaths() as $configureDatabasePath) {
            include_once $configureDatabasePath;
        }
        // Update modified variable
        $config = $databaseConfig;
    }

    /**
     * Including _configure_database.php is a legacy method of configuring a database
     * It's still used by https://github.com/silverstripe/silverstripe-sqlite3
     */
    protected static function getConfigureDatabasePaths(): array
    {
        // autoconfigure() will get called before flush() on ?flush, so manually flush just to ensure no weirdness
        if (isset($_GET['flush'])) {
            static::flush();
        }
        try {
            $cache = static::getCache();
        } catch (\LogicException $e) {
            // This try/catch statement is here rather than in getCache() for semver
            // A LogicException may be thrown from `Symfony\Component\Finder\Finder::getIterator()`
            // if the config manifest is empty.  There are some edge cases where this can happen, for instance
            // running `sspak save` on a fresh install without ?flush
            $cache = null;
        }
        $key = __FUNCTION__;
        if ($cache && $cache->has($key)) {
            return (array) $cache->get($key);
        } else {
            try {
                $paths = glob(BASE_PATH . '/vendor/*/*/_configure_database.php');
            } catch (Exception $e) {
                $paths = [];
            }
            if ($cache) {
                $cache->set($key, $paths);
            }
            return $paths;
        }
    }

    /**
     * @return CacheInterface
     */
    public static function getCache(): CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.DatabaseAdapterRegistry');
    }

    public static function flush()
    {
        static::getCache()->clear();
    }

    /**
     * Return all registered adapters
     *
     * @return array
     */
    public static function get_adapters()
    {
        return DatabaseAdapterRegistry::$adapters;
    }

    /**
     * Returns registry data for a class
     *
     * @param string $class
     * @return array List of adapter properties
     */
    public static function get_adapter($class)
    {
        if (isset(DatabaseAdapterRegistry::$adapters[$class])) {
            return DatabaseAdapterRegistry::$adapters[$class];
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
        return DatabaseAdapterRegistry::$default_fields;
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
        return (class_exists($class ?? '')) ? new $class() : null;
    }
}
