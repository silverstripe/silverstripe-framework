<?php
require_once 'Zend/Log/Formatter/Interface.php';

/**
 * Formats SS error entries in an error file log.
 * Format: [d-M-Y h:i:s] <type> at <file> line <line>: <errormessage> <url>
 * @package framework
 * @subpackage dev
 */
class SS_LogErrorFileFormatter implements Zend_Log_Formatter_Interface {

	public function format($event) {
		$errno = $event['message']['errno'];
		$errstr = $event['message']['errstr'];
		$errfile = $event['message']['errfile'];
		$errline = $event['message']['errline'];
		$errcontext = $event['message']['errcontext'];

		switch($event['priorityName']) {
			case 'ERR':
				$errtype = 'Error';
				break;
			case 'WARN':
				$errtype = 'Warning';
				break;
			case 'NOTICE':
				$errtype = 'Notice';
				break;
			default:
				$errtype = $event['priorityName'];
		}

		$urlSuffix = '';
		$relfile = Director::makeRelative($errfile);
		if(strlen($relfile) && $relfile[0] == '/') $relfile = substr($relfile, 1);
		if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] && isset($_SERVER['REQUEST_URI'])) {
			$urlSuffix = " (http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI])";
		}

		return '[' . date('d-M-Y H:i:s') . "] $errtype at $relfile line $errline: $errstr$urlSuffix" . PHP_EOL;
	}

}
