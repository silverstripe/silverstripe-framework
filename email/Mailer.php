<?php

/**
 * Mailer objects are responsible for actually sending emails.
 * The default Mailer class will use PHP's mail() function.
 *
 * @package framework
 * @subpackage email
 */
class Mailer extends Object {

	/**
	 * Default encoding type for messages. Available options are:
	 * - quoted-printable
	 * - base64
	 *
	 * @var string
	 * @config
	 */
	private static $default_message_encoding = 'quoted-printable';

	/**
	 * Encoding type currently set
	 *
	 * @var string
	 */
	protected $messageEncoding = null;

	/**
	 * Email used for bounces
	 *
	 * @var string
	 * @config
	 */
	private static $default_bounce_email = null;

	/**
	 * Email used for bounces
	 *
	 * @var string
	 */
	protected $bounceEmail = null;

	/**
	 * Email used for bounces
	 *
	 * @return string
	 */
	public function getBounceEmail() {
		return $this->bounceEmail
			?: (defined('BOUNCE_EMAIL') ? BOUNCE_EMAIL : null)
			?: self::config()->default_bounce_email;
	}

	/**
	 * Set the email used for bounces
	 *
	 * @param string $email
	 */
	public function setBounceEmail($email) {
		$this->bounceEmail = $email;
	}

	/**
	 * Get the encoding type used for plain text messages
	 *
	 * @return string
	 */
	public function getMessageEncoding() {
		return $this->messageEncoding ?: static::config()->default_message_encoding;
	}

	/**
	 * Sets encoding type for messages. Available options are:
	 * - quoted-printable
	 * - base64
	 *
	 * @param string $encoding
	 */
	public function setMessageEncoding($encoding) {
		$this->messageEncoding = $encoding;
	}

	/**
	 * Encode a message using the given encoding mechanism
	 *
	 * @param string $message
	 * @param string $encoding
	 * @return string Encoded $message
	 */
	protected function encodeMessage($message, $encoding) {
		switch($encoding) {
			case 'base64':
				return chunk_split(base64_encode($message), 60);
			case 'quoted-printable':
				return quoted_printable_encode($message);
			default:
				return $message;
		}
	}

	/**
	 * Merge custom headers with default ones
	 *
	 * @param array $headers Default headers
	 * @param array $customHeaders Custom headers
	 * @return array Resulting message headers
	 */
	protected function mergeCustomHeaders($headers, $customHeaders) {
		$headers["X-Mailer"] = X_MAILER;
		if(!isset($customHeaders["X-Priority"])) {
			$headers["X-Priority"]	= 3;
		}

		// Merge!
		$headers = array_merge($headers, $customHeaders);

		// Headers 'Cc' and 'Bcc' need to have the correct case
		foreach(array('Bcc', 'Cc') as $correctKey) {
			foreach($headers as $key => $value) {
				if(strcmp($key, $correctKey) !== 0 && strcasecmp($key, $correctKey) === 0) {
					$headers[$correctKey] = $value;
					unset($headers[$key]);
				}
			}
		}

		return $headers;
	}

	/**
	 * Send a plain-text email.
	 *
	 * @param string $to Email recipient
	 * @param string $from Email from
	 * @param string $subject Subject text
	 * @param string $plainContent Plain text content
	 * @param array $attachedFiles List of attached files
	 * @param array $customHeaders List of custom headers
	 * @return mixed Return false if failure, or list of arguments if success
	 */
	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = array(), $customHeaders = array()) {
		// Prepare plain text body
		$fullBody = $this->encodeMessage($plainContent, $this->getMessageEncoding());
		$headers["Content-Type"] = "text/plain; charset=utf-8";
		$headers["Content-Transfer-Encoding"] = $this->getMessageEncoding();

		// Send prepared message
		return $this->sendPreparedMessage($to, $from, $subject, $attachedFiles, $customHeaders, $fullBody, $headers);
	}


	/**
	 * Sends an email as a both HTML and plaintext
	 *
	 * @param string $to Email recipient
	 * @param string $from Email from
	 * @param string $subject Subject text
	 * @param string $htmlContent HTML Content
	 * @param array $attachedFiles List of attachments
	 * @param array $customHeaders User specified headers
	 * @param string $plainContent Plain text content. If omitted, will be generated from $htmlContent
	 * @return mixed Return false if failure, or list of arguments if success
	 */
	public function sendHTML($to, $from, $subject, $htmlContent,
		$attachedFiles = array(), $customHeaders = array(), $plainContent = ''
	) {
		// Prepare both Plain and HTML components and merge
		$plainPart = $this->preparePlainSubmessage($plainContent, $htmlContent);
		$htmlPart = $this->prepareHTMLSubmessage($htmlContent);
		list($fullBody, $headers) = $this->encodeMultipart(
			array($plainPart, $htmlPart),
			"multipart/alternative"
		);

		// Send prepared message
		return $this->sendPreparedMessage($to, $from, $subject, $attachedFiles, $customHeaders, $fullBody, $headers);
	}

	/**
	 * Send an email of an arbitrary format
	 *
	 * @param string $to To
	 * @param string $from From
	 * @param string $subject Subject
	 * @param array $attachedFiles List of attachments
	 * @param array $customHeaders User specified headers
	 * @param string $fullBody Prepared message
	 * @param array $headers Prepared headers
	 * @return mixed Return false if failure, or list of arguments if success
	 */
	protected function sendPreparedMessage($to, $from, $subject, $attachedFiles, $customHeaders, $fullBody, $headers) {
		// If the subject line contains extended characters, we must encode the
		$subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";
		$to = $this->validEmailAddress($to);
		$from = $this->validEmailAddress($from);

		// Messages with attachments are handled differently
		if($attachedFiles) {
			list($fullBody, $headers) = $this->encodeAttachments($attachedFiles, $headers, $fullBody);
		}

		// Get bounce email
		$bounceAddress = $this->getBounceEmail() ?: $from;
		if(preg_match('/^([^<>]*)<([^<>]+)> *$/', $bounceAddress, $parts)) $bounceAddress = $parts[2];

		// Get headers
		$headers["From"] = $from;
		$headers = $this->mergeCustomHeaders($headers, $customHeaders);
		$headersEncoded = $this->processHeaders($headers);

		return $this->email($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress);
	}

	/**
	 * Send the actual email
	 *
	 * @param string $to
	 * @param string $subjectEncoded
	 * @param string $fullBody
	 * @param string $headersEncoded
	 * @param string $bounceAddress
	 * @return mixed Return false if failure, or list of arguments if success
	 */
	protected function email($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress) {
		// Try it without the -f option if it fails
		$result = @mail($to, $subjectEncoded, $fullBody, $headersEncoded, escapeshellarg("-f$bounceAddress"));
		if(!$result) {
			$result = mail($to, $subjectEncoded, $fullBody, $headersEncoded);
		}

		if($result) {
			return array($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress);
		}

		return false;
	}

	/**
	 * Encode attachments into a message
	 *
	 * @param array $attachments
	 * @param array $headers
	 * @param string $body
	 * @return array Array containing completed body followed by headers
	 */
	protected function encodeAttachments($attachments, $headers, $body) {
		// The first part is the message itself
		$fullMessage = $this->processHeaders($headers, $body);
		$messageParts = array($fullMessage);

		// Include any specified attachments as additional parts
		foreach($attachments as $file) {
			if(isset($file['tmp_name']) && isset($file['name'])) {
				$messageParts[] = $this->encodeFileForEmail($file['tmp_name'], $file['name']);
			} else {
				$messageParts[] = $this->encodeFileForEmail($file);
			}
		}

		// We further wrap all of this into another multipart block
		return $this->encodeMultipart($messageParts, "multipart/mixed");
	}

	/**
	 * Generate the plainPart of a html message
	 *
	 * @param string $plainContent Plain body
	 * @param string $htmlContent HTML message
	 * @return string Encoded headers / message in a single block
	 */
	protected function preparePlainSubmessage($plainContent, $htmlContent) {
		$plainEncoding = $this->getMessageEncoding();

		// Generate plain text version if not explicitly given
		if(!$plainContent) $plainContent = Convert::xml2raw($htmlContent);

		// Make the plain text part
		$headers["Content-Type"] = "text/plain; charset=utf-8";
		$headers["Content-Transfer-Encoding"] = $plainEncoding;
		$plainContentEncoded = $this->encodeMessage($plainContent, $plainEncoding);

		// Merge with headers
		return $this->processHeaders($headers, $plainContentEncoded);
	}

	/**
	 * Generate the html part of a html message
	 *
	 * @param string $htmlContent HTML message
	 * @return string Encoded headers / message in a single block
	 */
	protected function prepareHTMLSubmessage($htmlContent) {
		// Add basic wrapper tags if the body tag hasn't been given
		if(stripos($htmlContent, '<body') === false) {
			$htmlContent =
				"<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n" .
				"<HTML><HEAD>\n" .
				"<META http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n" .
				"<STYLE type=\"text/css\"></STYLE>\n\n".
				"</HEAD>\n" .
				"<BODY bgColor=\"#ffffff\">\n" .
					$htmlContent .
				"\n</BODY>\n" .
				"</HTML>";
		}

		// Make the HTML part
		$headers["Content-Type"] = "text/html; charset=utf-8";
		$headers["Content-Transfer-Encoding"] = $this->getMessageEncoding();
		$htmlContentEncoded = $this->encodeMessage($htmlContent, $this->getMessageEncoding());

		// Merge with headers
		return $this->processHeaders($headers, $htmlContentEncoded);
	}

	/**
	 * Encode an array of parts using multipart
	 *
	 * @param array $parts List of parts
	 * @param string $contentType Content-type of parts
	 * @param array $headers Existing headers to include in response
	 * @return array Array with two items, the body followed by headers
	 */
	protected function encodeMultipart($parts, $contentType, $headers = array()) {
		$separator = "----=_NextPart_" . preg_replace('/[^0-9]/', '', rand() * 10000000000);

		$headers["MIME-Version"] = "1.0";
		$headers["Content-Type"] = "$contentType; boundary=\"$separator\"";
		$headers["Content-Transfer-Encoding"] = "7bit";

		if($contentType == "multipart/alternative") {
			// $baseMessage = "This is an encoded HTML message.  There are two parts: a plain text and an HTML message,
			// open whatever suits you better.";
			$baseMessage = "\nThis is a multi-part message in MIME format.";
		} else {
			// $baseMessage = "This is a message containing attachments.  The e-mail body is contained in the first
			// attachment";
			$baseMessage = "\nThis is a multi-part message in MIME format.";
		}

		$separator = "\n--$separator\n";
		$body = "$baseMessage\n" .
			$separator . implode("\n".$separator, $parts) . "\n" . trim($separator) . "--";

		return array($body, $headers);
	}


	/**
	 * Add headers to the start of the message
	 *
	 * @param array $headers
	 * @param string $body
	 * @return string Resulting message body
	 */
	protected function processHeaders($headers, $body = '') {
		$result = '';
		foreach($headers as $key => $value) {
			$result .= "$key: $value\n";
		}
		if($body) $result .= "\n$body";

		return $result;
	}

	/**
	 * Encode the contents of a file for emailing, including headers
	 *
	 * $file can be an array, in which case it expects these members:
	 *   'filename'        - the filename of the file
	 *   'contents'        - the raw binary contents of the file as a string
	 *  and can optionally include these members:
	 *   'mimetype'        - the mimetype of the file (calculated from filename if missing)
	 *   'contentLocation' - the 'Content-Location' header value for the file
	 *
	 * $file can also be a string, in which case it is assumed to be the filename
	 *
	 * h5. contentLocation
	 *
	 * Content Location is one of the two methods allowed for embedding images into an html email.
	 * It's also the simplest, and best supported.
	 *
	 * Assume we have an email with this in the body:
	 *
	 *   <img src="http://example.com/image.gif" />
	 *
	 * To display the image, an email viewer would have to download the image from the web every time
	 * it is displayed. Due to privacy issues, most viewers will not display any images unless
	 * the user clicks 'Show images in this email'. Not optimal.
	 *
	 * However, we can also include a copy of this image as an attached file in the email.
	 * By giving it a contentLocation of "http://example.com/image.gif" most email viewers
	 * will use this attached copy instead of downloading it. Better,
	 * most viewers will show it without a 'Show images in this email' conformation.
	 *
	 * Here is an example of passing this information through Email.php:
	 *
	 *   $email = new Email();
	 *   $email->attachments[] = array(
	 *     'filename' => BASE_PATH . "/themes/mytheme/images/header.gif",
	 *     'contents' => file_get_contents(BASE_PATH . "/themes/mytheme/images/header.gif"),
	 *     'mimetype' => 'image/gif',
	 *     'contentLocation' => Director::absoluteBaseURL() . "/themes/mytheme/images/header.gif"
	 *   );
	 */
	protected function encodeFileForEmail($file, $destFileName = false, $disposition = NULL, $extraHeaders = "") {
		if(!$file) {
			user_error("encodeFileForEmail: not passed a filename and/or data", E_USER_WARNING);
			return;
		}

		if (is_string($file)) {
			$file = array('filename' => $file);
			$fh = fopen($file['filename'], "rb");
			if ($fh) {
				$file['contents'] = "";
				while(!feof($fh)) $file['contents'] .= fread($fh, 10000);
				fclose($fh);
			}
		}

		// Build headers, including content type
		if(!$destFileName) $base = basename($file['filename']);
		else $base = $destFileName;

		$mimeType = !empty($file['mimetype']) ? $file['mimetype'] : HTTP::get_mime_type($file['filename']);
		if(!$mimeType) $mimeType = "application/unknown";
		if (empty($disposition)) $disposition = isset($file['contentLocation']) ? 'inline' : 'attachment';

		// Encode for emailing
		if (substr($mimeType, 0, 4) != 'text') {
			$encoding = "base64";
			$file['contents'] = chunk_split(base64_encode($file['contents']));
		} else {
			// This mime type is needed, otherwise some clients will show it as an inline attachment
			$mimeType = 'application/octet-stream';
			$encoding = "quoted-printable";
			$file['contents'] = quoted_printable_encode($file['contents']);
		}

		$headers =	"Content-type: $mimeType;\n\tname=\"$base\"\n".
					"Content-Transfer-Encoding: $encoding\n".
					"Content-Disposition: $disposition;\n\tfilename=\"$base\"\n";

		if ( isset($file['contentLocation']) ) $headers .= 'Content-Location: ' . $file['contentLocation'] . "\n" ;

		$headers .= $extraHeaders . "\n";

		// Return completed packet
		return $headers . $file['contents'];
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function validEmailAddr($emailAddress) {
		Deprecation::notice('4.0', 'This method will be removed in 4.0. Use protected method Mailer->validEmailAddress().');
		return $this->validEmailAddress($emailAddress);
	}

	/**
	 * Cleans up emails which may be in 'Name <email@silverstripe.com>' format
	 *
	 * @param string $emailAddress
	 * @return string
	 */
	protected function validEmailAddress($emailAddress) {
		$emailAddress = trim($emailAddress);
		$openBracket = strpos($emailAddress, '<');
		$closeBracket = strpos($emailAddress, '>');

		// Unwrap email contained by braces
		if($openBracket === 0 && $closeBracket !== false) {
			return substr($emailAddress, 1, $closeBracket - 1);
		}

		// Ensure name component cannot be mistaken for an email address
		if($openBracket) {
			$emailAddress = str_replace('@', '', substr($emailAddress, 0, $openBracket))
				. substr($emailAddress, $openBracket);
		}

		return $emailAddress;
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function wrapImagesInline($htmlContent) {
		Deprecation::notice('4.0', 'wrapImagesInline is deprecated');
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function wrapImagesInline_rewriter($url) {
		Deprecation::notice('4.0', 'wrapImagesInline_rewriter is deprecated');
	}
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function htmlEmail($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false,
	$plainContent = false) {

	Deprecation::notice('4.0', 'Use Email->sendHTML() instead');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->sendHTML($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function plaintextEmail($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false) {
	Deprecation::notice('4.0', 'Use Email->sendPlain() instead');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->sendPlain($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function encodeMultipart($parts, $contentType, $headers = false) {
	Deprecation::notice('4.0', 'Use Email->$this->encodeMultipart() instead');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->encodeMultipart($parts, $contentType, $headers = false);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function wrapImagesInline($htmlContent) {
	Deprecation::notice('4.0', 'Functionality removed from core');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->wrapImagesInline($htmlContent);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function wrapImagesInline_rewriter($url) {
	Deprecation::notice('4.0', 'Functionality removed from core');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->wrapImagesInline_rewriter($url);

}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function processHeaders($headers, $body = false) {
	Deprecation::notice('4.0', 'Set headers through Email->addCustomHeader()');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->processHeaders($headers, $url);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function encodeFileForEmail($file, $destFileName = false, $disposition = NULL, $extraHeaders = "") {
	Deprecation::notice('4.0', 'Please add files through Email->attachFile()');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->encodeFileForEmail($file, $destFileName, $disposition, $extraHeaders);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function QuotedPrintable_encode($quotprint) {
	Deprecation::notice('4.0', 'No longer available, handled internally');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->QuotedPrintable_encode($quotprint);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function validEmailAddr($emailAddress) {
	Deprecation::notice('4.0', 'This method will be removed in 4.0. Use protected method Mailer->validEmailAddress().');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->validEmailAddr($emailAddress);
}
