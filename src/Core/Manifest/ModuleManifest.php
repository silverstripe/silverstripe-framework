<?php

namespace SilverStripe\Core\Manifest;

use LogicException;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * A utility class which builds a manifest of configuration items
 */
class ModuleManifest
{
    use Configurable;

    const PROJECT_KEY = '$project';

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
    protected $cacheKey;

    /**
     * Factory to use to build cache
     *
     * @var CacheFactory
     */
    protected $cacheFactory;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * List of all modules.
     *
     * @var Module[]
     */
    protected $modules = [];

    /**
     * List of modules sorted by priority
     *
     * @config
     * @var array
     */
    private static $module_priority = [];

    /**
     * Project name
     *
     * @config
     * @var string
     */
    private static $project = null;

    /**
     * Constructs and initialises a new configuration object, either loading
     * from the cache or re-scanning for classes.
     *
     * @param string $base The project base path.
     * @param CacheFactory $cacheFactory Cache factory to use
     */
    public function __construct($base, CacheFactory $cacheFactory = null)
    {
        $this->base = $base;
        $this->cacheKey = sha1($base ?? '') . '_modules';
        $this->cacheFactory = $cacheFactory;
    }

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
     * @param bool $includeTests
     * @param bool $forceRegen Force the manifest to be regenerated.
     */
    public function init($includeTests = false, $forceRegen = false)
    {
        // build cache from factory
        if ($this->cacheFactory) {
            $this->cache = $this->cacheFactory->create(
                CacheInterface::class . '.modulemanifest',
                ['namespace' => 'modulemanifest' . ($includeTests ? '_tests' : '')]
            );
        }

        // Unless we're forcing regen, try loading from cache
        if (!$forceRegen && $this->cache) {
            $this->modules = $this->cache->get($this->cacheKey) ?: [];
        }
        if (empty($this->modules)) {
            $this->regenerate($includeTests);
        }
    }

    /**
     * Includes all of the php _config.php files found by this manifest.
     */
    public function activateConfig()
    {
        $modules = $this->getModules();
        // Work in reverse priority, so the higher priority modules get later execution
        foreach (array_reverse($modules ?? []) as $module) {
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
     */
    public function regenerate($includeTests = false)
    {
        $this->modules = [];

        $finder = new ManifestFileFinder();
        $finder->setOptions([
            'min_depth' => 0,
            'ignore_tests' => !$includeTests,
            'dir_callback' => function ($basename, $pathname, $depth) use ($finder) {
                if ($finder->isDirectoryModule($basename, $pathname, $depth)) {
                    $this->addModule($pathname);
                }
            },
        ]);
        $finder->find($this->base);

        // Include root itself if module
        if ($finder->isDirectoryModule(basename($this->base ?? ''), $this->base, 0)) {
            $this->addModule($this->base);
        }

        if ($this->cache) {
            $this->cache->set($this->cacheKey, $this->modules);
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
        if (!strstr($name ?? '', '/')) {
            foreach ($this->modules as $module) {
                if (strcasecmp($module->getShortName() ?? '', $name ?? '') === 0) {
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

    /**
     * Sort modules sorted by priority
     */
    public function sort()
    {
        $order = static::config()->uninherited('module_priority');
        $project = static::config()->get('project');

        /** @var PrioritySorter $sorter */
        $sorter = Injector::inst()->createWithArgs(
            PrioritySorter::class . '.modulesorter',
            [
                $this->modules,
                $order ?: [],
            ]
        );

        if ($project) {
            $sorter->setVariable(ModuleManifest::PROJECT_KEY, $project);
        }

        $this->modules = $sorter->getSortedList();
    }

    /**
     * Get module that contains the given path
     *
     * @param string $path Full filesystem path to the given file
     * @return Module The module, or null if not a path in any module
     */
    public function getModuleByPath($path)
    {
        // Ensure path exists
        $path = realpath($path ?? '');
        if (!$path) {
            return null;
        }

        $rootModule = null;

        // Find based on loaded modules
        $modules = ModuleLoader::inst()->getManifest()->getModules();

        foreach ($modules as $module) {
            // Check if path is in module
            $modulePath = realpath($module->getPath() ?? '');
            // if there is a real path
            if ($modulePath) {
                // we remove separator to ensure that we are comparing fairly
                $modulePath = rtrim($modulePath ?? '', DIRECTORY_SEPARATOR);
                $path = rtrim($path ?? '', DIRECTORY_SEPARATOR);
                // if the paths are not the same
                if ($modulePath !== $path) {
                    //add separator to avoid mixing up, for example:
                    //silverstripe/framework and silverstripe/framework-extension
                    $modulePath .= DIRECTORY_SEPARATOR;
                    $path .= DIRECTORY_SEPARATOR;
                    // if the module path is not the same as the start of the module path being tested
                    if (stripos($path ?? '', $modulePath ?? '') !== 0) {
                        // then we need to test the next module
                        continue;
                    }
                }
            }
            // If this is the root module, keep looking in case there is a more specific module later
            if (empty($module->getRelativePath())) {
                $rootModule = $module;
            } else {
                return $module;
            }
        }

        // Fall back to top level module
        return $rootModule;
    }
}
