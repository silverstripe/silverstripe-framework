<?php

/**
 * Service used to access logging functionality within the system
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class Logger {
	
	/**
	 * Create a logger and initialise the SS_Log subsystem
	 *
	 * @param array $logs 
	 */
	public function __construct($logs = array()) {
		foreach ($logs as $log) {
			$level = isset($log['level']) ? $log['level'] : SS_Log::WARN;
			$type = isset($log['type']) ? $log['type'] : '<=';
			SS_Log::add_writer($log['writer'], $level, $type);
		}
	}

	public function log($message, $level=Zend_Log::NOTICE, $errfile = null, $errno=null, $errline=null, $errcontext=null) {
		if (!$level) {
			$level = SS_Log::NOTICE;
		}

		$message = array(
			'errno' => $errno,
			'errstr' => $message,
			'errfile' => $errfile ? $errfile : __FILE__,
			'errline' => $errline,
			'errcontext' => $errcontext
		);

		SS_Log::log($message, $level);
	}
}
