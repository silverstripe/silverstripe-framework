<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Flushable;
use SilverStripe\Core\ClassInfo;

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
                $class::flush();
            }
        }

        return $delegate($request);
    }
}
