<?php
require_once 'Zend/Log/Writer/Abstract.php';

/**
 * Writes an error message to a file.
 * 
 * Note: You need to make sure your web server is able
 * to write to the file path that you specify to write
 * logs to.
 * 
 * @uses error_log() built-in PHP function.
 * @see SS_Log for more information on using writers.
 * 
 * @package framework
 * @subpackage dev
 */
class SS_LogFileWriter extends Zend_Log_Writer_Abstract {

	/**
	 * The path to the file that errors will be stored in.
	 * For example, "/var/logs/silverstripe/errors.log".
	 * 
	 * @var string
	 */
	protected $path;
	
	/**
	 * Message type to pass to error_log()
	 * @see http://us3.php.net/manual/en/function.error-log.php
	 * @var int
	 */
	protected $messageType;
	
	/**
	 * Extra headers to pass to error_log()
	 * @see http://us3.php.net/manual/en/function.error-log.php
	 * @var string
	 */
	protected $extraHeaders;

	public function __construct($path, $messageType = 3, $extraHeaders = '') {
		$this->path = $path;
		$this->messageType = $messageType;
		$this->extraHeaders = $extraHeaders;
	}
	
	public static function factory($path, $messageType = 3, $extraHeaders = '') {
		return new SS_LogFileWriter($path, $messageType, $extraHeaders);
	}

	/**
	 * Write the log message to the file path set
	 * in this writer.
	 */
	public function _write($event) {
		if(!$this->_formatter) {
			$formatter = new SS_LogErrorFileFormatter();
			$this->setFormatter($formatter);
		}
		$message = $this->_formatter->format($event);
		if(!file_exists(dirname($this->path))) mkdir(dirname($this->path), 0755, true);
		error_log($message, $this->messageType, $this->path, $this->extraHeaders);
	}

}
