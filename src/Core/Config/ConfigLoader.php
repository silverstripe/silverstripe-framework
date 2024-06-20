<?php

namespace SilverStripe\Core\Config;

use BadMethodCallException;
use SilverStripe\Config\Collections\ConfigCollectionInterface;

/**
 * Registers config sources via ConfigCollectionInterface
 */
class ConfigLoader
{
    /**
     * @internal
     * @var ConfigLoader
     */
    private static $instance;

    /**
     * @var ConfigCollectionInterface[] map of config collections
     */
    protected $manifests = [];

    /**
     * @return ConfigLoader
     */
    public static function inst()
    {
        return ConfigLoader::$instance ? ConfigLoader::$instance : ConfigLoader::$instance = new static();
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return ConfigCollectionInterface
     */
    public function getManifest()
    {
        if ($this !== ConfigLoader::$instance) {
            throw new BadMethodCallException(
                "Non-current config manifest cannot be accessed. Please call ->activate() first"
            );
        }
        if (empty($this->manifests)) {
            throw new BadMethodCallException("No config manifests available");
        }
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
        return count($this->manifests ?? []);
    }

    /**
     * Nest the config loader and activates it
     *
     * @return static
     */
    public function nest()
    {
        // Nest config
        $manifest = clone $this->getManifest();

        // Create new blank loader with new stack (top level nesting)
        $newLoader = new static;
        $newLoader->pushManifest($manifest);

        // Activate new loader
        return $newLoader->activate();
    }

    /**
     * Mark this instance as the current instance
     *
     * @return $this
     */
    public function activate()
    {
        static::$instance = $this;
        return $this;
    }
}
