<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Core\Kernel;
use SilverStripe\Core\Environment;

/**
 * Checks whether a filesystem resource has been changed since
 * the manifest generation
 *
 * For this discoverer to get activated you should define SS_FLUSH_ON_DEPLOY
 * variable
 *  - if the environment variable SS_FLUSH_ON_DEPLOY undefined or `false`, then does nothing
 *  - if SS_FLUSH_ON_DEPLOY is true, then checks __FILE__ modification time
 *  - otherwise takes {BASE_PATH/SS_FLUSH_ON_DEPLOY} as the resource to check
 *
 * Examples:
 *
 *  - `SS_FLUSH_ON_DEPLOY=""` would check the BASE_PATH folder for modifications (not the files within)
 *  - `SS_FLUSH_ON_DEPLOY=true` would check BASE_PATH/vendor/silverstripe/framework/src/Core/Startup/DeployFlushDiscoverer.php
 *                              file modification
 *  - `SS_FLUSH_ON_DEPLOY=false` disable filesystem checks
 *  - `SS_FLUSH_ON_DEPLOY="public/index.php"` checks BASE_PATH/public/index.php file modification time
 */
class DeployFlushDiscoverer implements FlushDiscoverer
{
    /**
     * Active kernel
     *
     * @var Kernel
     */
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Returns the timestamp of the manifest generation or null
     * if no cache has been found (or couldn't read the cache)
     *
     * @return int|null unix timestamp
     */
    protected function getCacheTimestamp()
    {
        $classLoader = $this->kernel->getClassLoader();
        $classManifest = $classLoader->getManifest();
        $cacheTimestamp = $classManifest->getManifestTimestamp();

        return $cacheTimestamp;
    }

    /**
     * Returns the resource to be checked for deployment
     *
     *  - if the environment variable SS_FLUSH_ON_DEPLOY undefined or false, then returns null
     *  - if SS_FLUSH_ON_DEPLOY is true, then takes __FILE__ as the resource to check
     *  - otherwise takes {BASE_PATH/SS_FLUSH_ON_DEPLOY} as the resource to check
     *
     * @return string|null returns the resource path or null if not set
     */
    protected function getDeployResource()
    {
        $resource = Environment::getEnv('SS_FLUSH_ON_DEPLOY');

        if ($resource === false) {
            return null;
        }

        if ($resource === true) {
            $path = __FILE__;
        } else {
            $path = sprintf("%s/%s", BASE_PATH, $resource);
        }

        return $path;
    }


    /**
     * Returns the resource modification timestamp
     *
     * @param string $resource Path to the filesystem
     *
     * @return int
     */
    protected function getDeployTimestamp($resource)
    {
        if (!file_exists($resource ?? '')) {
            return 0;
        }

        return max(filemtime($resource ?? ''), filectime($resource ?? ''));
    }

    /**
     * Returns true if the deploy timestamp greater than the cache generation timestamp
     *
     * {@inheritdoc}
     */
    public function shouldFlush()
    {
        $resource = $this->getDeployResource();

        if (is_null($resource)) {
            return null;
        }

        $deploy = $this->getDeployTimestamp($resource);
        $cache = $this->getCacheTimestamp();

        if ($deploy && $cache && $deploy > $cache) {
            return true;
        }

        return null;
    }
}
