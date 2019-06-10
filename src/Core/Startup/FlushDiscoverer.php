<?php

namespace SilverStripe\Core\Startup;

/**
 * Public interface for startup flush discoverers
 */
interface FlushDiscoverer
{
    /**
     * Check whether we have to flush manifest
     *
     * The return value is either null or a bool
     *   - null means the discoverer does not override the default behaviour (other discoverers decision)
     *   - bool means the discoverer wants to force flush or prevent it (true or false respectively)
     *
     * @return null|bool null if don't care or bool to force or prevent flush
     */
    public function shouldFlush();
}
