<?php
require_once 'Zend/Log/Formatter/Interface.php';

/**
 * Formats SS error emails with a basic layout.
 * 
 * @package framework
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

		$data = '';
		$data .= '<style type="text/css">html, body, table {font-family: sans-serif; font-size: 12px;}</style>';
		$data .= "<div style=\"border: 5px $colour solid;\">\n";
		$data .= "<p style=\"color: white; background-color: $colour; margin: 0\">[$errorType] $errstr<br />$errfile:$errline\n<br />\n<br />\n</p>\n";

		// Get a backtrace, filtering out debug method calls
		$data .= SS_Backtrace::backtrace(true, false, array(
			'SS_LogErrorEmailFormatter->format',
			'SS_LogEmailWriter->_write'
		));

		// Compile extra data
		$blacklist = array('message', 'timestamp', 'priority', 'priorityName');
		$extras = array_diff_key($event, array_combine($blacklist, $blacklist));
		if($extras) {
			$data .= "<h3>Details</h3>\n";
			$data .= "<table class=\"extras\">\n";
			foreach($extras as $k => $v) {
				if(is_array($v)) $v = var_export($v, true);
				$data .= sprintf(
					"<tr><td><strong>%s</strong></td><td><pre>%s</pre></td></tr>\n", $k, $v);
			}
			$data .= "</table>\n";			
		}

		$data .= "</div>\n";

		$relfile = Director::makeRelative($errfile);
		if($relfile && $relfile[0] == '/') $relfile = substr($relfile, 1);
		
		$host = @$_SERVER['HTTP_HOST'];
		$uri = @$_SERVER['REQUEST_URI'];

		$subject = "[$errorType] in $relfile:{$errline} (http://{$host}{$uri})";

		return array(
			'subject' => $subject,
			'data' => $data
		);
	}

}
