<?php

namespace SilverStripe\Core\Injector;

use BadMethodCallException;

/**
 * Registers chained injectors
 */
class InjectorLoader
{
    /**
     * @internal
     * @var self
     */
    private static $instance;

    /**
     * @var Injector[] map of injector instances
     */
    protected $manifests = array();

    /**
     * @return self
     */
    public static function inst()
    {
        return self::$instance ? self::$instance : self::$instance = new static();
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return Injector
     */
    public function getManifest()
    {
        if ($this !== self::$instance) {
            throw new BadMethodCallException(
                "Non-current injector manifest cannot be accessed. Please call ->activate() first"
            );
        }
        if (empty($this->manifests)) {
            throw new BadMethodCallException("No injector manifests available");
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
     * @param Injector $manifest
     */
    public function pushManifest(Injector $manifest)
    {
        $this->manifests[] = $manifest;
    }

    /**
     * @return Injector
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
     * Nest the config loader
     *
     * @return static
     */
    public function nest()
    {
        // Nest injector (note: Don't call getManifest()->nest() since that self-pushes a new manifest)
        $manifest = clone $this->getManifest();

        // Create new blank loader with new stack (top level nesting)
        $newLoader = new static;
        $newLoader->pushManifest($manifest);

        // Activate new loader
        $newLoader->activate();
        return $newLoader;
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
