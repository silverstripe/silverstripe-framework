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
	 * Send a plain-text email.
	 *  
	 * @param string $to
	 * @param string $from
	 * @param string §subject
	 * @param string $plainContent
	 * @param bool $attachedFiles
	 * @param array $customheaders
	 * @return bool
	 */
	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		// Not ensurely where this is supposed to be set, but defined it false for now to remove php notices
		$plainEncoding = false; 

		if ($customheaders && is_array($customheaders) == false) {
			echo "htmlEmail($to, $from, $subject, ...) could not send mail: improper \$customheaders passed:<BR>";
			dieprintr($customheaders);
		}
	
		// If the subject line contains extended characters, we must encode it
		$subject = Convert::xml2raw($subject);
		$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

		// Make the plain text part
		$headers["Content-Type"] = "text/plain; charset=utf-8";
		$headers["Content-Transfer-Encoding"] = $plainEncoding ? $plainEncoding : "quoted-printable";

		$plainContent = ($plainEncoding == "base64") 
			? chunk_split(base64_encode($plainContent),60)
			: $this->QuotedPrintable_encode($plainContent);

		// Messages with attachments are handled differently
		if($attachedFiles) {
			// The first part is the message itself
			$fullMessage = $this->processHeaders($headers, $plainContent);
			$messageParts = array($fullMessage);

			// Include any specified attachments as additional parts
			foreach($attachedFiles as $file) {
				if(isset($file['tmp_name']) && isset($file['name'])) {
					$messageParts[] = $this->encodeFileForEmail($file['tmp_name'], $file['name']);
				} else {
					$messageParts[] = $this->encodeFileForEmail($file);
				}
			}

			// We further wrap all of this into another multipart block
			list($fullBody, $headers) = $this->encodeMultipart($messageParts, "multipart/mixed");

		// Messages without attachments do not require such treatment
		} else {
			$fullBody = $plainContent;
		}

		// Email headers
		$headers["From"] 		= $this->validEmailAddr($from);

		// Messages with the X-SilverStripeMessageID header can be tracked
		if(isset($customheaders["X-SilverStripeMessageID"]) && defined('BOUNCE_EMAIL')) {		
			$bounceAddress = BOUNCE_EMAIL;
			// Get the human name from the from address, if there is one
			if(preg_match('/^([^<>]+)<([^<>])> *$/', $from, $parts))
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
		$headers = $this->processHeaders($headers);
		$to = $this->validEmailAddr($to);

		// Try it without the -f option if it fails
		if(!$result = @mail($to, $subject, $fullBody, $headers, "-f$bounceAddress"))
			$result = mail($to, $subject, $fullBody, $headers);
		
		if($result)
			return array($to,$subject,$fullBody,$headers);
			
		return false;
	}
	
	/**
	 * Sends an email as a both HTML and plaintext
	 *   
	 *   $attachedFiles should be an array of file names
	 *   - if you pass the entire $_FILES entry, the user-uploaded filename will be preserved
	 *   use $plainContent to override default plain-content generation
	 * 
	 * @return bool
	 */
	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false,
			$plainContent = false) {

		if ($customheaders && is_array($customheaders) == false) {
			echo "htmlEmail($to, $from, $subject, ...) could not send mail: improper \$customheaders passed:<BR>";
			dieprintr($customheaders);
		}

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
		$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

		// Make the plain text part
		$headers["Content-Type"] = "text/plain; charset=utf-8";
		$headers["Content-Transfer-Encoding"] = $plainEncoding ? $plainEncoding : "quoted-printable";

		$plainPart = $this->processHeaders($headers, ($plainEncoding == "base64") 
		? chunk_split(base64_encode($plainContent),60) 
			: wordwrap($this->QuotedPrintable_encode($plainContent),75));

		// Make the HTML part
		$headers["Content-Type"] = "text/html; charset=utf-8";
		
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

		$headers["Content-Transfer-Encoding"] = "quoted-printable";
		$htmlPart = $this->processHeaders($headers, wordwrap($this->QuotedPrintable_encode($htmlContent),75));
	
		list($messageBody, $messageHeaders) = $this->encodeMultipart(
			array($plainPart,$htmlPart), 
			"multipart/alternative"
		);

		// Messages with attachments are handled differently
		if($attachedFiles && is_array($attachedFiles)) {
			
			// The first part is the message itself
				$fullMessage = $this->processHeaders($messageHeaders, $messageBody);
			$messageParts = array($fullMessage);

			// Include any specified attachments as additional parts
			foreach($attachedFiles as $file) {
				if(isset($file['tmp_name']) && isset($file['name'])) {
						$messageParts[] = $this->encodeFileForEmail($file['tmp_name'], $file['name']);
				} else {
						$messageParts[] = $this->encodeFileForEmail($file);
				}
			}
				
			// We further wrap all of this into another multipart block
				list($fullBody, $headers) = $this->encodeMultipart($messageParts, "multipart/mixed");

		// Messages without attachments do not require such treatment
		} else {
			$headers = $messageHeaders;
			$fullBody = $messageBody;
		}

		// Email headers
		$headers["From"] = $this->validEmailAddr($from);

		// Messages with the X-SilverStripeMessageID header can be tracked
		if(isset($customheaders["X-SilverStripeMessageID"]) && defined('BOUNCE_EMAIL')) {
			$bounceAddress = BOUNCE_EMAIL;
		} else {
			$bounceAddress = $from;
		}

		// Strip the human name from the bounce address
		if(preg_match('/^([^<>]*)<([^<>]+)> *$/', $bounceAddress, $parts)) $bounceAddress = $parts[2];	

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
		$headers = $this->processHeaders($headers);
		$to = $this->validEmailAddr($to);
		
		// Try it without the -f option if it fails
		if(!($result = @mail($to, $subject, $fullBody, $headers, escapeshellarg("-f$bounceAddress")))) {
			$result = mail($to, $subject, $fullBody, $headers);
		}
		
		return $result;
	}

		/**
		 * @todo Make visibility protected in 3.2
	 */
	function encodeMultipart($parts, $contentType, $headers = false) {
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
		 * @todo Make visibility protected in 3.2
	 */
	function processHeaders($headers, $body = false) {
		$res = '';
		if(is_array($headers)) {
			while(list($k, $v) = each($headers)) {
				$res .= "$k: $v\n";	
			}
		}
		if($body) $res .= "\n$body";

		return $res;
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
	 * 
	 * @todo Make visibility protected in 3.2
	 */
	function encodeFileForEmail($file, $destFileName = false, $disposition = NULL, $extraHeaders = "") {
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
				$file['contents'] = $this->QuotedPrintable_encode($file['contents']);		
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
	 * @todo Make visibility protected in 3.2
	 */
	function QuotedPrintable_encode($quotprint) {
		$quotprint = (string)str_replace('\r\n',chr(13).chr(10),$quotprint);
		$quotprint = (string)str_replace('\n',  chr(13).chr(10),$quotprint);
		$quotprint = (string)preg_replace_callback("~([\x01-\x1F\x3D\x7F-\xFF])~", function($matches) {
			return sprintf('=%02X', ord($matches[1]));
		}, $quotprint);
		//$quotprint = (string)str_replace('\=0D=0A',"=0D=0A",$quotprint);
		$quotprint = (string)str_replace('=0D=0A',"\n",$quotprint);	
		$quotprint = (string)str_replace('=0A=0D',"\n",$quotprint);	
		$quotprint = (string)str_replace('=0D',"\n",$quotprint);	
		$quotprint = (string)str_replace('=0A',"\n",$quotprint);	
		return (string) $quotprint;
	}

	/**
	 * @todo Make visibility protected in 3.2
	 */
	function validEmailAddr($emailAddress) {
		$emailAddress = trim($emailAddress);
		$angBrack = strpos($emailAddress, '<');
		
		if($angBrack === 0) {
			$emailAddress = substr($emailAddress, 1, strpos($emailAddress,'>')-1);
			
		} else if($angBrack) {		
			$emailAddress = str_replace('@', '', substr($emailAddress, 0, $angBrack))
				. substr($emailAddress, $angBrack);
		}
		
		return $emailAddress;
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
		$headers["Content-Type"] = "text/html; charset=utf-8";
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
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function htmlEmail($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false,
	$plainContent = false) {

	Deprecation::notice('3.1', 'Use Email->sendHTML() instead');
	
	$mailer = Injector::inst()->create('Mailer');
	return $mailer->sendHTML($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function plaintextEmail($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false) {
	Deprecation::notice('3.1', 'Use Email->sendPlain() instead');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->sendPlain($to, $from, $subject, $plainContent, $attachedFiles, $customheaders = false);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function encodeMultipart($parts, $contentType, $headers = false) {
	Deprecation::notice('3.1', 'Use Email->$this->encodeMultipart() instead');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->encodeMultipart($parts, $contentType, $headers = false);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function wrapImagesInline($htmlContent) {
	Deprecation::notice('3.1', 'Functionality removed from core');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->wrapImagesInline($htmlContent);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function wrapImagesInline_rewriter($url) {
	Deprecation::notice('3.1', 'Functionality removed from core');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->wrapImagesInline_rewriter($url);
	
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function processHeaders($headers, $body = false) {
	Deprecation::notice('3.1', 'Set headers through Email->addCustomHeader()');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->processHeaders($headers, $url);
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function encodeFileForEmail($file, $destFileName = false, $disposition = NULL, $extraHeaders = "") {
	Deprecation::notice('3.1', 'Please add files through Email->attachFile()');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->encodeFileForEmail($file, $destFileName, $disposition, $extraHeaders);	
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function QuotedPrintable_encode($quotprint) {
	Deprecation::notice('3.1', 'No longer available, handled internally');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->QuotedPrintable_encode($quotprint);	
}

/**
 * @package framework
 * @subpackage email
 * @deprecated 3.1
 */
function validEmailAddr($emailAddress) {
	Deprecation::notice('3.1', 'Use Email->validEmailAddr() instead');

	$mailer = Injector::inst()->create('Mailer');
	return $mailer->validEmailAddr($emailAddress);	
}
