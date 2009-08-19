<?php
/**
 * Modifications to Zend_Log to make it work nicer
 * with SSErrorLog. Specifically, this includes removing
 * writers that have been added to the logger, as well as
 * listing which ones are currently in use.
 * 
 * @package sapphire
 * @subpackage dev
 */

require_once 'Zend/Log.php';

class SSZendLog extends Zend_Log {
	
	public function getWriters() {
		return $this->_writers;
	}
	
	/**
	 * Remove a writer instance that exists in
	 * the current writers collection for this logger.
	 * 
	 * @param object Zend_Log_Writer_Abstract instance
	 */
	public function removeWriter($writer) {
		foreach($this->_writers as $index => $existingWriter) {
			if($existingWriter == $writer) {
				unset($this->_writers[$index]);
			}
		}
	}
	
}