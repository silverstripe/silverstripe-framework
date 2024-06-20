<?php

namespace SilverStripe\Core\Manifest;

/**
 * A class that handles loading classes and interfaces from a class manifest
 * instance.
 */
class ClassLoader
{

    /**
     * @internal
     * @var ClassLoader
     */
    private static $instance;

    /**
     * Map of 'instance' (ClassManifest) and other options.
     *
     * @var array
     */
    protected $manifests = [];

    /**
     * @return ClassLoader
     */
    public static function inst()
    {
        return ClassLoader::$instance ? ClassLoader::$instance : ClassLoader::$instance = new static();
    }

    /**
     * Returns the currently active class manifest instance that is used for
     * loading classes.
     *
     * @return ClassManifest
     */
    public function getManifest()
    {
        return $this->manifests[count($this->manifests) - 1]['instance'];
    }

    /**
     * Returns true if this class loader has a manifest.
     */
    public function hasManifest()
    {
        return (bool)$this->manifests;
    }

    /**
     * Pushes a class manifest instance onto the top of the stack.
     *
     * @param ClassManifest $manifest
     * @param bool $exclusive Marks the manifest as exclusive. If set to FALSE, will
     * look for classes in earlier manifests as well.
     */
    public function pushManifest(ClassManifest $manifest, $exclusive = true)
    {
        $this->manifests[] = ['exclusive' => $exclusive, 'instance' => $manifest];
    }

    /**
     * @return ClassManifest
     */
    public function popManifest()
    {
        $manifest = array_pop($this->manifests);
        return $manifest['instance'];
    }

    public function registerAutoloader()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Loads a class or interface if it is present in the currently active
     * manifest.
     *
     * @param string $class
     * @return String
     */
    public function loadClass($class)
    {
        if ($path = $this->getItemPath($class)) {
            require_once $path;
        }
        return $path;
    }

    /**
     * Returns the path for a class or interface in the currently active manifest,
     * or any previous ones if later manifests aren't set to "exclusive".
     *
     * @param string $class
     * @return string|false
     */
    public function getItemPath($class)
    {
        foreach (array_reverse($this->manifests ?? []) as $manifest) {
            /** @var ClassManifest $manifestInst */
            $manifestInst = $manifest['instance'];
            if ($path = $manifestInst->getItemPath($class)) {
                return $path;
            }
            if ($manifest['exclusive']) {
                break;
            }
        }
        return false;
    }

    /**
     * Initialise the class loader
     *
     * @param bool $includeTests
     * @param bool $forceRegen
     */
    public function init($includeTests = false, $forceRegen = false)
    {
        foreach ($this->manifests as $manifest) {
            /** @var ClassManifest $instance */
            $instance = $manifest['instance'];
            $instance->init($includeTests, $forceRegen);
        }

        $this->registerAutoloader();
    }
}
