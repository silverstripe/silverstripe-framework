<?php
/**
 * @package sapphire
 * @subpackage control
 */

/**
 * The content negotiator performs text/html or application/xhtml+xml switching.
 * It does this through the static function ContentNegotiator::process()
 * @package sapphire
 * @subpackage control
 */
class ContentNegotiator {
	protected static $encoding = 'utf-8';
	
	/**
	 * Set the character set encoding for this page.  By default it's utf-8, but you could change it to, say, windows-1252, to
	 * improve interoperability with extended characters being imported from windows excel.
	 */
	static function set_encoding($encoding) {
		self::$encoding = $encoding;
	}
	/**
	 * Return the character encoding set bhy ContentNegotiator::set_encoding().  It's recommended that all classes that need to
	 * specify the character set make use of this function.
	 */
	static function get_encoding() {
	    return self::$encoding;
	}
	
	
	static function process(HTTPResponse $response) {
		if(self::$disabled) return;

		$mimes = array(
			"xhtml" => "application/xhtml+xml",
			"html" => "text/html",
		);
		$q = array();
		if(headers_sent()) {
			$chosenFormat = "html";

		} else if(isset($_GET['forceFormat'])) {
			$chosenFormat = $_GET['forceFormat'];

		} else {
			// The W3C validator doesn't send an HTTP_ACCEPT header, but it can support xhtml.  We put this special case in here so that
			// designers don't get worried that their templates are HTML4.
 			if(isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 14) == 'W3C_Validator/') {
				$chosenFormat = "xhtml";
	
			} else {
				foreach($mimes as $format => $mime) {
					$regExp = '/' . str_replace(array('+','/'),array('\+','\/'), $mime) . '(;q=(\d+\.\d+))?/i';
					if (isset($_SERVER['HTTP_ACCEPT']) && preg_match($regExp, $_SERVER['HTTP_ACCEPT'], $matches)) {
						$preference = isset($matches[2]) ? $matches[2] : 1;
						if(!isset($q[$preference])) $q[$preference] = $format;
					}
				}

				if($q) {
					// Get the preferred format
					krsort($q);
					$chosenFormat = reset($q);
				} else {
					$chosenFormat = "html";
				}
			}
		}

		$negotiator = new ContentNegotiator();
		$negotiator->$chosenFormat( $response );
	}

	function xhtml(HTTPResponse $response) {
		$content = $response->getBody();
		
		// Only serve "pure" XHTML if the XML header is present
		if(substr($content,0,5) == '<' . '?xml' ) {
			$response->addHeader("Content-type", "application/xhtml+xml; charset=" . self::$encoding);
			$response->addHeader("Vary" , "Accept");
			
			$content = str_replace('&nbsp;','&#160;', $content);
			$content = str_replace('<br>','<br />', $content);
			$content = eregi_replace('(<img[^>]*[^/>])>','\\1/>', $content);
			
			$response->setBody($content);

		} else {
			return $this->html($response);
		}
	}
	function html(HTTPResponse $response) {
		$response->addHeader("Content-type", "text/html; charset=" . self::$encoding);
		$response->addHeader("Vary", "Accept");

		$content = $response->getBody();

		$content = ereg_replace("<\\?xml[^>]+\\?>\n?",'',$content);
		$content = str_replace(array('/>','xml:lang','application/xhtml+xml'),array('>','lang','text/html'), $content);
		$content = ereg_replace('<!DOCTYPE[^>]+>', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">', $content);
		$content = ereg_replace('<html xmlns="[^"]+"','<html ', $content);
		
		$response->setBody($content);
	}

	protected static $disabled;
	static function disable() {
		self::$disabled = true;
	}
}

?>
