<?php

/**
 * Wrapper class for a logging handler like {@link Zend_Log}
 * which takes a message (or a map of context variables) and
 * sends it to one or more {@link Zend_Log_Writer_Abstract}
 * subclasses for output.
 *
 * These priorities are currently supported:
 *  - SS_Log::ERR
 *  - SS_Log::WARN
 *  - SS_Log::NOTICE
 *
 * You can add an error writer by calling {@link SS_Log::add_writer()}
 *
 * Example usage of logging errors by email notification:
 * <code>
 * SS_Log::add_writer(new SS_LogEmailWriter('my@email.com'), SS_Log::ERR);
 * </code>
 *
 * Example usage of logging errors by file:
 * <code>
 *	SS_Log::add_writer(new SS_LogFileWriter('/var/log/silverstripe/errors.log'), SS_Log::ERR);
 * </code>
 *
 * Example usage of logging at warnings and errors by setting the priority to '<=':
 * <code>
 * SS_Log::add_writer(new SS_LogEmailWriter('my@email.com'), SS_Log::WARN, '<=');
 * </code>
 *
 * Each writer object can be assigned a formatter. The formatter is
 * responsible for formatting the message before giving it to the writer.
 * {@link SS_LogErrorEmailFormatter} is such an example that formats errors
 * into HTML for human readability in an email client.
 *
 * Formatters are added to writers like this:
 * <code>
 * $logEmailWriter = new SS_LogEmailWriter('my@email.com');
 * $myEmailFormatter = new MyLogEmailFormatter();
 * $logEmailWriter->setFormatter($myEmailFormatter);
 * </code>
 *
 * @package framework
 * @subpackage dev
 */
class SS_Log
{

	const ERR = 'error';
	const WARN = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';

	/**
	 * Get the logger currently in use, or create a new one if it doesn't exist.
	 *
	 * @return Psr\Log\LoggerInterface
	 */
	public static function get_logger() {
		Deprecation::notice('4.0', 'Use Injector::inst()->get(\'Logger\') instead');
		return Injector::inst()->get('Logger');
	}

	public static function add_writer($writer) {
		throw new \InvalidArgumentException("SS_Log::add_writer() on longer works. Please use a Monolog Handler "
			."instead, and add list of handlers in the Logger.handlers configuration parameter.");
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
	 * @param const $priority Priority. Possible values: SS_Log::ERR, SS_Log::WARN or SS_Log::NOTICE
	 * @param  mixed    $extras    Extra information to log in event
	 *
	 * @deprecated 4.0.0:5.0.0 Use Injector::inst()->get('Logger')->log($priority, $message) instead
	 */
	public static function log($message, $priority, $extras = null) {
		Deprecation::notice('4.0', 'Use Injector::inst()->get(\'Logger\')->log($priority, $message) instead');

		Injector::inst()->get('Logger')->log($priority, $message);
	}
}
