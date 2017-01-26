<?php

namespace SilverStripe\Core;

/**
 * Represents a class with a local cache which normally invalidates itself between requests.
 *
 * Designed so that tests can automatically flush these objects between runs in lieu
 * of a real change.
 */
interface Resettable
{
    /**
     * Reset the local cache of this object
     */
    public static function reset();
}
