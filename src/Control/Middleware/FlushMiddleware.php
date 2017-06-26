<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;

/**
 * Triggers a call to flush() on all implementors of Flushable.
 */
class FlushMiddleware implements HTTPMiddleware
{
    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if (array_key_exists('flush', $request->getVars())) {
            foreach (ClassInfo::implementorsOf(Flushable::class) as $class) {
                /** @var Flushable|string $class */
                $class::flush();
            }
        }

        return $delegate($request);
    }
}
