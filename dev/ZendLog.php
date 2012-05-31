<?php
require_once 'Zend/Log.php';

/**
 * Extensions to Zend_Log to make it work nicer
 * with {@link SS_Log}.
 * 
 * Please refer to {@link SS_Log} for information on
 * setting up logging for your projects.
 * 
 * @package sapphire
 * @subpackage dev
 */
class SS_ZendLog extends Zend_Log {

	/**
	 * @var array List of callable, keyed by a unique identifier.
	 * The callables should return a map of values.
	 * Logs additional event context, e.g. PHP's superglobals.
	 * Caution: Depends on logger implementation (mainly targeted at {@link SS_LogEmailWriter}).
	 * @see http://framework.zend.com/manual/en/zend.log.overview.html#zend.log.overview.understanding-fields
	 */
	protected $eventItemCallbacks = array();

	public function __construct(Zend_Log_Writer_Abstract $writer = null) {
		$this->eventItemCallbacks['globals'] = function() {
			$names = array(
				'HTTP_ACCEPT',
				'HTTP_ACCEPT_CHARSET', 
				'HTTP_ACCEPT_ENCODING', 
				'HTTP_ACCEPT_LANGUAGE', 
				'HTTP_REFERRER',
				'HTTP_USER_AGENT',
				'HTTPS',
				'REMOTE_ADDR',
			);
			return array_intersect_key($_SERVER, array_combine($names, $names));
		};

		parent::__construct();
	}

	public function log($message, $priority, $extras) {
		// Collect extras
		if(!$extras) $extras = array();
		foreach($this->eventItemCallbacks as $id => $callable) {
			$extras = array_merge($extras, call_user_func($callable));
		}

		// Add extras to event
		foreach($extras as $key => $val) {
			$this->setEventItem($key, $val);
		}

		parent::log($message, $priority, $extras);
	}

	/**
	 * Get all writers in this logger.
	 * @return array of Zend_Log_Writer_Abstract instances
	 */
	public function getWriters() {
		return $this->_writers;
	}

	/**
	 * Remove a writer instance that exists in this logger.
	 * @param object Zend_Log_Writer_Abstract instance
	 */
	public function removeWriter($writer) {
		foreach($this->_writers as $index => $existingWriter) {
			if($existingWriter == $writer) {
				unset($this->_writers[$index]);
			}
		}
	}

	/**
	 * Clear all writers in this logger.
	 */
	public function clearWriters() {
		$this->_writers = array();
	}

	/**
	 * @param Int $id
	 * @param Callable $callable
	 */
	public function addEventItemCallback($id, $callable) {
		$this->eventItemCallbacks[$id] = $callable;
	}

	/**
	 * @return array
	 */
	public function getEventItemCallbacks() {
		return $this->eventItemCallbacks;
	}

}