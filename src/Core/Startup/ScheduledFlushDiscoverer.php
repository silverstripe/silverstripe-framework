<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Core\Kernel;

/**
 * Checks the manifest cache for flush being scheduled in a
 * previous request
 */
class ScheduledFlushDiscoverer implements FlushDiscoverer
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
     * Returns the flag whether the manifest flush
     * has been scheduled in previous requests
     *
     * @return bool unix timestamp
     */
    protected function getFlush()
    {
        $classLoader = $this->kernel->getClassLoader();
        $classManifest = $classLoader->getManifest();

        return (bool) $classManifest->isFlushScheduled();
    }

    /**
     * @internal This method is not a part of public API and will be deleted without a deprecation warning
     *
     * This method is here so that scheduleFlush functionality implementation is kept close to the check
     * implementation.
     */
    public static function scheduleFlush(Kernel $kernel)
    {
        $classLoader = $kernel->getClassLoader();
        $classManifest = $classLoader->getManifest();

        if (!$classManifest->isFlushScheduled()) {
            $classManifest->scheduleFlush();
            return true;
        }

        return false;
    }

    public function shouldFlush()
    {
        if ($this->getFlush()) {
            return true;
        }

        return null;
    }
}
