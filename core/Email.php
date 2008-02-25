<?php

/**
 * @package sapphire
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
 * @package sapphire
 * @subpackage email
 */
class Email extends ViewableData {
	protected $from, $to, $subject, $body, $plaintext_body, $cc, $bcc;
	
	/**
	 * A map of header-name -> header-value
	 */
	protected $customHeaders;

	protected $attachments = array();
	protected $parseVariables_done = false;
	
	protected $ss_template = "GenericEmail";
	protected $template_data = null;
    protected $bounceHandlerURL = null;
	
    /**
    * The default administrator email address. This will be set in the config on a site-by-site basis
    */
    static $admin_email_address = '';
	protected static $send_all_emails_to = null;
	protected static $bcc_all_emails_to = null;
	protected static $cc_all_emails_to = null;
    
	/**
	 * Create a new email.
	 */
	public function __construct($from, $to, $subject, $body = null, $bounceHandlerURL = null, $cc = null, $bcc=null ) {
		$this->from = $from;
		$this->to = $to;
		$this->subject = $subject;
		$this->body = $body;
		$this->cc = $cc;
		$this->bcc = $bcc;
        $this->setBounceHandlerURL( $bounceHandlerURL );
	}
	public function attachFileFromString($data, $filename, $mimetype = null) {
		$this->attachments[] = array(
			'contents' => $data,
			'filename' => $filename,
			'mimetype' => $mimetype,
		);
	}
    
    public function setBounceHandlerURL( $bounceHandlerURL ) {
        if( $bounceHandlerURL )
            $this->bounceHandlerURL = $bounceHandlerURL;
        else
            $this->bounceHandlerURL = $_SERVER['HTTP_HOST'] . Director::baseURL() . 'Email_BounceHandler';      
    }

	public function attachFile($filename, $attachedFilename = null, $mimetype = null) {
		$this->attachFileFromString(file_get_contents(Director::getAbsFile($filename)), $attachedFilename, $mimetype);
	}

	public function setFormat($format) {
		$this->format = $format;
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
	
	public function setSubject($val) { $this->subject = $val; }
	public function setBody($val) { $this->body = $val; }
	public function setTo($val) { $this->to = $val; }
	public function setFrom($val) { $this->from = $val; }
	public function setCc($val) {$this->cc = $val;}
	public function setBcc($val) {$this->bcc = $val;}
	/**
	 * Add a custom header to this value.
	 * Useful for implementing all those cool features that we didn't think of.
	 */
	public function addCustomHeader($headerName, $headerValue) {
		if($headerName == 'Cc') $this->cc = $headerValue;
		else if($headerName == 'Bcc') $this->bcc = $headerValue;
		else {
			if($this->customHeaders[$headerName]) $this->customHeaders[$headerName] .= ", ";
			$this->customHeaders[$headerName] .= $headerValue;
		}
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
	 * Used by SSViewer templates to detect if we're rendering an email template rather than a page template
	 */
	public function IsEmail() {
		return true;
	}
	
	/**
	 * Load all the template variables into the internal variables, including
	 * the template into body.  Called before send() or debugSend()
	 */
	protected function parseVariables() {
		if(!$this->parseVariables_done) {
			$this->parseVariables_done = true;

			// Parse $ variables in the base parameters
			$data = $this->templateData();
			
			foreach(array('from','to','subject','body', 'plaintext_body', 'cc', 'bcc') as $param) {
				$template = SSViewer::fromString($this->$param);
				$this->$param = $template->process($data);
			}
			
			// Process a .SS template file
			$fullBody = $this->body;
			if($this->ss_template) {
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
    return ereg('^([a-zA-Z0-9_+\.\-]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$', $address);
  }
  
  /**
  * @desc Send the email in plaintext
  */
  function sendPlain($messageID = null) {
    global $project;
    
    Requirements::clear();
    
    $this->parseVariables();
    
    if(empty($this->from)) $this->from = Email::getAdminEmail();
            
    $this->setBounceHandlerURL($this->bounceHandlerURL);
                
    $headers['X-SilverStripeBounceURL'] = $this->bounceHandlerURL;
            
    if($messageID) $headers['X-SilverStripeMessageID'] = $project . '.' . $messageID;
            
    if($project) $headers['X-SilverStripeSite'] = $project;

	$to = $this->to;
	$subject = $this->subject;
	if(self::$send_all_emails_to) {
		$subject .= " [addressed to $to";
		$to = self::$send_all_emails_to;
	    if($this->cc) $subject .= ", cc to $this->cc";
	    if($this->bcc) $subject .= ", bcc to $this->bcc";
		$usbject .= ']';
	} else {
	    if($this->cc) $headers["Cc"] = $this->cc;
	    if($this->bcc) $headers["Bcc"] = $this->bcc;
	}
	
	if(self::$cc_all_emails_to) {
		if(trim($headers['Cc'])) $headers['Cc'] .= ', ';
		$headers['Cc'] .= self::$cc_all_emails_to;		
	}
	if(self::$bcc_all_emails_to) {
		if(trim($headers['Bcc'])) $headers['Bcc'] .= ', ';
		$headers['Bcc'] .= self::$bcc_all_emails_to;		
	}
    
    return plaintextEmail($to, $this->from, $subject, $this->body, $this->attachments, $headers);
  }
	
	/**
	 * Send the email.
	 */
	public function send( $messageID = null ) {   	
    	Requirements::clear();
	
		$this->parseVariables();

		if( empty( $this->from ) ){
			$this->from = Email::getAdminEmail();
		}

		$this->setBounceHandlerURL( $this->bounceHandlerURL );

		$headers = $this->customHeaders;

		$headers['X-SilverStripeBounceURL'] = $this->bounceHandlerURL;

		if( $messageID ) $headers['X-SilverStripeMessageID'] = project() . '.' . $messageID;

		if( project() ) $headers['X-SilverStripeSite'] = project();

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
		    if($this->cc) $headers["Cc"] = $this->cc;
		    if($this->bcc) $headers["Bcc"] = $this->bcc;
		}

		if(self::$cc_all_emails_to) {
			if(trim($headers['Cc'])) $headers['Cc'] .= ', ';
			$headers['Cc'] .= self::$cc_all_emails_to;		
		}
		if(self::$bcc_all_emails_to) {
			if(trim($headers['Bcc'])) $headers['Bcc'] .= ', ';
			$headers['Bcc'] .= self::$bcc_all_emails_to;		
		}
       
		$result = htmlEmail($to, $this->from, $subject, $this->body, $this->attachments, $this->plaintext_body, $headers);
		
		Requirements::restore();
		
		return $result;
	}
	
	public static function setAdminEmail( $newEmail ) {
		self::$admin_email_address = $newEmail;
	}
  
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
	 * It won't affect the original delivery in the same way that send_all_emails_to does.  It just adds a CC header 
	 * with the given email address.  Note that you can only call this once - subsequent calls will overwrite the configuration
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
	 * It won't affect the original delivery in the same way that send_all_emails_to does.  It just adds a BCC header 
	 * with the given email address.  Note that you can only call this once - subsequent calls will overwrite the configuration
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
  	 * @see http://code.iamcal.com/php/rfc822/rfc822.phps
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
}

/**
 * Implements an email template that can be populated.
 * @package sapphire
 * @subpackage email
 */
class Email_Template extends Email {
	public function __construct() {
	}

	public function BaseURL() {
		return Director::absoluteBaseURL();
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
}


// TO DO: Clean this code up, make it more OO.
// For now, we've just put a clean interface around this dirty code :)

/*
 * Sends an email as a both HTML and plaintext
 *   $attachedFiles should be an array of file names
 *    - if you pass the entire $_FILES entry, the user-uploaded filename will be preserved
 *   use $plainContent to override default plain-content generation
 */
function htmlEmail($to, $from, $subject, $htmlContent, $attachedFiles = false, $plainContent = false, $customheaders = false, $inlineImages = false) {
	
	if ($customheaders && is_array($customheaders) == false) {
		echo "htmlEmail($to, $from, $subject, ...) could not send mail: improper \$customheaders passed:<BR>";
		dieprintr($headers);
	}

    
	$subjectIsUnicode = (strpos($subject,"&#") !== false);
	$bodyIsUnicode = (strpos($htmlContent,"&#") !== false);
    $plainEncoding = "";
	
	// We generate plaintext content by default, but you can pass custom stuff
	$plainEncoding = '';
	if(!$plainContent) {
		$plainContent = Convert::xml2raw($htmlContent);
		if(isset($bodyIsUnicode) && $bodyIsUnicode) $plainEncoding = "base64";
	}


	// If the subject line contains extended characters, we must encode the 
	$subject = Convert::xml2raw($subject);
	if(isset($subjectIsUnicode) && $subjectIsUnicode)
		$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";


	// Make the plain text part
	$headers["Content-Type"] = "text/plain; charset=\"utf-8\"";
	$headers["Content-Transfer-Encoding"] = $plainEncoding ? $plainEncoding : "quoted-printable";

	$plainPart = processHeaders($headers, ($plainEncoding == "base64") ? chunk_split(base64_encode($plainContent),60) : wordwrap($plainContent,120));

	// Make the HTML part
	$headers["Content-Type"] = "text/html; charset=\"utf-8\"";
        
	
	// Add basic wrapper tags if the body tag hasn't been given
	if(stripos($htmlContent, '<body') === false) {
		$htmlContent =
			"<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n" .
			"<HTML><HEAD>\n" .
			"<META http-equiv=Content-Type content=\"text/html; charset=utf-8\">\n" .
			"<STYLE type=3Dtext/css></STYLE>\n\n".
			"</HEAD>\n" .
			"<BODY bgColor=#ffffff>\n" .
				$htmlContent .
			"\n</BODY>\n" .
			"</HTML>";
	}

	if($inlineImages) {
		$htmlPart = wrapImagesInline($htmlContent);
	} else {
		$headers["Content-Transfer-Encoding"] = "quoted-printable";
		$htmlPart = processHeaders($headers, wordwrap(QuotedPrintable_encode($htmlContent),120));
	}
	
	list($messageBody, $messageHeaders) = encodeMultipart(array($plainPart,$htmlPart), "multipart/alternative");

	// Messages with attachments are handled differently
	if($attachedFiles && is_array($attachedFiles)) {
		
		// The first part is the message itself
		$fullMessage = processHeaders($messageHeaders, $messageBody);
		$messageParts = array($fullMessage);

		// Include any specified attachments as additional parts
		foreach($attachedFiles as $file) {
			if($file['tmp_name'] && $file['name']) {
				$messageParts[] = encodeFileForEmail($file['tmp_name'], $file['name']);
			} else {
				$messageParts[] = encodeFileForEmail($file);
			}
		}
			
		// We further wrap all of this into another multipart block
		list($fullBody, $headers) = encodeMultipart($messageParts, "multipart/mixed");

	// Messages without attachments do not require such treatment
	} else {
		$headers = $messageHeaders;
		$fullBody = $messageBody;
	}

	// Email headers
	$headers["From"] 		= validEmailAddr($from);

	// Messages with the X-SilverStripeMessageID header can be tracked
	if(isset($customheaders["X-SilverStripeMessageID"]) && defined('BOUNCE_EMAIL')) {
		$bounceAddress = BOUNCE_EMAIL;
		// Get the human name from the from address, if there is one
		if(ereg('^([^<>]+)<([^<>])> *$', $from, $parts))
			$bounceAddress = "$parts[1]<$bounceAddress>";
	} else {
		$bounceAddress = $from;
	}
	
	// $headers["Sender"] 		= $from;
	$headers["X-Mailer"]	= X_MAILER;
	if (!isset($customheaders["X-Priority"])) $headers["X-Priority"]	= 3;
	
	$headers = array_merge((array)$headers, (array)$customheaders);

	// the carbon copy header has to be 'Cc', not 'CC' or 'cc' -- ensure this.
	if (isset($headers['CC'])) { $headers['Cc'] = $headers['CC']; unset($headers['CC']); }
	if (isset($headers['cc'])) { $headers['Cc'] = $headers['cc']; unset($headers['cc']); }
	
	// the carbon copy header has to be 'Bcc', not 'BCC' or 'bcc' -- ensure this.
	if (isset($headers['BCC'])) {$headers['Bcc']=$headers['BCC']; unset($headers['BCC']); }
	if (isset($headers['bcc'])) {$headers['Bcc']=$headers['bcc']; unset($headers['bcc']); }
		
	
	// Send the email
	$headers = processHeaders($headers);
	$to = validEmailAddr($to);
	
	// Try it without the -f option if it fails
	if(!($result = @mail($to, $subject, $fullBody, $headers, "-f$bounceAddress"))) {
		$result = mail($to, $subject, $fullBody, $headers);
	}
	
	return $result;
}

/*
 * Send a plain text e-mail
 */
function plaintextEmail($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false) {
	$subjectIsUnicode = false;	
	$plainEncoding = false; // Not ensurely where this is supposed to be set, but defined it false for now to remove php notices

	if ($customheaders && is_array($customheaders) == false) {
		echo "htmlEmail($to, $from, $subject, ...) could not send mail: improper \$customheaders passed:<BR>";
		dieprintr($headers);
	}

	if(strpos($subject,"&#") !== false) $subjectIsUnicode = true;

	// If the subject line contains extended characters, we must encode it
	$subject = Convert::xml2raw($subject);
	if($subjectIsUnicode)
		$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";


	// Make the plain text part
	$headers["Content-Type"] = "text/plain; charset=\"utf-8\"";
	$headers["Content-Transfer-Encoding"] = $plainEncoding ? $plainEncoding : "quoted-printable";

	$plainContent = ($plainEncoding == "base64") ? chunk_split(base64_encode($plainContent),60) : QuotedPrintable_encode($plainContent);

	// Messages with attachments are handled differently
	if(is_array($attachedFiles)) {
		// The first part is the message itself
		$fullMessage = processHeaders($headers, $plainContent);
		$messageParts = array($fullMessage);

		// Include any specified attachments as additional parts
		foreach($attachedFiles as $file) {
			if($file['tmp_name'] && $file['name']) {
				$messageParts[] = encodeFileForEmail($file['tmp_name'], $file['name']);
			} else {
				$messageParts[] = encodeFileForEmail($file);
			}
		}
		

		// We further wrap all of this into another multipart block
		list($fullBody, $headers) = encodeMultipart($messageParts, "multipart/mixed");

	// Messages without attachments do not require such treatment
	} else {
		$fullBody = $plainContent;
	}

	// Email headers
	$headers["From"] 		= validEmailAddr($from);

	// Messages with the X-SilverStripeMessageID header can be tracked
	if(isset($customheaders["X-SilverStripeMessageID"]) && defined('BOUNCE_EMAIL')) {		
		$bounceAddress = BOUNCE_EMAIL;
		// Get the human name from the from address, if there is one
		if(ereg('^([^<>]+)<([^<>])> *$', $from, $parts))
			$bounceAddress = "$parts[1]<$bounceAddress>";
	} else {
		$bounceAddress = $from;
	}
	
	// $headers["Sender"] 		= $from;
	$headers["X-Mailer"]	= X_MAILER;
	if(!isset($customheaders["X-Priority"])) {
		$headers["X-Priority"]	= 3;
	}
	
	$headers = array_merge((array)$headers, (array)$customheaders);

	// the carbon copy header has to be 'Cc', not 'CC' or 'cc' -- ensure this.
	if (isset($headers['CC'])) { $headers['Cc'] = $headers['CC']; unset($headers['CC']); }
	if (isset($headers['cc'])) { $headers['Cc'] = $headers['cc']; unset($headers['cc']); }
		
	// Send the email
	$headers = processHeaders($headers);
	$to = validEmailAddr($to);

	// Try it without the -f option if it fails
	if(!$result = @mail($to, $subject, $fullBody, $headers, "-f$bounceAddress"))
		$result = mail($to, $subject, $fullBody, $headers);
	
	if($result)
		return array($to,$subject,$fullBody,$headers);
		
	return false;
}


function encodeMultipart($parts, $contentType, $headers = false) {
	$separator = "----=_NextPart_" . ereg_replace('[^0-9]','',rand() * 10000000000);


	$headers["MIME-Version"] = "1.0";
	$headers["Content-Type"] = "$contentType; boundary=\"$separator\"";
	$headers["Content-Transfer-Encoding"] = "7bit";

	if($contentType == "multipart/alternative") {
		//$baseMessage = "This is an encoded HTML message.  There are two parts: a plain text and an HTML message, open whatever suits you better.";
		$baseMessage = "\nThis is a multi-part message in MIME format.";
	} else {
		//$baseMessage = "This is a message containing attachments.  The e-mail body is contained in the first attachment";
		$baseMessage = "\nThis is a multi-part message in MIME format.";
	}


	$separator = "\n--$separator\n";
	$body = "$baseMessage\n" .
		$separator . implode("\n".$separator, $parts) . "\n" . trim($separator) . "--";

	return array($body, $headers);
}

/*
 * Return a multipart/related e-mail chunk for the given HTML message and its linked images
 * Decodes absolute URLs, accessing the appropriate local images
 */
function wrapImagesInline($htmlContent) {
	global $_INLINED_IMAGES;
	$_INLINED_IMAGES = null;
	
	$replacedContent = imageRewriter($htmlContent, 'wrapImagesInline_rewriter($URL)');
	
	
	// Make the HTML part
	$headers["Content-Type"] = "text/html; charset=\"utf-8\"";
	$headers["Content-Transfer-Encoding"] = "quoted-printable";
	$multiparts[] = processHeaders($headers, QuotedPrintable_encode($replacedContent));
	
	// Make all the image parts		
	global $_INLINED_IMAGES;
	foreach($_INLINED_IMAGES as $url => $cid) {
		$multiparts[] = encodeFileForEmail($url, false, "inline", "Content-ID: <$cid>\n");		
	}

	// Merge together in a multipart
	list($body, $headers) = encodeMultipart($multiparts, "multipart/related");
	return processHeaders($headers, $body);
}
function wrapImagesInline_rewriter($url) {
	$url = relativiseURL($url);
	
	global $_INLINED_IMAGES;
	if(!$_INLINED_IMAGES[$url]) {
		$identifier = "automatedmessage." . rand(1000,1000000000) . "@silverstripe.com";
		$_INLINED_IMAGES[$url] = $identifier;
	}
	return "cid:" . $_INLINED_IMAGES[$url];
	
}

/*
 * Combine headers w/ the body into a single string
 */
function processHeaders($headers, $body = false) {
	$res = '';
	if(is_array($headers)) while(list($k, $v) = each($headers))
		$res .= "$k: $v\n";
	if($body) $res .= "\n$body";
	return $res;
}

/*
 * Encode the contents of a file for emailing, including headers
 */
function encodeFileForEmail($file, $destFileName = false, $disposition = "attachment", $extraHeaders = "") {	
	if(!$file) {
		user_error("encodeFileForEmail: not passed a filename and/or data", E_USER_WARNING);
		return;
	}
	
	if (is_string($file)) {
		$file = array('filename' => $file);
		$fh = fopen($file['filename'], "rb");
		if ($fh) {
			while(!feof($fh)) $file['contents'] .= fread($fh, 10000);	
			fclose($fh);
		}
	}

	// Build headers, including content type
	if(!$destFileName) $base = basename($file['filename']);
	else $base = $destFileName;

	$mimeType = $file['mimetype'] ? $file['mimetype'] : getMimeType($file['filename']);
	if(!$mimeType) $mimeType = "application/unknown";
		
	// Encode for emailing
	if (substr($file['mimetype'], 0, 4) != 'text') {
		$encoding = "base64";
		$file['contents'] = chunk_split(base64_encode($file['contents']));
	} else {
		// This mime type is needed, otherwise some clients will show it as an inline attachment
		$mimeType = 'application/octet-stream';
		$encoding = "quoted-printable";		
		$file['contents'] = QuotedPrintable_encode($file['contents']);		
	}

	$headers = "Content-type: $mimeType;\n\tname=\"$base\"\n".
						 "Content-Transfer-Encoding: $encoding\n".
						 "Content-Disposition: $disposition;\n\tfilename=\"$base\"\n" . $extraHeaders . "\n";

	// Return completed packet
	return $headers . $file['contents'];
}

function QuotedPrintable_encode($quotprint) {		
		$quotprint = (string) str_replace('\r\n',chr(13).chr(10),$quotprint);
		$quotprint = (string) str_replace('\n',  chr(13).chr(10),$quotprint);
		$quotprint = (string) preg_replace("~([\x01-\x1F\x3D\x7F-\xFF])~e", "sprintf('=%02X', ord('\\1'))", $quotprint);
		//$quotprint = (string) str_replace('\=0D=0A',"=0D=0A",$quotprint);
		$quotprint = (string) str_replace('=0D=0A',"\n",$quotprint);	
		$quotprint = (string) str_replace('=0A=0D',"\n",$quotprint);	
		$quotprint = (string) str_replace('=0D',"\n",$quotprint);	
		$quotprint = (string) str_replace('=0A',"\n",$quotprint);	
		return (string) $quotprint;
}

function validEmailAddr($emailAddress) {
	$emailAddress = trim($emailAddress);
	$angBrack = strpos($emailAddress, '<');
	
	if($angBrack === 0) {
		$emailAddress = substr($emailAddress, 1, strpos($emailAddress,'>')-1);
		
	} else if($angBrack) {		
		$emailAddress = str_replace('@', '', substr($emailAddress, 0, $angBrack))
							.substr($emailAddress, $angBrack);
	}
	
	return $emailAddress;
}

/*
 * Get mime type based on extension
 */
function getMimeType($filename) {
	global $global_mimetypes;
	if(!$global_mimetypes) loadMimeTypes();
	$ext = strtolower(substr($filename,strrpos($filename,'.')+1));
	return $global_mimetypes[$ext];
}

/*
 * Load the mime-type data from the system file
 */
function loadMimeTypes() {
	$mimetypePathCustom = '/etc/mime.types';
	$mimetypePathGeneric = Director::baseFolder() . '/sapphire/email/mime.types';
	$mimeTypes = file_exists($mimetypePathGeneric) ?  file($mimetypePathGeneric) : file($mimetypePathCustom);
	foreach($mimeTypes as $typeSpec) {
		if(($typeSpec = trim($typeSpec)) && substr($typeSpec,0,1) != "#") {
			$parts = split("[ \t\r\n]+", $typeSpec);
			if(sizeof($parts) > 1) {
				$mimeType = array_shift($parts);
				foreach($parts as $ext) {
					$ext = strtolower($ext);
					$mimeData[$ext] = $mimeType;
				}
			}
		}
	}

	global $global_mimetypes;
	$global_mimetypes = $mimeData;
	return $mimeData;
}

/**
 * Base class that email bounce handlers extend
 * @package sapphire
 * @subpackage email
 */
class Email_BounceHandler extends Controller {
	
	function init() {
		BasicAuth::disable();
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

	// Check against access key defined in sapphire/_config.php
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
	  	if(ereg('<(.*)>', $email, $parts)) $email = $parts[1];
	  	
	  	$SQL_email = Convert::raw2sql($email);
	  	$SQL_bounceTime = Convert::raw2sql("$date $time");

    	$duplicateBounce = DataObject::get_one("Email_BounceRecord", "BounceEmail = '$SQL_email' AND (BounceTime+INTERVAL 1 MINUTE) > '$SQL_bounceTime'");
    	
    	if(!$duplicateBounce) {
        $record = new Email_BounceRecord();
        
        $member = DataObject::get_one( 'Member', "`Email`='$SQL_email'" );
        
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
			$oldNewsletterSentRecipient = DataObject::get_one("Newsletter_SentRecipient", "MemberID = '$SQL_memberID' AND ParentID = '$SQL_newsletterID' AND Email = '$SQL_email'");
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
 * @package sapphire
 * @subpackage email
 */
class Email_BounceRecord extends DataObject {
    static $db = array(
        'BounceEmail' => 'Varchar',
        'BounceTime' => 'Datetime',
        'BounceMessage' => 'Varchar'
    );
    
    static $has_one = array(
        'Member' => 'Member'
    );   
}

/**
 * This class is responsible for ensuring that members who are on it receive NO email 
 * communication at all. any correspondance is caught before the email is sent.
 * @package sapphire
 * @subpackage email
 */
class Email_BlackList extends DataObject{
	 static $db = array(
        'BlockedEmail' => 'Varchar',  
    );
     static $has_one = array(
        'Member' => 'Member'
    );
    
    /**
     * Helper function to see if the email being
     * sent has specifically been blocked.
     */
    static function isBlocked($email){
    	$blockedEmails = DataObject::get("Email_BlackList")->toDropDownMap("ID","BlockedEmail");
    	if($blockedEmails){
	    	if(in_array($email,$blockedEmails)){
	    		return true;
	    	}else{
	    		return false;
	    	}
    	}else{
    		return false;
    	}
    }
}

?>
