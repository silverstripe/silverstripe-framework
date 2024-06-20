<?php

namespace SilverStripe\View;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Manifest\ManifestFileFinder;
use SilverStripe\Core\Manifest\ModuleLoader;

/**
 * A class which builds a manifest of all themes (which is really just a directory called "templates")
 */
class ThemeManifest implements ThemeList
{

    const TEMPLATES_DIR = 'templates';

    /**
     * Base path
     *
     * @var string
     */
    protected $base;

    /**
     * Path to application code
     *
     * @var string
     */
    protected $project;

    /**
     * Cache
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Cache key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * List of theme root directories
     *
     * @var string[]
     */
    protected $themes = null;

    /**
     * @var CacheFactory
     */
    protected $cacheFactory= null;

    /**
     * Constructs a new template manifest. The manifest is not actually built
     * or loaded from cache until needed.
     *
     * @param string $base The base path.
     * @param string $project Path to application code
     * @param CacheFactory $cacheFactory Cache factory to generate backend cache with
     */
    public function __construct($base, $project = null, CacheFactory $cacheFactory = null)
    {
        $this->base = $base;
        $this->project = $project;
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * @param bool $includeTests Include tests in the manifest
     * @param bool $forceRegen Force the manifest to be regenerated.
     */
    public function init($includeTests = false, $forceRegen = false)
    {
        // build cache from factory
        if ($this->cacheFactory) {
            $this->cache = $this->cacheFactory->create(
                CacheInterface::class . '.thememanifest',
                [ 'namespace' => 'thememanifest' . ($includeTests ? '_tests' : '') ]
            );
        }
        $this->cacheKey = $this->getCacheKey($includeTests);
        if (!$forceRegen && $this->cache && ($data = $this->cache->get($this->cacheKey))) {
            $this->themes = $data;
        } else {
            $this->regenerate($includeTests);
        }
    }

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Generate a unique cache key to avoid manifest cache collisions.
     * We compartmentalise based on the base path, the given project, and whether
     * or not we intend to include tests.
     *
     * @param bool $includeTests
     * @return string
     */
    public function getCacheKey($includeTests = false)
    {
        return sha1(sprintf(
            "manifest-%s-%s-%u",
            $this->base,
            $this->project,
            $includeTests
        ));
    }

    /**
     * @return string[]
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * Regenerates the manifest by scanning the base path.
     *
     * @param bool $includeTests
     */
    public function regenerate($includeTests = false)
    {
        $finder = new ManifestFileFinder();
        $finder->setOptions([
            'include_themes' => false,
            'ignore_dirs' => ['node_modules', THEMES_DIR],
            'ignore_tests'  => !$includeTests,
            'dir_callback'  => [$this, 'handleDirectory']
        ]);

        $this->themes = [];

        $modules = ModuleLoader::inst()->getManifest()->getModules();
        foreach ($modules as $module) {
            $finder->find($module->getPath());
        }

        if ($this->cache) {
            $this->cache->set($this->cacheKey, $this->themes);
        }
    }

    /**
     * Add a directory to the manifest
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     */
    public function handleDirectory($basename, $pathname, $depth)
    {
        if ($basename !== ThemeManifest::TEMPLATES_DIR) {
            return;
        }
        $dir = trim(substr(dirname($pathname ?? ''), strlen($this->base ?? '')), '/\\');
        $this->themes[] = "/" . $dir;
    }

    /**
     * Sets the project
     *
     * @param string $project
     * @return $this
     */
    public function setProject($project)
    {
        $this->project = $project;

        return $this;
    }
}
