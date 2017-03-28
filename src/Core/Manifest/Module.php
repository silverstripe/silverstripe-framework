<?php

namespace SilverStripe\Core\Manifest;

use Exception;
use Serializable;

class Module implements Serializable
{
    /**
     * Directory
     *
     * @var string
     */
    protected $path = null;

    /**
     * Base folder of application
     *
     * @var string
     */
    protected $basePath = null;

    /**
     * Cache of composer data
     *
     * @var array
     */
    protected $composerData = null;

    public function __construct($path, $base)
    {
        $this->path = rtrim($path, '/\\');
        $this->basePath = rtrim($base, '/\\');
        $this->loadComposer();
    }

    /**
     * Gets name of this module. Used as unique key and identifier for this module.
     *
     * If installed by composer, this will be the full composer name (vendor/name).
     * If not insalled by composer this will default to the basedir()
     *
     * @return string
     */
    public function getName()
    {
        return $this->getComposerName() ?: $this->getShortName();
    }

    /**
     * Get full composer name. Will be null if no composer.json is available
     *
     * @return string|null
     */
    public function getComposerName()
    {
        if (isset($this->composerData['name'])) {
            return $this->composerData['name'];
        }
        return null;
    }

    /**
     * Gets "short" name of this module. This is the base directory this module
     * is installed in.
     *
     * If installed in root, this will be generated from the composer name instead
     *
     * @return string
     */
    public function getShortName()
    {
        // If installed in the root directory we need to infer from composer
        if ($this->path === $this->basePath && $this->composerData) {
            // Sometimes we customise installer name
            if (isset($this->composerData['extra']['installer-name'])) {
                return $this->composerData['extra']['installer-name'];
            }

            // Strip from full composer name
            $composerName = $this->getComposerName();
            if ($composerName) {
                list(, $name) = explode('/', $composerName);
                return $name;
            }
        }

        // Base name of directory
        return basename($this->path);
    }

    /**
     * Get base path for this module
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get path relative to base dir.
     * If module path is base this will be empty string
     *
     * @return string
     */
    public function getRelativePath()
    {
        return ltrim(substr($this->path, strlen($this->basePath)), '/\\');
    }

    public function serialize()
    {
        return json_encode([$this->path, $this->basePath, $this->composerData]);
    }

    public function unserialize($serialized)
    {
        list($this->path, $this->basePath, $this->composerData) = json_decode($serialized, true);
    }

    /**
     * Activate _config.php for this module, if one exists
     */
    public function activate()
    {
        $config = "{$this->path}/_config.php";
        if (file_exists($config)) {
            require_once $config;
        }
    }

    /**
     * @throws Exception
     */
    protected function loadComposer()
    {
        // Load composer data
        $path = "{$this->path}/composer.json";
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $result = json_decode($content, true);
            if (json_last_error()) {
                throw new Exception(json_last_error_msg());
            }
            $this->composerData = $result;
        }
    }

    /**
     * Gets path to physical file resource relative to base directory.
     * Directories included
     *
     * This method makes no distinction between public / local resources,
     * which may change in the near future.
     *
     * @internal Experimental API and may change
     * @param string $path File or directory path relative to module directory
     * @return string Path relative to base directory
     */
    public function getResourcePath($path)
    {
        $base = trim($this->getRelativePath(), '/\\');
        $path = trim($path, '/\\');
        return trim("{$base}/{$path}", '/\\');
    }

    /**
     * Check if this module has a given resource
     *
     * @internal Experimental API and may change
     * @param string $path
     * @return bool
     */
    public function hasResource($path)
    {
        $resource = $this->getResourcePath($path);
        return file_exists($this->basePath . '/' . $resource);
    }
}
