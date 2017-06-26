<?php

namespace SilverStripe\Core;

use SilverStripe\Control\Middleware\FlushMiddleware;

/**
 * Provides an interface for classes to implement their own flushing functionality
 * whenever flush=1 is requested.
 */
interface Flushable
{

    /**
     * This function is triggered early in the request if the "flush" query
     * parameter has been set. Each class that implements Flushable implements
     * this function which looks after it's own specific flushing functionality.
     *
     * @see FlushMiddleware
     */
    public static function flush();
}
