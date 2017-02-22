<?php

namespace SilverStripe\Core\Manifest;

use LogicException;
use SilverStripe\Core\Cache;
use Zend_Cache_Core;

/**
 * A utility class which builds a manifest of configuration items
 */
class ModuleManifest
{
    /**
     * The base path used when building the manifest
     *
     * @var string
     */
    protected $base;

    /**
     * A string to prepend to all cache keys to ensure all keys are unique to just this $base
     *
     * @var string
     */
    protected $key;

    /**
     * Whether `test` directories should be searched when searching for configuration
     *
     * @var bool
     */
    protected $includeTests;

    /**
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     * List of all modules.
     *
     * @var Module[]
     */
    protected $modules = array();

    /**
     * Adds a path as a module
     *
     * @param string $path
     */
    public function addModule($path)
    {
        $module = new Module($path, $this->base);
        $name = $module->getName();

        // Save if not already added
        if (empty($this->modules[$name])) {
            $this->modules[$name] = $module;
            return;
        }

        // Validate duplicate module
        $path = $module->getPath();
        $otherPath = $this->modules[$name]->getPath();
        if ($otherPath !== $path) {
            throw new LogicException(
                "Module {$name} is in two places - {$path} and {$otherPath}"
            );
        }
    }

    /**
     * Returns true if the passed module exists
     *
     * @param string $name Either full composer name or short name
     * @return bool
     */
    public function moduleExists($name)
    {
        $module = $this->getModule($name);
        return !empty($module);
    }

    /**
     * Constructs and initialises a new configuration object, either loading
     * from the cache or re-scanning for classes.
     *
     * @param string $base The project base path.
     * @param bool $includeTests
     * @param bool $forceRegen Force the manifest to be regenerated.
     */
    public function __construct($base, $includeTests = false, $forceRegen = false)
    {
        $this->base = $base;
        $this->key = sha1($base).'_modules';
        $this->includeTests = $includeTests;

        // Get the Zend Cache to load/store cache into
        $this->cache = $this->getCache();

        // Unless we're forcing regen, try loading from cache
        if (!$forceRegen) {
            $this->modules = $this->cache->load($this->key) ?: [];
        }
        if (empty($this->modules)) {
            $this->regenerate($includeTests);
        }
    }

    /**
     * Provides a hook for mock unit tests despite no DI
     * @return Zend_Cache_Core
     */
    protected function getCache()
    {
        return Cache::factory('SS_Configuration', 'Core', array(
            'automatic_serialization' => true,
            'lifetime' => null
        ));
    }

    /**
     * Includes all of the php _config.php files found by this manifest.
     */
    public function activateConfig()
    {
        foreach ($this->getModules() as $module) {
            $module->activate();
        }
    }

    /**
     * Completely regenerates the manifest file. Scans through finding all php _config.php and yaml _config/*.ya?ml
     * files,parses the yaml files into fragments, sorts them and figures out what values need to be checked to pick
     * the correct variant.
     *
     * Does _not_ build the actual variant
     *
     * @param bool $includeTests
     * @param bool $cache Cache the result.
     */
    public function regenerate($includeTests = false, $cache = true)
    {
        $this->modules = [];

        $finder = new ManifestFileFinder();
        $finder->setOptions(array(
            'min_depth' => 0,
            'name_regex'    => '/(^|[\/\\\\])_config.php$/',
            'ignore_tests'  => !$includeTests,
            'file_callback' => array($this, 'addSourceConfigFile'),
            // Cannot be max_depth: 1 due to "/framework/admin/_config.php"
            'max_depth'     => 2
        ));
        $finder->find($this->base);

        $finder = new ManifestFileFinder();
        $finder->setOptions(array(
            'name_regex'    => '/\.ya?ml$/',
            'ignore_tests'  => !$includeTests,
            'file_callback' => array($this, 'addYAMLConfigFile'),
            'max_depth'     => 2
        ));
        $finder->find($this->base);

        if ($cache) {
            $this->cache->save($this->modules, $this->key);
        }
    }

    /**
     * Record finding of _config.php file
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     */
    public function addSourceConfigFile($basename, $pathname, $depth)
    {
        $this->addModule(dirname($pathname));
    }

    /**
     * Handle lookup of _config/*.yml file
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     */
    public function addYAMLConfigFile($basename, $pathname, $depth)
    {
        if (preg_match('{/([^/]+)/_config/}', $pathname, $match)) {
            $this->addModule(dirname(dirname($pathname)));
        }
    }

    /**
     * Get module by name
     *
     * @param string $name
     * @return Module
     */
    public function getModule($name)
    {
        // Optimised find
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }

        // Fall back to lookup by shortname
        if (!strstr($name, '/')) {
            foreach ($this->modules as $module) {
                if (strcasecmp($module->getShortName(), $name) === 0) {
                    return $module;
                }
            }
        }

        return null;
    }

    /**
     * Get modules found
     *
     * @return Module[]
     */
    public function getModules()
    {
        return $this->modules;
    }
}
