<?php declare(strict_types = 1);

namespace SilverStripe\Logging;

/**
 * Core error handler for a SilverStripe application
 */
interface ErrorHandler
{
    /**
     * Register and begin handling errors with this handler
     */
    public function start();
}
