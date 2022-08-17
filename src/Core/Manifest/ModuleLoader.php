<?php

namespace SilverStripe\Core\Manifest;

/**
 * Module manifest holder
 */
class ModuleLoader
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var ModuleManifest[] Module manifests
     */
    protected $manifests = [];

    /**
     * @return self
     */
    public static function inst(): SilverStripe\Core\Manifest\ModuleLoader
    {
        return self::$instance ? self::$instance : self::$instance = new static();
    }

    /**
     * Get module by name from the current manifest.
     * Alias for ::inst()->getManifest()->getModule()
     *
     * @param string $module
     * @return Module
     */
    public static function getModule(string $module): SilverStripe\Core\Manifest\Module
    {
        return static::inst()->getManifest()->getModule($module);
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return ModuleManifest
     */
    public function getManifest(): SilverStripe\Core\Manifest\ModuleManifest
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
    public function pushManifest(ModuleManifest $manifest): void
    {
        $this->manifests[] = $manifest;
    }

    /**
     * @return ModuleManifest
     */
    public function popManifest(): SilverStripe\Core\Manifest\ModuleManifest
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
     * @param string[] $ignoredCIConfigs
     */
    public function init(bool $includeTests = false, bool $forceRegen = false, array $ignoredCIConfigs = []): void
    {
        foreach ($this->manifests as $manifest) {
            $manifest->init($includeTests, $forceRegen, $ignoredCIConfigs);
        }
    }
}
