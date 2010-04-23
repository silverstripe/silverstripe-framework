<?php
require_once 'Zend/Log/Formatter/Interface.php';

/**
 * Formats SS error emails with a basic layout.
 * 
 * @package sapphire
 * @subpackage dev
 */
class SS_LogErrorEmailFormatter implements Zend_Log_Formatter_Interface {

	public function format($event) {
		switch($event['priorityName']) {
			case 'ERR':
				$errorType = 'Error';
				$colour = 'red';
				break;
			case 'WARN':
				$errorType = 'Warning';
				$colour = 'orange';
				break;
			case 'NOTICE':
				$errorType = 'Notice';
				$colour = 'grey';
				break;
		}

		if(!is_array($event['message'])) {
			return false;
		}

		$errno = $event['message']['errno'];
		$errstr = $event['message']['errstr'];
		$errfile = $event['message']['errfile'];
		$errline = $event['message']['errline'];
		$errcontext = $event['message']['errcontext'];

		$data = "<div style=\"border: 5px $colour solid\">\n";
		$data .= "<p style=\"color: white; background-color: $colour; margin: 0\">$errorType: $errstr<br /> At line $errline in $errfile\n<br />\n<br />\n</p>\n";

		// Get a backtrace, filtering out debug method calls
		$data .= SS_Backtrace::backtrace(true, false, array(
			'SS_LogErrorEmailFormatter->format',
			'SS_LogEmailWriter->_write'
		));

		$data .= "</div>\n";

		$relfile = Director::makeRelative($errfile);
		if($relfile[0] == '/') $relfile = substr($relfile, 1);

		$subject = "$errorType at $relfile line {$errline} (http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI])";

		return array(
			'subject' => $subject,
			'data' => $data
		);
	}

}