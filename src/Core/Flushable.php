<?php

namespace SilverStripe\Core;

/**
 * Provides an interface for classes to implement their own flushing functionality
 * whenever flush=1 is requested.
 */
interface Flushable
{

    /**
     * This function is triggered early in the request if the kernel gets flushed.
     * Each class that implements Flushable implements
     * this function which looks after it's own specific flushing functionality.
     */
    public static function flush();
}
