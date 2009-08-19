<?php
/**
 * Sends an error message to an email whenever an error occurs
 * in sapphire.
 * 
 * @see SSLog for more information on using writers.
 * 
 * @package sapphire
 * @subpackage dev
 */

require_once 'Zend/Log/Writer/Abstract.php';

class SSLogEmailWriter extends Zend_Log_Writer_Abstract {

	protected $emailAddress;

	protected $customSmtpServer;

	public function __construct($emailAddress, $customSmtpServer = false) {
		$this->emailAddress = $emailAddress;
		$this->customSmtpServer = $customSmtpServer;
	}

	/**
	 * Send an email to the designated emails set in
	 * {@link Debug::send_errors_to()}
	 */
	public function _write($event) {
		// If no formatter set up, use the default
		if(!$this->_formatter) {
			$formatter = new SSLogErrorEmailFormatter();
			$this->setFormatter($formatter);
		}

		$formattedData = $this->_formatter->format($event);
		$subject = $formattedData['subject'];
		$data = $formattedData['data'];

		$originalSMTP = ini_get('SMTP');
		// override the SMTP server with a custom one if required
		if($this->customSmtpServer) ini_set('SMTP', $this->customSmtpServer);

		mail($this->emailAddress, $subject, $data, "Content-type: text/html\nFrom: errors@silverstripe.com");

		// reset the SMTP server to the original
		if($this->customSmtpServer) ini_set('SMTP', $originalSMTP);
	}

}