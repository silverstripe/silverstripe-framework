<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * The content negotiator performs "text/html" or "application/xhtml+xml" switching. It does this through
 * the public static function ContentNegotiator::process(). By default, ContentNegotiator will comply to
 * the Accept headers the clients sends along with the HTTP request, which is most likely
 * "application/xhtml+xml" (see "Order of selection" below).
 *
 * Order of selection between html or xhtml is as follows:
 * - if PHP has already sent the HTTP headers, default to "html" (we can't send HTTP Content-Type headers
 *   any longer)
 * - if a GET variable ?forceFormat is set, it takes precedence (for testing purposes)
 * - if the user agent is detected as W3C Validator we always deliver "xhtml"
 * - if an HTTP Accept header is sent from the client, we respect its order (this is the most common case)
 * - if none of the above matches, fallback is "html"
 *
 * ContentNegotiator doesn't enable you to send content as a true XML document through the "text/xml"
 * or "application/xhtml+xml" Content-Type.
 *
 * Please see http://webkit.org/blog/68/understanding-html-xml-and-xhtml/ for further information.
 *
 * Some developers might know what they're doing and don't want ContentNegotiator messing with their
 * HTML4 doctypes, but still find it useful to have self-closing tags removed.
 */
class ContentNegotiator
{
    use Injectable;
    use Configurable;

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
     * @var bool
     */
    private static $enabled = false;

    /**
     * @var bool
     */
    protected static $current_enabled = null;

    /**
     * @config
     * @var string
     */
    private static $default_format = 'html';

    /**
     * Returns true if negotiation is enabled for the given response. By default, negotiation is only
     * enabled for pages that have the xml header.
     *
     * @param HTTPResponse $response
     * @return bool
     */
    public static function enabled_for($response)
    {
        $contentType = $response->getHeader("Content-Type");

        // Disable content negotiation for other content types
        if ($contentType
            && substr($contentType ?? '', 0, 9) != 'text/html'
            && substr($contentType ?? '', 0, 21) != 'application/xhtml+xml'
        ) {
            return false;
        }

        if (ContentNegotiator::getEnabled()) {
            return true;
        } else {
            return (substr($response->getBody() ?? '', 0, 5) == '<' . '?xml');
        }
    }

    /**
     * Gets the current enabled status, if it is not set this will fallback to config
     *
     * @return bool
     */
    public static function getEnabled()
    {
        if (isset(static::$current_enabled)) {
            return static::$current_enabled;
        }
        return Config::inst()->get(static::class, 'enabled');
    }

    /**
     * Sets the current enabled status
     *
     * @param bool $enabled
     */
    public static function setEnabled($enabled)
    {
        static::$current_enabled = $enabled;
    }

    /**
     * @param HTTPResponse $response
     */
    public static function process(HTTPResponse $response)
    {
        if (!ContentNegotiator::enabled_for($response)) {
            return;
        }

        $mimes = [
            "xhtml" => "application/xhtml+xml",
            "html" => "text/html",
        ];
        $q = [];
        if (headers_sent()) {
            $chosenFormat = static::config()->get('default_format');
        } elseif (isset($_GET['forceFormat'])) {
            $chosenFormat = $_GET['forceFormat'];
        } else {
            // The W3C validator doesn't send an HTTP_ACCEPT header, but it can support xhtml. We put this
            // special case in here so that designers don't get worried that their templates are HTML4.
            if (isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 14) == 'W3C_Validator/') {
                $chosenFormat = "xhtml";
            } else {
                foreach ($mimes as $format => $mime) {
                    $regExp = '/' . str_replace(['+', '/'], ['\+', '\/'], $mime ?? '') . '(;q=(\d+\.\d+))?/i';
                    if (isset($_SERVER['HTTP_ACCEPT']) && preg_match($regExp ?? '', $_SERVER['HTTP_ACCEPT'] ?? '', $matches)) {
                        $preference = isset($matches[2]) ? $matches[2] : 1;
                        if (!isset($q[$preference])) {
                            $q[$preference] = $format;
                        }
                    }
                }

                if ($q) {
                    // Get the preferred format
                    krsort($q);
                    $chosenFormat = reset($q);
                } else {
                    $chosenFormat = Config::inst()->get(static::class, 'default_format');
                }
            }
        }

        $negotiator = new ContentNegotiator();
        $negotiator->$chosenFormat($response);
    }

    /**
     * Check user defined content type and use it, if it's empty use the strict application/xhtml+xml.
     * Replaces a few common tags and entities with their XHTML representations (<br>, <img>, &nbsp;
     * <input>, checked, selected).
     *
     * @param HTTPResponse $response
     */
    public function xhtml(HTTPResponse $response)
    {
        $content = $response->getBody();
        $encoding = Config::inst()->get('SilverStripe\\Control\\ContentNegotiator', 'encoding');

        $contentType = Config::inst()->get('SilverStripe\\Control\\ContentNegotiator', 'content_type');
        if (empty($contentType)) {
            $response->addHeader("Content-Type", "application/xhtml+xml; charset=" . $encoding);
        } else {
            $response->addHeader("Content-Type", $contentType . "; charset=" . $encoding);
        }
        $response->addHeader("Vary", "Accept");

        // Fix base tag
        $content = preg_replace(
            '/<base href="([^"]*)"><!--\[if[[^\]*]\] \/><!\[endif\]-->/',
            '<base href="$1" />',
            $content ?? ''
        );

        $content = str_replace('&nbsp;', '&#160;', $content ?? '');
        $content = str_replace('<br>', '<br />', $content ?? '');
        $content = str_replace('<hr>', '<hr />', $content ?? '');
        $content = preg_replace('#(<img[^>]*[^/>])>#i', '\\1/>', $content ?? '');
        $content = preg_replace('#(<input[^>]*[^/>])>#i', '\\1/>', $content ?? '');
        $content = preg_replace('#(<param[^>]*[^/>])>#i', '\\1/>', $content ?? '');
        $content = preg_replace("#(\<option[^>]*[\s]+selected)(?!\s*\=)#si", "$1=\"selected\"$2", $content ?? '');
        $content = preg_replace("#(\<input[^>]*[\s]+checked)(?!\s*\=)#si", "$1=\"checked\"$2", $content ?? '');

        $response->setBody($content);
    }

    /**
     * Performs the following replacements:
     * - Check user defined content type and use it, if it's empty use the text/html.
     * - If find a XML header replaces it and existing doctypes with HTML4.01 Strict.
     * - Replaces self-closing tags like <img /> with unclosed solitary tags like <img>.
     * - Replaces all occurrences of "application/xhtml+xml" with "text/html" in the template.
     * - Removes "xmlns" attributes and any <?xml> Pragmas.
     *
     * @param HTTPResponse $response
     */
    public function html(HTTPResponse $response)
    {
        $encoding = $this->config()->get('encoding');
        $contentType = $this->config()->get('content_type');
        if (empty($contentType)) {
            $response->addHeader("Content-Type", "text/html; charset=" . $encoding);
        } else {
            $response->addHeader("Content-Type", $contentType . "; charset=" . $encoding);
        }
        $response->addHeader("Vary", "Accept");

        $content = $response->getBody();
        $hasXMLHeader = (substr($content ?? '', 0, 5) == '<' . '?xml');

        // Fix base tag
        $content = preg_replace(
            '/<base href="([^"]*)" \/>/',
            '<base href="$1"><!--[if lte IE 6]></base><![endif]-->',
            $content ?? ''
        );

        $content = preg_replace("#<\\?xml[^>]+\\?>\n?#", '', $content ?? '');
        $content = str_replace(
            ['/>', 'xml:lang', 'application/xhtml+xml'],
            ['>', 'lang', 'text/html'],
            $content ?? ''
        );

        // Only replace the doctype in templates with the xml header
        if ($hasXMLHeader) {
            $content = preg_replace(
                '/<!DOCTYPE[^>]+>/',
                '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
                $content ?? ''
            );
        }
        $content = preg_replace('/<html xmlns="[^"]+"/', '<html ', $content ?? '');

        $response->setBody($content);
    }
}
