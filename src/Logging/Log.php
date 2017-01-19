<?php

namespace SilverStripe\Logging;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;

/**
 * Wrapper class for a logging handler like {@link Zend_Log}
 * which takes a message (or a map of context variables) and
 * sends it to one or more {@link Zend_Log_Writer_Abstract}
 * subclasses for output.
 *
 * These priorities are currently supported:
 *  - Log::ERR
 *  - Log::WARN
 *  - Log::NOTICE
 *  - Log::INFO
 *  - Log::DEBUG
*/
class Log
{

    const ERR = 'error';
    const WARN = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Get the logger currently in use, or create a new one if it doesn't exist.
     *
     * @deprecated 4.0..5.0
     * @return LoggerInterface
     */
    public static function get_logger()
    {
        Deprecation::notice('5.0', 'Use Injector::inst()->get(\'Logger\') instead');
        return Injector::inst()->get('Logger');
    }

    /**
     * Dispatch a message by priority level.
     *
     * The message parameter can be either a string (a simple error
     * message), or an array of variables. The latter is useful for passing
     * along a list of debug information for the writer to handle, such as
     * error code, error line, error context (backtrace).
     *
     * @param mixed $message Exception object or array of error context variables
     * @param string $priority Priority. Possible values: Log::ERR, Log::WARN, Log::NOTICE, Log::INFO or Log::DEBUG
     *
     * @deprecated 4.0.0:5.0.0 Use Injector::inst()->get('Logger')->log($priority, $message) instead
     */
    public static function log($message, $priority)
    {
        Deprecation::notice('5.0', 'Use Injector::inst()->get(\'Logger\')->log($priority, $message) instead');
        Injector::inst()->get('Logger')->log($priority, $message);
    }
}
