<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\Deprecation;

/**
 * Triggers a call to flush() on all implementors of Flushable.
 * @deprecated 5.4.0 Will be replaced with flushing inside the Kernel directly in a future major release.
 */
class FlushMiddleware implements HTTPMiddleware
{
    public function __construct()
    {
        Deprecation::noticeWithNoReplacment('5.4.0', 'Will be replaced with flushing inside the Kernel directly in a future major release.');
    }

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
