<?php
require_once 'Zend/Log/Writer/Abstract.php';

/**
 * Sends an error message to the system log whenever an
 * error occurs.
 *
 * @see SS_Log for more information on using writers
 * @uses Zend_Log_Writer_Abstract
 * @package framework
 * @subpackage dev
 */
class SS_SysLogWriter extends Zend_Log_Writer_Abstract {

	/**
	 * @param string $ident Identity of log, defaults to "Silverstripe_log" if null
	 * @param $options Option constants, passed to openlog()
	 * @param $facility Type of program logging the message, passed to openlog()
	 */
	public function __construct($ident = null, $options = null, $facility = LOG_LOCAL0) {
		if(!$ident) $ident = 'SilverStripe_log';
		if(!$options) $options = LOG_PID | LOG_PERROR;
		openlog($ident, $options, $facility);
	}

	/**
	 * Close the log when this object is destroyed.
	 */
	public function __destruct() {
		closelog();
	}

	/**
	 * @param $option See {@link __construct}
	 * @return SS_SysLogWriter
	 */
	static public function factory($config) {
		return new SS_SysLogWriter(null, $config);
	}

	/**
	 * Write to the system log with the event details.
	 * @param array $event Error details
	 */
	public function _write($event) {
		// If no formatter set up, use default then log the event
		if(!$this->_formatter) $this->setFormatter(new SS_LogErrorFileFormatter());
		syslog($event['priority'], $this->_formatter->format($event));
	}

}
