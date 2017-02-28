<?php

namespace SilverStripe\Core\Config;

use SilverStripe\Config\Collections\ConfigCollectionInterface;

/**
 * Registers config sources via ConfigCollectionInterface
 */
class ConfigLoader
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var ConfigCollectionInterface[] map of config collections
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
     * @return ConfigCollectionInterface
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
     * Pushes a class manifest instance onto the top of the stack.
     *
     * @param ConfigCollectionInterface $manifest
     */
    public function pushManifest(ConfigCollectionInterface $manifest)
    {
        $this->manifests[] = $manifest;
    }

    /**
     * @return ConfigCollectionInterface
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

    /**
     * Nest the current manifest
     *
     * @return ConfigCollectionInterface
     */
    public function nest()
    {
        $manifest = $this->getManifest()->nest();
        $this->pushManifest($manifest);
        return $manifest;
    }
}
