<?php
/**
 * @package framework
 * @subpackage email
 */
class TestMailer extends Mailer {
	protected $emailsSent = array();
	
	/**
	 * Send a plain-text email.
	 * TestMailer will merely record that the email was asked to be sent, without sending anything.
	 */
	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customHeaders = false) {
		$this->emailsSent[] = array(
			'type' => 'plain',
			'to' => $to,
			'from' => $from,
			'subject' => $subject,

			'content' => $plainContent,
			'plainContent' => $plainContent,

			'attachedFiles' => $attachedFiles,
			'customHeaders' => $customHeaders,
		);
		
		return true;
	}
	
	/**
	 * Send a multi-part HTML email
	 * TestMailer will merely record that the email was asked to be sent, without sending anything.
	 */
	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customHeaders = false,
			$plainContent = false, $inlineImages = false) {

		$this->emailsSent[] = array(
			'type' => 'html',
			'to' => $to,
			'from' => $from,
			'subject' => $subject,

			'content' => $htmlContent,
			'plainContent' => $plainContent,
			'htmlContent' => $htmlContent,

			'attachedFiles' => $attachedFiles,
			'customHeaders' => $customHeaders,
			'inlineImages' => $inlineImages,
		);
		
		return true;
	}
	
	/**
	 * Clear the log of emails sent
	 */
	public function clearEmails() {
		$this->emailsSent = array();
	}
	
	/**
	 * Search for an email that was sent.
	 * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
	 * @param $to
	 * @param $from
	 * @param $subject
	 * @param $content
	 * @return array Contains the keys: 'type', 'to', 'from', 'subject', 'content', 'plainContent', 'attachedFiles',
	 *               'customHeaders', 'htmlContent', 'inlineImages'
	 */
	public function findEmail($to, $from = null, $subject = null, $content = null) {
		foreach($this->emailsSent as $email) {
			$matched = true;

			foreach(array('to','from','subject','content') as $field) {
				if($value = $$field) {
					if($value[0] == '/') $matched = preg_match($value, $email[$field]);
					else $matched = ($value == $email[$field]);
					if(!$matched) break;
				}
			}
			
			if($matched) return $email;
		}
	}


}
