<?php

namespace SilverStripe\View;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Manifest\ManifestFileFinder;

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
     * Include tests
     *
     * @var bool
     */
    protected $tests;

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
     * Constructs a new template manifest. The manifest is not actually built
     * or loaded from cache until needed.
     *
     * @param string $base The base path.
     * @param string $project Path to application code
     *
     * @param bool $includeTests Include tests in the manifest.
     * @param bool $forceRegen Force the manifest to be regenerated.
     * @param CacheFactory $cacheFactory Cache factory to generate backend cache with
     */
    public function __construct(
        $base,
        $project,
        $includeTests = false,
        $forceRegen = false,
        CacheFactory $cacheFactory = null
    ) {
        $this->base  = $base;
        $this->tests = $includeTests;
        $this->project = $project;

        // build cache from factory
        if ($cacheFactory) {
            $this->cache = $cacheFactory->create(
                CacheInterface::class.'.thememanifest',
                [ 'namespace' => 'thememanifest' . ($includeTests ? '_tests' : '') ]
            );
        }
        $this->cacheKey = $this->getCacheKey();

        if ($forceRegen) {
            $this->regenerate();
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
     * @return string
     */
    public function getCacheKey()
    {
        return sha1(sprintf(
            "manifest-%s-%s-%u",
            $this->base,
            $this->project,
            $this->tests
        ));
    }

    public function getThemes()
    {
        if ($this->themes === null) {
            $this->init();
        }
        return $this->themes;
    }

    /**
     * Regenerates the manifest by scanning the base path.
     */
    public function regenerate()
    {
        $finder = new ManifestFileFinder();
        $finder->setOptions(array(
            'include_themes' => false,
            'ignore_dirs' => array('node_modules', THEMES_DIR),
            'ignore_tests'  => !$this->tests,
            'dir_callback'  => array($this, 'handleDirectory')
        ));

        $this->themes = [];
        $finder->find($this->base);

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
        if ($basename !== self::TEMPLATES_DIR) {
            return;
        }

        // We only want part of the full path, so split into directories
        $parts = explode('/', $pathname);
        // Take the end (the part relative to base), except the very last directory
        $themeParts = array_slice($parts, -$depth, $depth-1);
        // Then join again
        $path = '/'.implode('/', $themeParts);

        // If this is in the project, add to beginning of list. Else add to end.
        if ($themeParts && $themeParts[0] == $this->project) {
            array_unshift($this->themes, $path);
        } else {
            array_push($this->themes, $path);
        }
    }

    /**
     * Initialise the manifest
     */
    protected function init()
    {
        if ($this->cache && ($data = $this->cache->get($this->cacheKey))) {
            $this->themes = $data;
        } else {
            $this->regenerate();
        }
    }
}
