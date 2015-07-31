<?php
/**
 * The content negotiator performs "text/html" or "application/xhtml+xml" switching.
 * It does this through the public static function ContentNegotiator::process().
 * By default, ContentNegotiator will comply to the Accept headers the clients
 * sends along with the HTTP request, which is most likely "application/xhtml+xml"
 * (see "Order of selection" below).
 *
 * Order of selection between html or xhtml is as follows:
 * - if PHP has already sent the HTTP headers, default to "html" (we can't send HTTP Content-Type headers any longer)
 * - if a GET variable ?forceFormat is set, it takes precedence (for testing purposes)
 * - if the user agent is detected as W3C Validator we always deliver "xhtml"
 * - if an HTTP Accept header is sent from the client, we respect its order (this is the most common case)
 * - if none of the above matches, fallback is "html"
 *
 * ContentNegotiator doesn't enable you to send content as a true XML document
 * through the "text/xml" or "application/xhtml+xml" Content-Type.
 * Please see http://webkit.org/blog/68/understanding-html-xml-and-xhtml/ for further information.
 *
 * @package framework
 * @subpackage control
 *
 * @todo Check for correct XHTML doctype in xhtml()
 * @todo Allow for other HTML4 doctypes (e.g. Transitional) in html()
 * @todo Make content replacement and doctype setting two separately configurable behaviours - some
 * devs might know what they're doing and don't want contentnegotiator messing with their HTML4 doctypes,
 * but still find it useful to have self-closing tags removed.
 */
class ContentNegotiator extends Object {

	/**
	 * @config
	 * @var string
	 */
	private static $content_type = '';

	/**
	 * @config
	 * @var string
	 */
	private static $encoding = 'utf-8';

	/**
	 * @config
	 * @var boolean
	 */
	private static $enabled = false;

	/**
	 * @config
	 * @var string
	 */
	private static $default_format = 'html';

	/**
	 * Set the character set encoding for this page.  By default it's utf-8, but you could change it to, say,
	 * windows-1252, to improve interoperability with extended characters being imported from windows excel.
	 *
	 * @deprecated 4.0 Use the "ContentNegotiator.encoding" config setting instead
	 */
	public static function set_encoding($encoding) {
		Deprecation::notice('4.0', 'Use the "ContentNegotiator.encoding" config setting instead');
		Config::inst()->update('ContentNegotiator', 'encoding', $encoding);
	}

	/**
	 * Return the character encoding set bhy ContentNegotiator::set_encoding().  It's recommended that all classes
	 * that need to specify the character set make use of this function.
	 *
	 * @deprecated 4.0 Use the "ContentNegotiator.encoding" config setting instead
	 */
	public static function get_encoding() {
		Deprecation::notice('4.0', 'Use the "ContentNegotiator.encoding" config setting instead');
		return Config::inst()->get('ContentNegotiator', 'encoding');
	}

	/**
	 * Enable content negotiation for all templates, not just those with the xml header.
	 *
	 * @deprecated 4.0 Use the "ContentNegotiator.enabled" config setting instead
	 */
	public static function enable() {
		Deprecation::notice('4.0', 'Use the "ContentNegotiator.enabled" config setting instead');
		Config::inst()->update('ContentNegotiator', 'enabled', true);
	}

	/**
	 * Disable content negotiation for all templates, not just those with the xml header.
	 *
	 * @deprecated 4.0 Use the "ContentNegotiator.enabled" config setting instead
	 */
	public static function disable() {
		Deprecation::notice('4.0', 'Use the "ContentNegotiator.enabled" config setting instead');
		Config::inst()->update('ContentNegotiator', 'enabled', false);
	}

	/**
	 * Returns true if negotation is enabled for the given response.
	 * By default, negotiation is only enabled for pages that have the xml header.
	 */
	public static function enabled_for($response) {
		$contentType = $response->getHeader("Content-Type");

		// Disable content negotation for other content types
		if($contentType && substr($contentType, 0,9) != 'text/html'
				&& substr($contentType, 0,21) != 'application/xhtml+xml') {
			return false;
		}

		if(Config::inst()->get('ContentNegotiator', 'enabled')) return true;
		else return (substr($response->getBody(),0,5) == '<' . '?xml');
	}

	public static function process(SS_HTTPResponse $response) {
		if(!self::enabled_for($response)) return;

		$mimes = array(
			"xhtml" => "application/xhtml+xml",
			"html" => "text/html",
		);
		$q = array();
		if(headers_sent()) {
			$chosenFormat = Config::inst()->get('ContentNegotiator', 'default_format');

		} else if(isset($_GET['forceFormat'])) {
			$chosenFormat = $_GET['forceFormat'];

		} else {
			// The W3C validator doesn't send an HTTP_ACCEPT header, but it can support xhtml.  We put this special
			// case in here so that designers don't get worried that their templates are HTML4.
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
					$chosenFormat = Config::inst()->get('ContentNegotiator', 'default_format');
				}
			}
		}

		$negotiator = new ContentNegotiator();
		$negotiator->$chosenFormat( $response );
	}

	/**
	 * Check user defined content type and use it, if it's empty use the strict application/xhtml+xml.
	 * Replaces a few common tags and entities with their XHTML representations (<br>, <img>, &nbsp;
	 * <input>, checked, selected).
	 *
	 * @param $response SS_HTTPResponse
	 * @return string
	 * @todo Search for more xhtml replacement
	 */
	public function xhtml(SS_HTTPResponse $response) {
		$content = $response->getBody();
		$encoding = Config::inst()->get('ContentNegotiator', 'encoding');

		$contentType = Config::inst()->get('ContentNegotiator', 'content_type');
		if (empty($contentType)) {
			$response->addHeader("Content-Type", "application/xhtml+xml; charset=" . $encoding);
		} else {
			$response->addHeader("Content-Type", $contentType . "; charset=" . $encoding);
		}
		$response->addHeader("Vary" , "Accept");

		// Fix base tag
		$content = preg_replace('/<base href="([^"]*)"><!--\[if[[^\]*]\] \/><!\[endif\]-->/',
			'<base href="$1" />', $content);

		$content = str_replace('&nbsp;','&#160;', $content);
		$content = str_replace('<br>','<br />', $content);
		$content = str_replace('<hr>','<hr />', $content);
		$content = preg_replace('#(<img[^>]*[^/>])>#i', '\\1/>', $content);
		$content = preg_replace('#(<input[^>]*[^/>])>#i', '\\1/>', $content);
		$content = preg_replace('#(<param[^>]*[^/>])>#i', '\\1/>', $content);
		$content = preg_replace("#(\<option[^>]*[\s]+selected)(?!\s*\=)#si", "$1=\"selected\"$2", $content);
		$content = preg_replace("#(\<input[^>]*[\s]+checked)(?!\s*\=)#si", "$1=\"checked\"$2", $content);

		$response->setBody($content);
	}

	/*
	 * Check user defined content type and use it, if it's empty use the text/html.
	 * If find a XML header replaces it and existing doctypes with HTML4.01 Strict.
	 * Replaces self-closing tags like <img /> with unclosed solitary tags like <img>.
	 * Replaces all occurrences of "application/xhtml+xml" with "text/html" in the template.
	 * Removes "xmlns" attributes and any <?xml> Pragmas.
	 */
	public function html(SS_HTTPResponse $response) {
		$encoding = Config::inst()->get('ContentNegotiator', 'encoding');
		$contentType = Config::inst()->get('ContentNegotiator', 'content_type');
		if (empty($contentType)) {
			$response->addHeader("Content-Type", "text/html; charset=" . $encoding);
		} else {
			$response->addHeader("Content-Type", $contentType . "; charset=" . $encoding);
		}
		$response->addHeader("Vary", "Accept");

		$content = $response->getBody();
		$hasXMLHeader = (substr($content,0,5) == '<' . '?xml' );

		// Fix base tag
		$content = preg_replace('/<base href="([^"]*)" \/>/',
			'<base href="$1"><!--[if lte IE 6]></base><![endif]-->', $content);

		$content = preg_replace("#<\\?xml[^>]+\\?>\n?#", '', $content);
		$content = str_replace(array('/>','xml:lang','application/xhtml+xml'),array('>','lang','text/html'), $content);

		// Only replace the doctype in templates with the xml header
		if($hasXMLHeader) {
			$content = preg_replace('/<!DOCTYPE[^>]+>/',
				'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
				$content);
		}
		$content = preg_replace('/<html xmlns="[^"]+"/','<html ', $content);

		$response->setBody($content);
	}

}
