<?php

namespace SilverStripe\Core\Manifest;

/**
 * Module manifest holder
 */
class ModuleLoader
{
    /**
     * @var ModuleLoader
     */
    private static $instance;

    /**
     * @var ModuleManifest[] Module manifests
     */
    protected $manifests = [];

    /**
     * @return ModuleLoader
     */
    public static function inst()
    {
        return ModuleLoader::$instance ? ModuleLoader::$instance : ModuleLoader::$instance = new static();
    }

    /**
     * Get module by name from the current manifest.
     * Alias for ::inst()->getManifest()->getModule()
     *
     * @param string $module
     * @return Module
     */
    public static function getModule($module)
    {
        return static::inst()->getManifest()->getModule($module);
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return ModuleManifest
     */
    public function getManifest()
    {
        return $this->manifests[count($this->manifests) - 1];
    }

    /**
     * Returns true if this class loader has a manifest.
     *
     * @return bool
     */
    public function hasManifest()
    {
        return (bool)$this->manifests;
    }

    /**
     * Pushes a module manifest instance onto the top of the stack.
     *
     * @param ModuleManifest $manifest
     */
    public function pushManifest(ModuleManifest $manifest)
    {
        $this->manifests[] = $manifest;
    }

    /**
     * @return ModuleManifest
     */
    public function popManifest()
    {
        return array_pop($this->manifests);
    }

    /**
     * Check number of manifests
     *
     * @return int
     */
    public function countManifests()
    {
        return count($this->manifests ?? []);
    }

    /**
     * Initialise the module loader
     *
     * @param bool $includeTests
     * @param bool $forceRegen
     */
    public function init($includeTests = false, $forceRegen = false)
    {
        foreach ($this->manifests as $manifest) {
            $manifest->init($includeTests, $forceRegen);
        }
    }
}
