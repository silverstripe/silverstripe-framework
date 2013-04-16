<?php
require_once 'Zend/Log/Writer/Abstract.php';

/**
 * Sends an error message to an email.
 * 
 * @see SS_Log for more information on using writers.
 * 
 * @package framework
 * @subpackage dev
 */
class SS_LogEmailWriter extends Zend_Log_Writer_Abstract {

	/**
	 * @config
	 * @var $send_from Email address to send log information from
	 */
	private static $send_from = 'errors@silverstripe.com';

	protected $emailAddress;

	protected $customSmtpServer;

	public function __construct($emailAddress, $customSmtpServer = false) {
		$this->emailAddress = $emailAddress;
		$this->customSmtpServer = $customSmtpServer;
	}
	
	public static function factory($emailAddress, $customSmtpServer = false) {
		return new SS_LogEmailWriter($emailAddress, $customSmtpServer);
	}

	/**
	 * @deprecated 3.2 Use the "SS_LogEmailWriter.send_from" config setting instead
	 */
	public static function set_send_from($address) {
		Deprecation::notice('3.2', 'Use the "SS_LogEmailWriter.send_from" config setting instead');
		Config::inst()->update('SS_LogEmailWriter', 'send_from', $address);
	}

	/**
	 * @deprecated 3.2 Use the "SS_LogEmailWriter.send_from" config setting instead
	 */
	public static function get_send_from() {
		Deprecation::notice('3.2', 'Use the "SS_LogEmailWriter.send_from" config setting instead');
		return Config::inst()->get('SS_LogEmailWriter', 'send_from');
	}

	/**
	 * Send an email to the email address set in
	 * this writer.
	 */
	public function _write($event) {
		// If no formatter set up, use the default
		if(!$this->_formatter) {
			$formatter = new SS_LogErrorEmailFormatter();
			$this->setFormatter($formatter);
		}

		$formattedData = $this->_formatter->format($event);
		$subject = $formattedData['subject'];
		$data = $formattedData['data'];
		$from = Config::inst()->get('SS_LogEmailWriter', 'send_from');

		// override the SMTP server with a custom one if required
		$originalSMTP = ini_get('SMTP');
		if($this->customSmtpServer) ini_set('SMTP', $this->customSmtpServer);

		// Use plain mail() implementation to avoid complexity of Mailer implementation.
		// Only use built-in mailer when we're in test mode (to allow introspection)
		$mailer = Email::mailer();
		if($mailer instanceof TestMailer) {
			$mailer->sendHTML(
				$this->emailAddress,
				null,
				$subject,
				$data,
				null,
				"Content-type: text/html\nFrom: " . $from
			);
		} else {
			mail(
				$this->emailAddress,
				$subject,
				$data,
				"Content-type: text/html\nFrom: " . $from
			);			
		}

		// reset the SMTP server to the original
		if($this->customSmtpServer) ini_set('SMTP', $originalSMTP);
	}

}
