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
    protected $manifests = array();

    /**
     * @return self
     */
    public static function instance()
    {
        return self::$instance ? self::$instance : self::$instance = new self();
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
        return count($this->manifests);
    }
}
