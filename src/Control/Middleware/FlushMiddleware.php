<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\BaseKernel;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;

/**
 * Triggers a call to flush() on all implementors of Flushable.
 */
class FlushMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $kernel = Injector::inst()->get(Kernel::class);
        if ((method_exists($kernel, 'isFlushed') && $kernel->isFlushed())) {
            // Disable cache when flushing
            HTTPCacheControlMiddleware::singleton()->disableCache(true);

            foreach (ClassInfo::implementorsOf(Flushable::class) as $class) {
                /** @var Flushable|string $class */
                $class::flush();
            }
        }

        return $delegate($request);
    }
}
