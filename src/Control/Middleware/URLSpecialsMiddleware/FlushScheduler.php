<?php

namespace SilverStripe\Control\Middleware\URLSpecialsMiddleware;

use SilverStripe\Core\BaseKernel;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Startup\ScheduledFlushDiscoverer;
use SilverStripe\Control\HTTPRequest;

/**
 * Schedule flush operation for a following request
 *
 * The scheduler does not trigger a flush but rather puts a marker
 * into the manifest cache so that one of the next Requests can
 * find it and perform the actual manifest flush.
 */
trait FlushScheduler
{
    /**
     * Schedules the manifest flush operation for a following request
     *
     * WARNING! Does not perform flush, but schedules it for another request
     *
     * @param HTTPRequest $request
     *
     * @return bool true if flush has been scheduled, false otherwise
     */
    public function scheduleFlush(HTTPRequest $request)
    {
        $flush = array_key_exists('flush', $request->getVars() ?? []) || ($request->getURL() === 'dev/build');

        $kernel = Injector::inst()->get(Kernel::class);
        if (!$flush || (method_exists($kernel, 'isFlushed') && $kernel->isFlushed())) {
            return false;
        }

        ScheduledFlushDiscoverer::scheduleFlush($kernel);

        return true;
    }
}
