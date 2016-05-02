<?php
require_once 'Zend/Log.php';

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
class SS_Log {

	const ERR = Zend_Log::ERR;
	const WARN = Zend_Log::WARN;
	const NOTICE = Zend_Log::NOTICE;
	const INFO = Zend_Log::INFO;
	const DEBUG = Zend_Log::DEBUG;

	/**
	 * Logger class to use.
	 * @see SS_Log::get_logger()
	 * @var string
	 */
	public static $logger_class = 'SS_ZendLog';

	/**
	 * @see SS_Log::get_logger()
	 * @var object
	 */
	protected static $logger;

	/**
	 * @var array Logs additional context from PHP's superglobals.
	 * Caution: Depends on logger implementation (mainly targeted at {@link SS_LogEmailWriter}).
	 * @see http://framework.zend.com/manual/en/zend.log.overview.html#zend.log.overview.understanding-fields
	 */
	protected static $log_globals = array(
		'_SERVER' => array(
			'HTTP_ACCEPT',
			'HTTP_ACCEPT_CHARSET',
			'HTTP_ACCEPT_ENCODING',
			'HTTP_ACCEPT_LANGUAGE',
			'HTTP_REFERRER',
			'HTTP_USER_AGENT',
			'HTTPS',
			'REMOTE_ADDR',
		),
	);

	/**
	 * Get the logger currently in use, or create a new one if it doesn't exist.
	 *
	 * @return object
	 */
	public static function get_logger() {
		if(!static::$logger) {
			// Create default logger
			static::$logger = new static::$logger_class;

			// Add default context (shouldn't change until the actual log event happens)
			foreach(static::$log_globals as $globalName => $keys) {
				foreach($keys as $key) {
					$val = isset($GLOBALS[$globalName][$key]) ? $GLOBALS[$globalName][$key] : null;
					static::$logger->setEventItem(sprintf('$%s[\'%s\']', $globalName, $key), $val);
				}
			}

		}
		return static::$logger;
	}

	/**
	 * Get all writers in use by the logger.
	 * @return array Collection of Zend_Log_Writer_Abstract instances
	 */
	public static function get_writers() {
		return static::get_logger()->getWriters();
	}

	/**
	 * Remove all writers currently in use.
	 */
	public static function clear_writers() {
		static::get_logger()->clearWriters();
	}

	/**
	 * Remove a writer instance from the logger.
	 * @param object $writer Zend_Log_Writer_Abstract instance
	 */
	public static function remove_writer($writer) {
		static::get_logger()->removeWriter($writer);
	}

	/**
	 * Add a writer instance to the logger.
	 * @param object $writer Zend_Log_Writer_Abstract instance
	 * @param const $priority Priority. Possible values: SS_Log::ERR, SS_Log::WARN or SS_Log::NOTICE
	 * @param $comparison Priority comparison operator.  Acts on the integer values of the error
	 * levels, where more serious errors are lower numbers.  By default this is "=", which means only
	 * the given priority will be logged.  Set to "<=" if you want to track errors of *at least*
	 * the given priority.
	 */
	public static function add_writer($writer, $priority = null, $comparison = '=') {
		if($priority) $writer->addFilter(new Zend_Log_Filter_Priority($priority, $comparison));
		static::get_logger()->addWriter($writer);
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
	 */
	public static function log($message, $priority, $extras = null) {
		if($message instanceof Exception) {
			$message = array(
				'errno' => '',
				'errstr' => $message->getMessage(),
				'errfile' => $message->getFile(),
				'errline' => $message->getLine(),
				'errcontext' => $message->getTrace()
			);
		} elseif(is_string($message)) {
			$trace = SS_Backtrace::filtered_backtrace();
			$lastTrace = $trace[0];
			$message = array(
				'errno' => '',
				'errstr' => $message,
				'errfile' => isset($lastTrace['file']) ? $lastTrace['file'] : null,
				'errline' => isset($lastTrace['line']) ? $lastTrace['line'] : null,
				'errcontext' => $trace
			);
		}
		try {
			static::get_logger()->log($message, $priority, $extras);
		} catch(Exception $e) {
			// @todo How do we handle exceptions thrown from Zend_Log?
			// For example, an exception is thrown if no writers are added
		}
	}

}
