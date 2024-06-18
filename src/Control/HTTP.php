<?php

namespace SilverStripe\Control;

use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use InvalidArgumentException;
use finfo;

/**
 * A class with HTTP-related helpers. Like Debug, this is more a bundle of methods than a class.
 */
class HTTP
{
    use Configurable;

    /**
     * Set to true to disable all deprecated HTTP Cache settings
     *
     * @var bool
     */
    private static $ignoreDeprecatedCaching = false;

    /**
     * Mapping of extension to mime types
     *
     * @var array
     * @config
     */
    private static $MimeTypes = [];

    /**
     * Turns a local system filename into a URL by comparing it to the script filename.
     *
     * @param string $filename
     * @return string
     */
    public static function filename2url($filename)
    {
        $filename = realpath($filename ?? '');
        if (!$filename) {
            return null;
        }

        // Filter files outside of the webroot
        $base = realpath(BASE_PATH);
        $baseLength = strlen($base ?? '');
        if (substr($filename ?? '', 0, $baseLength) !== $base) {
            return null;
        }

        $relativePath = ltrim(substr($filename ?? '', $baseLength ?? 0), '/\\');
        return Director::absoluteURL($relativePath);
    }

    /**
     * Turn all relative URLs in the content to absolute URLs.
     *
     * @param string $html
     *
     * @return string
     */
    public static function absoluteURLs($html)
    {
        $html = str_replace('$CurrentPageURL', Controller::curr()->getRequest()->getURL() ?? '', $html ?? '');
        return HTTP::urlRewriter($html, function ($url) {
            //no need to rewrite, if uri has a protocol (determined here by existence of reserved URI character ":")
            if (preg_match('/^\w+:/', $url ?? '')) {
                return $url;
            }
            return Director::absoluteURL((string) $url);
        });
    }

    /**
     * Rewrite all the URLs in the given content, evaluating the given string as PHP code.
     *
     * Put $URL where you want the URL to appear, however, you can't embed $URL in strings, for example:
     * <ul>
     * <li><code>'"../../" . $URL'</code></li>
     * <li><code>'myRewriter($URL)'</code></li>
     * <li><code>'(substr($URL, 0, 1)=="/") ? "../" . substr($URL, 1) : $URL'</code></li>
     * </ul>
     *
     * As of 3.2 $code should be a callable which takes a single parameter and returns the rewritten,
     * for example:
     * <code>
     * function(string $url) {
     *      return Director::absoluteURL((string) $url, true);
     * }
     * </code>
     *
     * @param string $content The HTML to search for links to rewrite.
     * @param callable $code Either a string that can evaluate to an expression to rewrite links
     * (depreciated), or a callable that takes a single parameter and returns the rewritten URL.
     *
     * @return string The content with all links rewritten as per the logic specified in $code.
     */
    public static function urlRewriter($content, $code)
    {
        if (!is_callable($code)) {
            throw new InvalidArgumentException(
                'HTTP::urlRewriter expects a callable as the second parameter'
            );
        }

        // Replace attributes
        $attribs = ["src", "background", "a" => "href", "link" => "href", "base" => "href"];
        $regExps = [];
        foreach ($attribs as $tag => $attrib) {
            if (!is_numeric($tag)) {
                $tagPrefix = "$tag ";
            } else {
                $tagPrefix = "";
            }

            $regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *\")([^\"]*)(\")/i";
            $regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *')([^']*)(')/i";
            $regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *)([^\"' ]*)( )/i";
        }
        // Replace css styles
        $styles = ['background-image', 'background', 'list-style-image', 'list-style', 'content'];
        foreach ($styles as $style) {
            $regExps[] = "/($style:[^;]*url *\\(\")([^\"]+)(\"\\))/i";
            $regExps[] = "/($style:[^;]*url *\\(')([^']+)('\\))/i";
            $regExps[] = "/($style:[^;]*url *\\()([^\"\\)')]+)(\\))/i";
        }

        // Callback for regexp replacement
        $callback = function ($matches) use ($code) {
            // Decode HTML attribute
            $URL = Convert::xml2raw($matches[2]);
            $rewritten = $code($URL);
            return $matches[1] . Convert::raw2xml($rewritten) . $matches[3];
        };

        // Execute each expression
        foreach ($regExps as $regExp) {
            $content = preg_replace_callback($regExp ?? '', $callback, $content ?? '');
        }

        return $content;
    }

    /**
     * Will try to include a GET parameter for an existing URL, preserving existing parameters and
     * fragments. If no URL is given, falls back to $_SERVER['REQUEST_URI']. Uses parse_url() to
     * dissect the URL, and http_build_query() to reconstruct it with the additional parameter.
     * Converts any '&' (ampersand) URL parameter separators to the more XHTML compliant '&amp;'.
     *
     * CAUTION: If the URL is determined to be relative, it is prepended with Director::absoluteBaseURL().
     * This method will always return an absolute URL because Director::makeRelative() can lead to
     * inconsistent results.
     *
     * @param string $varname
     * @param string $varvalue
     * @param string|null $currentURL Relative or absolute URL, or HTTPRequest to get url from
     * @param string $separator Separator for http_build_query().
     * @return string
     */
    public static function setGetVar($varname, $varvalue, $currentURL = null, $separator = '&')
    {
        if (!isset($currentURL)) {
            $request = Controller::curr()->getRequest();
            $currentURL = $request->getURL(true);
        }
        $uri = $currentURL;

        $isRelative = false;
        // We need absolute URLs for parse_url()
        if (Director::is_relative_url($uri)) {
            $uri = Controller::join_links(Director::absoluteBaseURL(), $uri);
            $isRelative = true;
        }

        // try to parse uri
        $parts = parse_url($uri ?? '');
        if (!$parts) {
            throw new InvalidArgumentException("Can't parse URL: " . $uri);
        }

        // Parse params and add new variable
        $params = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'] ?? '', $params);
        }
        $params[$varname] = $varvalue;

        // Generate URI segments and formatting
        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $user = (isset($parts['user']) && $parts['user'] != '') ? $parts['user'] : '';

        if ($user != '') {
            // format in either user:pass@host.com or user@host.com
            $user .= (isset($parts['pass']) && $parts['pass'] != '') ? ':' . $parts['pass'] . '@' : '@';
        }

        $host = (isset($parts['host'])) ? $parts['host'] : '';
        $port = (isset($parts['port']) && $parts['port'] != '') ? ':' . $parts['port'] : '';
        $path = (isset($parts['path']) && $parts['path'] != '') ? $parts['path'] : '';

        // handle URL params which are existing / new
        $params = ($params) ? '?' . http_build_query($params, '', $separator) : '';

        // keep fragments (anchors) intact.
        $fragment = (isset($parts['fragment']) && $parts['fragment'] != '') ? '#' . $parts['fragment'] : '';

        // Recompile URI segments
        $newUri = $scheme . '://' . $user . $host . $port . $path . $params . $fragment;

        if ($isRelative) {
            return Controller::join_links(Director::baseURL(), Director::makeRelative($newUri));
        }

        return $newUri;
    }

    /**
     * @param string $varname
     * @param string $varvalue
     * @param null|string $currentURL
     *
     * @return string
     */
    public static function RAW_setGetVar($varname, $varvalue, $currentURL = null)
    {
        $url = HTTP::setGetVar($varname, $varvalue, $currentURL);
        return Convert::xml2raw($url);
    }

    /**
     * Search for all tags with a specific attribute, then return the value of that attribute in a
     * flat array.
     *
     * @param string $content
     * @param array $attributes An array of tags to attributes, for example "[a] => 'href', [div] => 'id'"
     *
     * @return array
     */
    public static function findByTagAndAttribute($content, $attributes)
    {
        $regexes = [];

        foreach ($attributes as $tag => $attribute) {
            $regexes[] = "/<{$tag} [^>]*$attribute *= *([\"'])(.*?)\\1[^>]*>/i";
            $regexes[] = "/<{$tag} [^>]*$attribute *= *([^ \"'>]+)/i";
        }

        $result = [];

        if ($regexes) {
            foreach ($regexes as $regex) {
                if (preg_match_all($regex ?? '', $content ?? '', $matches)) {
                    $result = array_merge_recursive($result, (isset($matches[2]) ? $matches[2] : $matches[1]));
                }
            }
        }

        return count($result ?? []) ? $result : null;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public static function getLinksIn($content)
    {
        return HTTP::findByTagAndAttribute($content, ["a" => "href"]);
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public static function getImagesIn($content)
    {
        return HTTP::findByTagAndAttribute($content, ["img" => "src"]);
    }

    /**
     * Get the MIME type based on a file's extension. If the finfo class exists in PHP, and the file
     * exists relative to the project root, then use that extension, otherwise fallback to a list of
     * commonly known MIME types.
     *
     * @param string $filename
     * @return string
     */
    public static function get_mime_type($filename)
    {
        // If the finfo module is compiled into PHP, use it.
        $path = BASE_PATH . DIRECTORY_SEPARATOR . $filename;
        if (class_exists('finfo') && file_exists($path ?? '')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($path);
        }

        // Fallback to use the list from the HTTP.yml configuration and rely on the file extension
        // to get the file mime-type
        $ext = strtolower(File::get_file_extension($filename) ?? '');
        // Get the mime-types
        $mimeTypes = HTTP::config()->uninherited('MimeTypes');

        // The mime type doesn't exist
        if (!isset($mimeTypes[$ext])) {
            return 'application/unknown';
        }

        return $mimeTypes[$ext];
    }
}
