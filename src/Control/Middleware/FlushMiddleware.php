<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;

/**
 * Triggers a call to flush() on all implementors of Flushable.
 */
class FlushMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        if (Director::isManifestFlushed()) {
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
