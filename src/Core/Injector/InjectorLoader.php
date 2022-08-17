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
    protected $manifests = [];

    /**
     * @return self
     */
    public static function inst(): SilverStripe\Core\Injector\InjectorLoader
    {
        return self::$instance ? self::$instance : self::$instance = new static();
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return Injector
     */
    public function getManifest(): SilverStripe\Core\Injector\Injector
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
    public function pushManifest(Injector $manifest): void
    {
        $this->manifests[] = $manifest;
    }

    /**
     * @return Injector
     */
    public function popManifest(): SilverStripe\Core\Injector\Injector
    {
        return array_pop($this->manifests);
    }

    /**
     * Check number of manifests
     *
     * @return int
     */
    public function countManifests(): int
    {
        return count($this->manifests ?? []);
    }

    /**
     * Nest the config loader
     *
     * @return static
     */
    public function nest(): SilverStripe\Core\Injector\InjectorLoader
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
    public function activate(): SilverStripe\Core\Injector\InjectorLoader
    {
        static::$instance = $this;
        return $this;
    }
}
