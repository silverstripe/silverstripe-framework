<?php
require_once 'Zend/Log/Writer/Abstract.php';

/**
 * Sends an error message to an email.
 * 
 * @see SS_Log for more information on using writers.
 * 
 * @package sapphire
 * @subpackage dev
 */
class SS_LogEmailWriter extends Zend_Log_Writer_Abstract {

	/**
	 * @var $send_from Email address to send log information from
	 */
	protected static $send_from = 'errors@silverstripe.com';

	protected $emailAddress;

	protected $customSmtpServer;

	public function __construct($emailAddress, $customSmtpServer = false) {
		$this->emailAddress = $emailAddress;
		$this->customSmtpServer = $customSmtpServer;
	}

	public static function set_send_from($address) {
		self::$send_from = $address;
	}

	public static function get_send_from() {
		return self::$send_from;
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

		$originalSMTP = ini_get('SMTP');
		// override the SMTP server with a custom one if required
		if($this->customSmtpServer) ini_set('SMTP', $this->customSmtpServer);

		mail(
			$this->emailAddress,
			$subject,
			$data,
			"Content-type: text/html\nFrom: " . self::$send_from
		);

		// reset the SMTP server to the original
		if($this->customSmtpServer) ini_set('SMTP', $originalSMTP);
	}

}