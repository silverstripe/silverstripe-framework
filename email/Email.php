<?php

/**
 * @package framework
 * @subpackage email
 */

if(isset($_SERVER['SERVER_NAME'])) {
	/**
	 * X-Mailer header value on emails sent
	 */
	define('X_MAILER', 'SilverStripe Mailer - version 2006.06.21 (Sent from "'.$_SERVER['SERVER_NAME'].'")');
} else {
	/**
	 * @ignore
	 */
	define('X_MAILER', 'SilverStripe Mailer - version 2006.06.21');
}
// Note: The constant 'BOUNCE_EMAIL' should be defined as a valid email address for where bounces should be returned to.

/**
 * Class to support sending emails.
 * @package framework
 * @subpackage email
 */
class Email extends ViewableData {
	
	/**
	 * @param string $from Email-Address
	 */
	protected $from;
	
	/**
	 * @param string $to Email-Address. Use comma-separation to pass multiple email-addresses.
	 */
	protected $to;
	
	/**
	 * @param string $subject Subject of the email
	 */
	protected $subject;
	
	/**
	 * @param string $body HTML content of the email.
	 * Passed straight into {@link $ss_template} as $Body variable.
	 */
	protected $body;
	
	/**
	 * @param string $plaintext_body Optional string for plaintext emails.
	 * If not set, defaults to converting the HTML-body with {@link Convert::xml2raw()}.
	 */
	protected $plaintext_body;
	
	/**
	 * @param string $cc
	 */
	protected $cc;
	
	/**
	 * @param string $bcc
	 */
	protected $bcc;
	
	/**
	 * @param Mailer $mailer Instance of a {@link Mailer} class.
	 */
	protected static $mailer;
	
	/**
	 * This can be used to provide a mailer class other than the default, e.g. for testing.
	 * 
	 * @param Mailer $mailer
	 */
	static function set_mailer(Mailer $mailer) {
		self::$mailer = $mailer;
	}
	
	/**
	 * Get the mailer.
	 * 
	 * @return Mailer
	 */
	static function mailer() {
		if(!self::$mailer) self::$mailer = new Mailer();
		return self::$mailer;
	}
	
	/**
	 * @param array $customHeaders A map of header-name -> header-value
	 */
	protected $customHeaders = array();

	/**
	 * @param array $attachements Internal, use {@link attachFileFromString()} or {@link attachFile()}
	 */
	protected $attachments = array();
	
	/**
	 * @param boolean $
	 */
	protected $parseVariables_done = false;
	
	/**
	 * @param string $ss_template The name of the used template (without *.ss extension)
	 */
	protected $ss_template = "GenericEmail";
	
	/**
	 * @param array $template_data Additional data available in a template.
	 * Used in the same way than {@link ViewableData->customize()}.
	 */
	protected $template_data = null;
	
	/**
	 * @param string $bounceHandlerURL
	 */
	protected $bounceHandlerURL = null;
	
	/**
	 * @param sring $admin_email_address The default administrator email address. 
	 * This will be set in the config on a site-by-site basis
	 */
	static $admin_email_address = '';

	/**
	 * @param string $send_all_emails_to Email-Address
	 */
	protected static $send_all_emails_to = null;
	
	/**
	 * @param string $bcc_all_emails_to Email-Address
	 */
	protected static $bcc_all_emails_to = null;
	
	/**
	 * @param string $cc_all_emails_to Email-Address
	 */
	protected static $cc_all_emails_to = null;
		
	/**
	 * Create a new email.
	 */
	public function __construct($from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null) {
		if($from != null) $this->from = $from;
		if($to != null) $this->to = $to;
		if($subject != null) $this->subject = $subject;
		if($body != null) $this->body = $body;
		if($cc != null) $this->cc = $cc;
		if($bcc != null) $this->bcc = $bcc;
		if($bounceHandlerURL != null) $this->setBounceHandlerURL($bounceHandlerURL);
		parent::__construct();
	}
	
	public function attachFileFromString($data, $filename, $mimetype = null) {
		$this->attachments[] = array(
			'contents' => $data,
			'filename' => $filename,
			'mimetype' => $mimetype,
		);
	}
	
	public function setBounceHandlerURL( $bounceHandlerURL ) {
		if($bounceHandlerURL) {
			$this->bounceHandlerURL = $bounceHandlerURL;
		} else {
			$this->bounceHandlerURL = $_SERVER['HTTP_HOST'] . Director::baseURL() . 'Email_BounceHandler';
		}
	}
	
	public function attachFile($filename, $attachedFilename = null, $mimetype = null) {
		if(!$attachedFilename) $attachedFilename = basename($filename);
		$absoluteFileName = Director::getAbsFile($filename);
		if(file_exists($absoluteFileName)) {
			$this->attachFileFromString(file_get_contents($absoluteFileName), $attachedFilename, $mimetype);
		} else {
			user_error("Could not attach '$absoluteFileName' to email. File does not exist.", E_USER_NOTICE);
		}
	}

	public function Subject() {
		return $this->subject;
	}
	
	public function Body() {
		return $this->body;
	}
	
	public function To() {
		return $this->to;
	}
	
	public function From() {
		return $this->from;
	}
	
	public function Cc() {
		return $this->cc;
	}
	
	public function Bcc() {
		return $this->bcc;
	}
	
	public function setSubject($val) { 
		$this->subject = $val; 
	}
	
	public function setBody($val) { 
		$this->body = $val; 
	}
	
	public function setTo($val) { 
		$this->to = $val; 
	}
	
	public function setFrom($val) { 
		$this->from = $val; 
	}
	
	public function setCc($val) {
		$this->cc = $val;
	}
	
	public function setBcc($val) {
		$this->bcc = $val;
	}
	
	/**
	 * Set the "Reply-To" header with an email address.
	 * @param string $email The email address of the "Reply-To" header
	 */
	public function replyTo($email) { 
		$this->addCustomHeader('Reply-To', $email); 
	}
	
	/**
	 * Add a custom header to this value.
	 * Useful for implementing all those cool features that we didn't think of.
	 * 
	 * @param string $headerName
	 * @param string $headerValue
	 */
	public function addCustomHeader($headerName, $headerValue) {
		if($headerName == 'Cc') $this->cc = $headerValue;
		else if($headerName == 'Bcc') $this->bcc = $headerValue;
		else {
			if(isset($this->customHeaders[$headerName])) $this->customHeaders[$headerName] .= ", " . $headerValue;
			else $this->customHeaders[$headerName] = $headerValue;
		}
	}

	public function BaseURL() {
		return Director::absoluteBaseURL();
	}
	
	/**
	 * Debugging help
	 */
	public function debug() {
		$this->parseVariables();

		return "<h2>Email template $this->class</h2>\n" . 
			"<p><b>From:</b> $this->from\n" .
			"<b>To:</b> $this->to\n" . 
			"<b>Cc:</b> $this->cc\n" . 
			"<b>Bcc:</b> $this->bcc\n" . 
			"<b>Subject:</b> $this->subject</p>" . 
			$this->body;
	}

	/**
	 * Set template name (without *.ss extension).
	 * 
	 * @param string $template
	 */
	public function setTemplate($template) {
		$this->ss_template = $template;
	}
	
	/**
	 * @return string
	 */
	public function getTemplate() {
		return $this->ss_template;
	}

	protected function templateData() {
		if($this->template_data) {
			return $this->template_data->customise(array(
				"To" => $this->to,
				"Cc" => $this->cc,
				"Bcc" => $this->bcc,
				"From" => $this->from,
				"Subject" => $this->subject,
				"Body" => $this->body,
				"BaseURL" => $this->BaseURL(),
				"IsEmail" => true,
			));
		} else {
			return $this;
		}
	}
	
	/**
	 * Used by {@link SSViewer} templates to detect if we're rendering an email template rather than a page template
	 */
	public function IsEmail() {
		return true;
	}
	
	/**
	 * Populate this email template with values.
	 * This may be called many times.
	 */
	function populateTemplate($data) {
		if($this->template_data) {
			$this->template_data = $this->template_data->customise($data);	
		} else {
			if(is_array($data)) $data = new ArrayData($data);
			$this->template_data = $this->customise($data);
		}
		$this->parseVariables_done = false;
	}
	
	/**
	 * Load all the template variables into the internal variables, including
	 * the template into body.	Called before send() or debugSend()
	 * $isPlain=true will cause the template to be ignored, otherwise the GenericEmail template will be used
	 * and it won't be plain email :) 
	 */
	protected function parseVariables($isPlain = false) {
		SSViewer::set_source_file_comments(false);
		
		if(!$this->parseVariables_done) {
			$this->parseVariables_done = true;

			// Parse $ variables in the base parameters
			$data = $this->templateData();
			
			// Process a .SS template file
			$fullBody = $this->body;
			if($this->ss_template && !$isPlain) {
				// Requery data so that updated versions of To, From, Subject, etc are included
				$data = $this->templateData();
				
				$template = new SSViewer($this->ss_template);
				
				if($template->exists()) {
					$fullBody = $template->process($data);
				}
			}
			
			// Rewrite relative URLs
			$this->body = HTTP::absoluteURLs($fullBody);
		}
	}
	
	/**
	 * @desc Validates the email address. Returns true of false
	 */
	static function validEmailAddress($address) {
        if (function_exists('filter_var')) {
            return filter_var($address, FILTER_VALIDATE_EMAIL);
        } else {
            return preg_match('#^([a-zA-Z0-9_+\.\-]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$#', $address);
        }
	}

	/**
	 * Send the email in plaintext.
	 * 
	 * @see send() for sending emails with HTML content.
	 * @uses Mailer->sendPlain()
	 * 
	 * @param string $messageID Optional message ID so the message can be identified in bounces etc.
	 * @return bool Success of the sending operation from an MTA perspective. 
	 * Doesn't actually give any indication if the mail has been delivered to the recipient properly)
	 */
	function sendPlain($messageID = null) {
		Requirements::clear();
		
		$this->parseVariables(true);
		
		if(empty($this->from)) $this->from = Email::getAdminEmail();
						
		$this->setBounceHandlerURL($this->bounceHandlerURL);
		
		$headers = $this->customHeaders;
		
		$headers['X-SilverStripeBounceURL'] = $this->bounceHandlerURL;
						
		if($messageID) $headers['X-SilverStripeMessageID'] = project() . '.' . $messageID;
						
		if(project()) $headers['X-SilverStripeSite'] = project();

		$to = $this->to;
		$subject = $this->subject;
		if(self::$send_all_emails_to) {
			$subject .= " [addressed to $to";
			$to = self::$send_all_emails_to;
			if($this->cc) $subject .= ", cc to $this->cc";
			if($this->bcc) $subject .= ", bcc to $this->bcc";
			$subject .= ']';
		} else {
			if($this->cc) $headers['Cc'] = $this->cc;
			if($this->bcc) $headers['Bcc'] = $this->bcc;
		}
	
		if(self::$cc_all_emails_to) {
			if(!empty($headers['Cc']) && trim($headers['Cc'])) {
				$headers['Cc'] .= ', ' . self::$cc_all_emails_to;		
			} else {
				$headers['Cc'] = self::$cc_all_emails_to;
			}
		}

		if(self::$bcc_all_emails_to) {
			if(!empty($headers['Bcc']) && trim($headers['Bcc'])) {
				$headers['Bcc'] .= ', ' . self::$bcc_all_emails_to;
			} else {
				$headers['Bcc'] = self::$bcc_all_emails_to;
			}
		}

		Requirements::restore();
		
		return self::mailer()->sendPlain($to, $this->from, $subject, $this->body, $this->attachments, $headers);
	}
	
	/**
	 * Send an email with HTML content.
	 *
	 * @see sendPlain() for sending plaintext emails only.
	 * @uses Mailer->sendHTML()
	 * 
	 * @param string $messageID Optional message ID so the message can be identified in bounces etc.
	 * @return bool Success of the sending operation from an MTA perspective. 
	 * Doesn't actually give any indication if the mail has been delivered to the recipient properly)
	 */
	public function send($messageID = null) {
		Requirements::clear();
	
		$this->parseVariables();

		if(empty($this->from)) $this->from = Email::getAdminEmail();

		$this->setBounceHandlerURL( $this->bounceHandlerURL );

		$headers = $this->customHeaders;

		$headers['X-SilverStripeBounceURL'] = $this->bounceHandlerURL;

		if($messageID) $headers['X-SilverStripeMessageID'] = project() . '.' . $messageID;

		if(project()) $headers['X-SilverStripeSite'] = project();

		$to = $this->to;
		$subject = $this->subject;
		if(self::$send_all_emails_to) {
			$subject .= " [addressed to $to";
			$to = self::$send_all_emails_to;
			if($this->cc) $subject .= ", cc to $this->cc";
			if($this->bcc) $subject .= ", bcc to $this->bcc";
			$subject .= ']';
			unset($headers['Cc']);
			unset($headers['Bcc']);
		} else {
			if($this->cc) $headers['Cc'] = $this->cc;
			if($this->bcc) $headers['Bcc'] = $this->bcc;
		}

		if(self::$cc_all_emails_to) {
			if(!empty($headers['Cc']) && trim($headers['Cc'])) {
				$headers['Cc'] .= ', ' . self::$cc_all_emails_to;		
			} else {
				$headers['Cc'] = self::$cc_all_emails_to;
			}
		}
		
		if(self::$bcc_all_emails_to) {
			if(!empty($headers['Bcc']) && trim($headers['Bcc'])) {
				$headers['Bcc'] .= ', ' . self::$bcc_all_emails_to;		
			} else {
				$headers['Bcc'] = self::$bcc_all_emails_to;
			}
		}
		
		Requirements::restore();
		
		return self::mailer()->sendHTML($to, $this->from, $subject, $this->body, $this->attachments, $headers, $this->plaintext_body);
	}

	/**
	 * Used as a default sender address in the {@link Email} class
	 * unless overwritten. Also shown to users on live environments
	 * as a contact address on system error pages.
	 * 
	 * Used by {@link Email->send()}, {@link Email->sendPlain()}, {@link Debug->friendlyError()}.
	 * 
	 * @param string $newEmail
	 */
	public static function setAdminEmail($newEmail) {
		self::$admin_email_address = $newEmail;
	}
	
	/**
	 * @return string
	 */
	public static function getAdminEmail() {
		return self::$admin_email_address;
	}

	/**
	 * Send every email generated by the Email class to the given address.
	 * It will also add " [addressed to (email), cc to (email), bcc to (email)]" to the end of the subject line
	 * This can be used when testing, by putting a command like this in your _config.php file
	 * 
	 * if(!Director::isLive()) Email::send_all_emails_to("someone@example.com")
	 */
	public static function send_all_emails_to($emailAddress) {
		self::$send_all_emails_to = $emailAddress;
	}
	
	/**
	 * CC every email generated by the Email class to the given address.
	 * It won't affect the original delivery in the same way that send_all_emails_to does.	It just adds a CC header 
	 * with the given email address.	Note that you can only call this once - subsequent calls will overwrite the configuration
	 * variable.
	 *
	 * This can be used when you have a system that relies heavily on email and you want someone to be checking all correspondence.
	 * 
	 * if(Director::isLive()) Email::cc_all_emails_to("supportperson@example.com")
	 */
	public static function cc_all_emails_to($emailAddress) {
		self::$cc_all_emails_to = $emailAddress;
	}

	/**
	 * BCC every email generated by the Email class to the given address.
	 * It won't affect the original delivery in the same way that send_all_emails_to does.	It just adds a BCC header 
	 * with the given email address.	Note that you can only call this once - subsequent calls will overwrite the configuration
	 * variable.
	 *
	 * This can be used when you have a system that relies heavily on email and you want someone to be checking all correspondence.
	 * 
	 * if(Director::isLive()) Email::cc_all_emails_to("supportperson@example.com")
	 */
	public static function bcc_all_emails_to($emailAddress) {
		self::$bcc_all_emails_to = $emailAddress;
	}
	
	/**
	 * Checks for RFC822-valid email format.
	 * 
	 * @param string $str
	 * @return boolean
	 * 
	 * @copyright Cal Henderson <cal@iamcal.com> 
	 * 	This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License 
	 * 	http://creativecommons.org/licenses/by-sa/2.5/
	 */
	function is_valid_address($email){
		$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
		$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
		$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
			'\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
		$quoted_pair = '\\x5c[\\x00-\\x7f]';
		$domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
		$quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
		$domain_ref = $atom;
		$sub_domain = "($domain_ref|$domain_literal)";
		$word = "($atom|$quoted_string)";
		$domain = "$sub_domain(\\x2e$sub_domain)*";
		$local_part = "$word(\\x2e$word)*";
		$addr_spec = "$local_part\\x40$domain";

		return preg_match("!^$addr_spec$!", $email) ? 1 : 0;
	}

	/**
	 * Encode an email-address to protect it from spambots.
	 * At the moment only simple string substitutions,
	 * which are not 100% safe from email harvesting.
	 * 
	 * @todo Integrate javascript-based solution
	 * 
	 * @param string $email Email-address
	 * @param string $method Method for obfuscating/encoding the address
	 *	- 'direction': Reverse the text and then use CSS to put the text direction back to normal
	 *	- 'visible': Simple string substitution ('@' to '[at]', '.' to '[dot], '-' to [dash])
	 *	- 'hex': Hexadecimal URL-Encoding - useful for mailto: links
	 * @return string
	 */
	public static function obfuscate($email, $method = 'visible') {
		switch($method) {
			case 'direction' :
				Requirements::customCSS(
					'span.codedirection { unicode-bidi: bidi-override; direction: rtl; }',
					'codedirectionCSS'
				);
				return '<span class="codedirection">' . strrev($email) . '</span>';
			case 'visible' :
				$obfuscated = array('@' => ' [at] ', '.' => ' [dot] ', '-' => ' [dash] ');
				return strtr($email, $obfuscated);
			case 'hex' :
				$encoded = '';
				for ($x=0; $x < strlen($email); $x++) $encoded .= '&#x' . bin2hex($email{$x}).';';
				return $encoded;
			default:
				user_error('Email::obfuscate(): Unknown obfuscation method', E_USER_NOTICE);
				return $email;
		}
	}
}

/**
 * Base class that email bounce handlers extend
 * @package framework
 * @subpackage email
 */
class Email_BounceHandler extends Controller {
	
	static $allowed_actions = array( 
		'index'
	);
	
	function init() {
		BasicAuth::protect_entire_site(false);
		parent::init();
	}
	
	function index() {
		$subclasses = ClassInfo::subclassesFor( $this->class );
		unset($subclasses[$this->class]);
		
		if( $subclasses ) {	
			$subclass = array_pop( $subclasses ); 
			$task = new $subclass();
			$task->index();
			return;
		}	 
				
		// Check if access key exists
		if( !isset($_REQUEST['Key']) ) {
			echo 'Error: Access validation failed. No "Key" specified.';
			return;
		}

		// Check against access key defined in framework/_config.php
		if( $_REQUEST['Key'] != EMAIL_BOUNCEHANDLER_KEY) {
			echo 'Error: Access validation failed. Invalid "Key" specified.';
			return;
		}

		if( !$_REQUEST['Email'] ) {
			echo "No email address";
			return;		
		}
		
		$this->recordBounce( $_REQUEST['Email'], $_REQUEST['Date'], $_REQUEST['Time'], $_REQUEST['Message'] );			 
	}
		
	private function recordBounce( $email, $date = null, $time = null, $error = null ) {
		if(preg_match('/<(.*)>/', $email, $parts)) $email = $parts[1];
		
		$SQL_email = Convert::raw2sql($email);
		$SQL_bounceTime = Convert::raw2sql("$date $time");

		$duplicateBounce = DataObject::get_one("Email_BounceRecord", "\"BounceEmail\" = '$SQL_email' AND (\"BounceTime\"+INTERVAL 1 MINUTE) > '$SQL_bounceTime'");
		
		if(!$duplicateBounce) {
			$record = new Email_BounceRecord();
			
			$member = DataObject::get_one( 'Member', "\"Email\"='$SQL_email'" );
			
			if( $member ) {
				$record->MemberID = $member->ID;

				// If the SilverStripeMessageID (taken from the X-SilverStripeMessageID header embedded in the email) is sent,
				// then log this bounce in a Newsletter_SentRecipient record so it will show up on the 'Sent Status Report' tab of the Newsletter
				if( isset($_REQUEST['SilverStripeMessageID'])) {
					// Note: was sent out with: $project . '.' . $messageID;
					$message_id_parts = explode('.', $_REQUEST['SilverStripeMessageID']);
					// Note: was encoded with: base64_encode( $newsletter->ID . '_' . date( 'd-m-Y H:i:s' ) );
					$newsletter_id_date_parts = explode ('_', base64_decode($message_id_parts[1]) );
		
					// Escape just in case
					$SQL_memberID = Convert::raw2sql($member->ID);
					$SQL_newsletterID = Convert::raw2sql($newsletter_id_date_parts[0]);
					
					// Log the bounce
					$oldNewsletterSentRecipient = DataObject::get_one("Newsletter_SentRecipient", "\"MemberID\" = '$SQL_memberID' AND \"ParentID\" = '$SQL_newsletterID' AND \"Email\" = '$SQL_email'");
					
					// Update the Newsletter_SentRecipient record if it exists
					if($oldNewsletterSentRecipient) {			
						$oldNewsletterSentRecipient->Result = 'Bounced';
						$oldNewsletterSentRecipient->write();
					} else {
						// For some reason it didn't exist, create a new record
						$newNewsletterSentRecipient = new Newsletter_SentRecipient();
						$newNewsletterSentRecipient->Email = $SQL_email;
						$newNewsletterSentRecipient->MemberID = $member->ID;
						$newNewsletterSentRecipient->Result = 'Bounced';
						$newNewsletterSentRecipient->ParentID = $newsletter_id_date_parts[0];
						$newNewsletterSentRecipient->write();
					}

					// Now we are going to Blacklist this member so that email will not be sent to them in the future.
					// Note: Sending can be re-enabled by going to 'Mailing List' 'Bounced' tab and unchecking the box under 'Blacklisted'
					$member->setBlacklistedEmail(TRUE);
					echo '<p><b>Member: '.$member->FirstName.' '.$member->Surname.' <'.$member->Email.'> was added to the Email Blacklist!</b></p>';
				}
			} 
						
			if( !$date )
					$date = date( 'd-m-Y' );
			/*else
					$date = date( 'd-m-Y', strtotime( $date ) );*/
					
			if( !$time )
					$time = date( 'H:i:s' );
			/*else
					$time = date( 'H:i:s', strtotime( $time ) );*/
					
			$record->BounceEmail = $email;
			$record->BounceTime = $date . ' ' . $time;
			$record->BounceMessage = $error;
			$record->write();
			
			echo "Handled bounced email to address: $email";	
		} else {
			echo 'Sorry, this bounce report has already been logged, not logging this duplicate bounce.';
		}
	}	
		
}

/**
 * Database record for recording a bounced email
 * @package framework
 * @subpackage email
 */
class Email_BounceRecord extends DataObject {
	static $db = array(
			'BounceEmail' => 'Varchar',
			'BounceTime' => 'SS_Datetime',
			'BounceMessage' => 'Varchar'
	);
	
	static $has_one = array(
			'Member' => 'Member'
	);	 

	static $has_many = array();
	
	static $many_many = array();
	
	static $defaults = array();
	
	static $singular_name = 'Email Bounce Record';
	
	
	/** 
	* a record of Email_BounceRecord can't be created manually. Instead, it should be	
	* created though system. 
	*/ 
	public function canCreate($member = null) { 
		return false; 
	}
}


