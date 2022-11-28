<?php

namespace SilverStripe\Core\Manifest;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Path;

/**
 * Abstraction of a PHP Package. Can be used to retrieve information about Silverstripe CMS modules, and other packages
 * managed via composer, by reading their `composer.json` file.
 */
class Module
{
    /**
     * Full directory path to this module with no trailing slash
     *
     * @var string
     */
    protected $path = null;

    /**
     * Base folder of application with no trailing slash
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

    /**
     * Loaded resources for this module
     *
     * @var ModuleResource[]
     */
    protected $resources = [];

    /**
     * Construct a module
     *
     * @param string $path Absolute filesystem path to this module
     * @param string $basePath base path for the application this module is installed in
     */
    public function __construct($path, $basePath)
    {
        $this->path = Path::normalise($path);
        $this->basePath = Path::normalise($basePath);
        $this->loadComposer();
    }

    /**
     * Gets name of this module. Used as unique key and identifier for this module.
     *
     * If installed by composer, this will be the full composer name (vendor/name).
     * If not installed by composer this will default to the `basedir()`
     *
     * @return string
     */
    public function getName()
    {
        return $this->getComposerName() ?: $this->getShortName();
    }

    /**
     * Get full composer name. Will be `null` if no composer.json is available
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
     * Get list of folders that need to be made available
     *
     * @return array
     */
    public function getExposedFolders()
    {
        if (isset($this->composerData['extra']['expose'])) {
            return $this->composerData['extra']['expose'];
        }
        return [];
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
                list(, $name) = explode('/', $composerName ?? '');
                return $name;
            }
        }

        // Base name of directory
        return basename($this->path ?? '');
    }

    /**
     * Name of the resource directory where vendor resources should be exposed as defined by the `extra.resources-dir`
     * key in the composer file. A blank string will be returned if the key is undefined.
     *
     * Only applicable when reading the composer file for the main project.
     * @return string
     */
    public function getResourcesDir()
    {
        return isset($this->composerData['extra']['resources-dir'])
            ? $this->composerData['extra']['resources-dir']
            : '';
    }

    /**
     * Get base path for this module
     *
     * @return string Path with no trailing slash E.g. /var/www/module
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get path relative to base dir.
     * If module path is base this will be empty string
     *
     * @return string Path with trimmed slashes. E.g. vendor/silverstripe/module.
     */
    public function getRelativePath()
    {
        if ($this->path === $this->basePath) {
            return '';
        }
        return substr($this->path ?? '', strlen($this->basePath ?? '') + 1);
    }

    public function __serialize(): array
    {
        return [
            'path' => $this->path,
            'basePath' => $this->basePath,
            'composerData' => $this->composerData
        ];
    }

    public function __unserialize(array $data): void
    {
            $this->path = $data['path'];
            $this->basePath = $data['basePath'];
            $this->composerData = $data['composerData'];
            $this->resources = [];
    }
    
    /**
     * Activate _config.php for this module, if one exists
     */
    public function activate()
    {
        $config = "{$this->path}/_config.php";
        if (file_exists($config ?? '')) {
            requireFile($config);
        }
    }

    /**
     * @throws Exception
     */
    protected function loadComposer()
    {
        // Load composer data
        $path = "{$this->path}/composer.json";
        if (file_exists($path ?? '')) {
            $content = file_get_contents($path ?? '');
            $result = json_decode($content ?? '', true);
            if (json_last_error()) {
                $errorMessage = json_last_error_msg();
                throw new Exception("$path: $errorMessage");
            }
            $this->composerData = $result;
        }
    }

    /**
     * Get resource for this module
     *
     * @param string $path
     * @return ModuleResource
     */
    public function getResource($path)
    {
        $path = Path::normalise($path, true);
        if (empty($path)) {
            throw new InvalidArgumentException('$path is required');
        }
        if (isset($this->resources[$path])) {
            return $this->resources[$path];
        }
        return $this->resources[$path] = new ModuleResource($this, $path);
    }
}

/**
 * Scope isolated require - prevents access to $this, and prevents module _config.php
 * files potentially leaking variables. Required argument $file is commented out
 * to avoid leaking that into _config.php
 *
 * @param string $file
 */
function requireFile()
{
    require_once func_get_arg(0);
}
