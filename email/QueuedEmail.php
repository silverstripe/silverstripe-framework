<?php

/**
 * @package sapphire
 * @subpackage email
 */

/**
 * Stores a queued email to be sent at the given time
 * @package sapphire
 * @subpackage email
 */
class QueuedEmail extends DataObject {
	
	static $db = array(
		'Send' => 'Datetime',
		'Subject' => 'Varchar',
		'From' => 'Varchar',
		'Content' => 'Text'
	);
	
	static $has_one = array(
		'To' => 'Member'
	);
	
	// overwrite this method to provide a check whether or not to send the email
	function canSendEmail() {
		return true;
	}
	
	function send() {
		$email = new Email( $this->From, $this->To()->Email, $this->Subject, $this->Content );
		$email->send();
	}
}
?>
