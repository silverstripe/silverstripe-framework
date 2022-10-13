<?php

namespace SilverStripe\Core\Manifest;

use Composer\Semver\Semver;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Serializable;
use SilverStripe\Core\Path;
use SilverStripe\Dev\Deprecation;

/**
 * Abstraction of a PHP Package. Can be used to retrieve information about Silverstripe CMS modules, and other packages
 * managed via composer, by reading their `composer.json` file.
 */
class Module implements Serializable
{
    /**
     * @deprecated 4.1.0:5.0.0 Use Path::normalise() instead
     */
    const TRIM_CHARS = ' /\\';

    /**
     * Return value of getCIConfig() when module uses PHPUNit 9
     */
    const CI_PHPUNIT_NINE = 'CI_PHPUNIT_NINE';

    /**
     * Return value of getCIConfig() when module uses PHPUNit 5
     */
    const CI_PHPUNIT_FIVE = 'CI_PHPUNIT_FIVE';

    /**
     * Return value of getCIConfig() when module does not use any CI
     */
    const CI_UNKNOWN = 'CI_UNKNOWN';



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
     * The __serialize() magic method will be automatically used instead of this
     *
     * @return string
     * @deprecated 4.12.0 Use __serialize() instead
     */
    public function serialize()
    {
        Deprecation::notice('4.12.0', 'Use __serialize() instead');
        return json_encode([$this->path, $this->basePath, $this->composerData]);
    }

    /**
     * The __unserialize() magic method will be automatically used instead of this almost all the time
     * This method will be automatically used if existing serialized data was not saved as an associative array
     * and the PHP version used in less than PHP 9.0
     *
     * @param string $serialized
     * @deprecated 4.12.0 Use __unserialize() instead
     */
    public function unserialize($serialized)
    {
        Deprecation::notice('4.12.0', 'Use __unserialize() instead');
        list($this->path, $this->basePath, $this->composerData) = json_decode($serialized ?? '', true);
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

    /**
     * @deprecated 4.0.1 Use getResource($path)->getRelativePath() instead
     * @param string $path
     * @return string
     */
    public function getRelativeResourcePath($path)
    {
        Deprecation::notice('4.0.1', 'Use getResource($path)->getRelativePath() instead');
        return $this
            ->getResource($path)
            ->getRelativePath();
    }

    /**
     * @deprecated 4.0.1 Use getResource($path)->getPath() instead
     * @param string $path
     * @return string
     */
    public function getResourcePath($path)
    {
        Deprecation::notice('4.0.1', 'Use getResource($path)->getPath() instead');
        return $this
            ->getResource($path)
            ->getPath();
    }

    /**
     * @deprecated 4.0.1 Use getResource($path)->getURL() instead
     * @param string $path
     * @return string
     */
    public function getResourceURL($path)
    {
        Deprecation::notice('4.0.1', 'Use getResource($path)->getURL() instead');
        return $this
            ->getResource($path)
            ->getURL();
    }

    /**
     * @deprecated 4.0.1 Use getResource($path)->exists() instead
     * @param string $path
     * @return string
     */
    public function hasResource($path)
    {
        Deprecation::notice('4.0.1', 'Use getResource($path)->exists() instead');
        return $this
            ->getResource($path)
            ->exists();
    }

    /**
     * Determine what configurations the module is using to run various aspects of its CI. THe only aspect
     * that is observed is `PHP`
     * @return array List of configuration aspects e.g.: `['PHP' => 'CI_PHPUNIT_NINE']`
     * @internal
     * @deprecated 4.12.0 Will be removed without equivalent functionality
     */
    public function getCIConfig(): array
    {
        Deprecation::notice('4.12.0', 'Will be removed without equivalent functionality');
        return [
            'PHP' => $this->getPhpCiConfig()
        ];
    }

    /**
     * Determine what CI Configuration the module uses to test its PHP code.
     */
    private function getPhpCiConfig(): string
    {
        // We don't have any composer data at all
        if (empty($this->composerData)) {
            return self::CI_UNKNOWN;
        }

        // We don't have any dev dependencies
        if (empty($this->composerData['require-dev']) || !is_array($this->composerData['require-dev'])) {
            return self::CI_UNKNOWN;
        }

        // We are assuming a typical setup where the CI lib is defined in require-dev rather than require
        $requireDev = $this->composerData['require-dev'];

        // Try to pick which CI we are using based on phpunit constraint
        $phpUnitConstraint = $this->requireDevConstraint(['sminnee/phpunit', 'phpunit/phpunit']);
        if ($phpUnitConstraint) {
            if ($this->constraintSatisfies(
                $phpUnitConstraint,
                ['5.7.0', '5.0.0', '5.x-dev', '5.7.x-dev'],
                5
            )) {
                return self::CI_PHPUNIT_FIVE;
            }
            if ($this->constraintSatisfies(
                $phpUnitConstraint,
                ['9.0.0', '9.5.0', '9.x-dev', '9.5.x-dev'],
                9
            )) {
                return self::CI_PHPUNIT_NINE;
            }
        }

        // Try to pick which CI we are using based on recipe-testing constraint
        $recipeTestingConstraint = $this->requireDevConstraint(['silverstripe/recipe-testing']);
        if ($recipeTestingConstraint) {
            if ($this->constraintSatisfies(
                $recipeTestingConstraint,
                ['1.0.0', '1.1.0', '1.2.0', '1.1.x-dev', '1.2.x-dev', '1.x-dev'],
                1
            )) {
                return self::CI_PHPUNIT_FIVE;
            }
            if ($this->constraintSatisfies(
                $recipeTestingConstraint,
                ['2.0.0', '2.0.x-dev', '2.x-dev'],
                2
            )) {
                return self::CI_PHPUNIT_NINE;
            }
        }

        return self::CI_UNKNOWN;
    }

    /**
     * Retrieve the constraint for the first module that is found in the require-dev section
     * @param string[] $modules
     * @return false|string
     */
    private function requireDevConstraint(array $modules)
    {
        if (empty($this->composerData['require-dev']) || !is_array($this->composerData['require-dev'])) {
            return false;
        }

        $requireDev = $this->composerData['require-dev'];
        foreach ($modules as $module) {
            if (isset($requireDev[$module])) {
                return $requireDev[$module];
            }
        }

        return false;
    }

    /**
     * Determines if the provided constraint allows at least one of the version provided
     */
    private function constraintSatisfies(
        string $constraint,
        array $possibleVersions,
        int $majorVersionFallback
    ): bool {
        // Let's see of any of our possible versions is allowed by the constraint
        if (!empty(Semver::satisfiedBy($possibleVersions, $constraint))) {
            return true;
        }

        // Let's see if we are using an exact version constraint. e.g. ~1.2.3 or 1.2.3 or ~1.2 or 1.2.*
        if (preg_match("/^~?$majorVersionFallback(\.(\d+)|\*){0,2}/", $constraint ?? '')) {
            return true;
        }

        return false;
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
