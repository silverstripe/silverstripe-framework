<?php

namespace SilverStripe\Control;

use SilverStripe\Assets\File;
use SilverStripe\Control\Middleware\ChangeDetectionMiddleware;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use InvalidArgumentException;
use finfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;

/**
 * A class with HTTP-related helpers. Like Debug, this is more a bundle of methods than a class.
 */
class HTTP
{
    use Configurable;

    /**
     * @deprecated 4.2..5.0 Use HTTPCacheControlMiddleware::singleton()->setMaxAge($age) instead
     * @var int
     */
    protected static $cache_age = 0;

    /**
     * @deprecated 4.2..5.0 Handled by HTTPCacheControlMiddleware
     * @var int
     */
    protected static $modification_date = null;

    /**
     * @deprecated 4.2..5.0 Handled by ChangeDetectionMiddleware
     * @var string
     */
    protected static $etag = null;

    /**
     * @config
     * @var bool
     * @deprecated 4.2..5.0 'HTTP.cache_ajax_requests config is deprecated.
     * Use HTTPCacheControlMiddleware::disableCache() instead'
     */
    private static $cache_ajax_requests = false;

    /**
     * @config
     * @var bool
     * @deprecated 4.2..5.0 Use HTTPCacheControlMiddleware.defaultState/.defaultForcingLevel instead
     */
    private static $disable_http_cache = false;

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
     * List of names to add to the Cache-Control header.
     *
     * @deprecated 4.2..5.0 Handled by HTTPCacheControlMiddleware instead
     * @see HTTPCacheControlMiddleware::__construct()
     * @config
     * @var array Keys are cache control names, values are boolean flags
     */
    private static $cache_control = [];

    /**
     * Vary string; A comma separated list of var header names
     *
     * @deprecated 4.2..5.0 Handled by HTTPCacheControlMiddleware instead
     * @config
     * @var string|null
     */
    private static $vary = null;

    /**
     * Turns a local system filename into a URL by comparing it to the script filename.
     *
     * @param string $filename
     * @return string
     */
    public static function filename2url($filename)
    {
        $filename = realpath($filename);
        if (!$filename) {
            return null;
        }

        // Filter files outside of the webroot
        $base = realpath(BASE_PATH);
        $baseLength = strlen($base);
        if (substr($filename, 0, $baseLength) !== $base) {
            return null;
        }

        $relativePath = ltrim(substr($filename, $baseLength), '/\\');
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
        $html = str_replace('$CurrentPageURL', Controller::curr()->getRequest()->getURL(), $html);
        return HTTP::urlRewriter($html, function ($url) {
            //no need to rewrite, if uri has a protocol (determined here by existence of reserved URI character ":")
            if (preg_match('/^\w+:/', $url)) {
                return $url;
            }
            return Director::absoluteURL($url, true);
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
     * function($url) {
     *      return Director::absoluteURL($url, true);
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
        // @todo - http://www.css3.info/preview/multiple-backgrounds/
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
            $content = preg_replace_callback($regExp, $callback, $content);
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
            $uri = Director::absoluteBaseURL() . $uri;
            $isRelative = true;
        }

        // try to parse uri
        $parts = parse_url($uri);
        if (!$parts) {
            throw new InvalidArgumentException("Can't parse URL: " . $uri);
        }

        // Parse params and add new variable
        $params = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);
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
        $params = ($params) ? '?' . http_build_query($params, null, $separator) : '';

        // keep fragments (anchors) intact.
        $fragment = (isset($parts['fragment']) && $parts['fragment'] != '') ? '#' . $parts['fragment'] : '';

        // Recompile URI segments
        $newUri = $scheme . '://' . $user . $host . $port . $path . $params . $fragment;

        if ($isRelative) {
            return Director::makeRelative($newUri);
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
        $url = self::setGetVar($varname, $varvalue, $currentURL);
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
                if (preg_match_all($regex, $content, $matches)) {
                    $result = array_merge_recursive($result, (isset($matches[2]) ? $matches[2] : $matches[1]));
                }
            }
        }

        return count($result) ? $result : null;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public static function getLinksIn($content)
    {
        return self::findByTagAndAttribute($content, ["a" => "href"]);
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public static function getImagesIn($content)
    {
        return self::findByTagAndAttribute($content, ["img" => "src"]);
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
        if (class_exists('finfo') && file_exists($path)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($path);
        }

        // Fallback to use the list from the HTTP.yml configuration and rely on the file extension
        // to get the file mime-type
        $ext = strtolower(File::get_file_extension($filename));
        // Get the mime-types
        $mimeTypes = HTTP::config()->uninherited('MimeTypes');

        // The mime type doesn't exist
        if (!isset($mimeTypes[$ext])) {
            return 'application/unknown';
        }

        return $mimeTypes[$ext];
    }

    /**
     * Set the maximum age of this page in web caches, in seconds.
     *
     * @deprecated 4.2..5.0 Use HTTPCacheControlMiddleware::singleton()->setMaxAge($age) instead
     * @param int $age
     */
    public static function set_cache_age($age)
    {
        Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware::singleton()->setMaxAge($age) instead');
        self::$cache_age = $age;
        HTTPCacheControlMiddleware::singleton()->setMaxAge($age);
    }

    /**
     * @param string $dateString
     * @deprecated 4.2..5.0 Use HTTPCacheControlMiddleware::registerModificationDate() instead
     */
    public static function register_modification_date($dateString)
    {
        Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware::registerModificationDate() instead');
        HTTPCacheControlMiddleware::singleton()->registerModificationDate($dateString);
    }

    /**
     * @param int $timestamp
     * @deprecated 4.2..5.0 Use HTTPCacheControlMiddleware::registerModificationDate() instead
     */
    public static function register_modification_timestamp($timestamp)
    {
        Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware::registerModificationDate() instead');
        HTTPCacheControlMiddleware::singleton()->registerModificationDate($timestamp);
    }

    /**
     * @deprecated 4.2..5.0 Use ChangeDetectionMiddleware instead
     * @param string $etag
     */
    public static function register_etag($etag)
    {
        Deprecation::notice('5.0', 'Use ChangeDetectionMiddleware instead');
        if (strpos($etag, '"') !== 0) {
            $etag =  "\"{$etag}\"";
        }
        self::$etag = $etag;
    }

    /**
     * Add the appropriate caching headers to the response, including If-Modified-Since / 304 handling.
     * Note that setting HTTP::$cache_age will overrule any cache headers set by PHP's
     * session_cache_limiter functionality. It is your responsibility to ensure only cacheable data
     * is in fact cached, and HTTP::$cache_age isn't set when the HTTP body contains session-specific
     * content.
     *
     * Omitting the $body argument or passing a string is deprecated; in these cases, the headers are
     * output directly.
     *
     * @param HTTPResponse $response
     * @deprecated 4.2..5.0 Headers are added automatically by HTTPCacheControlMiddleware instead.
     */
    public static function add_cache_headers($response = null)
    {
        Deprecation::notice('5.0', 'Headers are added automatically by HTTPCacheControlMiddleware instead.');

        // Skip if deprecated API is disabled
        if (Config::inst()->get(HTTP::class, 'ignoreDeprecatedCaching')) {
            return;
        }

        // Ensure a valid response object is provided
        if (!$response instanceof HTTPResponse) {
            user_error("HTTP::add_cache_headers() must be passed an HTTPResponse object", E_USER_WARNING);
            return;
        }

        // Warn if already assigned cache-control headers
        if ($response->getHeader('Cache-Control')) {
            trigger_error(
                'Cache-Control header has already been set. '
                . 'Please use HTTPCacheControlMiddleware API to set caching options instead.',
                E_USER_WARNING
            );
            return;
        }

        // Ensure a valid request object exists in the current context
        if (!Injector::inst()->has(HTTPRequest::class)) {
            user_error("HTTP::add_cache_headers() cannot work without a current HTTPRequest object", E_USER_WARNING);
            return;
        }

        /** @var HTTPRequest $request */
        $request = Injector::inst()->get(HTTPRequest::class);

        // Run middleware
        ChangeDetectionMiddleware::singleton()->process($request, function (HTTPRequest $request) use ($response) {
            return HTTPCacheControlMiddleware::singleton()->process($request, function (HTTPRequest $request) use ($response) {
                return $response;
            });
        });
    }

    /**
     * Ensure that all deprecated HTTP cache settings are respected
     *
     * @deprecated 4.2..5.0 Use HTTPCacheControlMiddleware instead
     * @throws \LogicException
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     */
    public static function augmentState(HTTPRequest $request, HTTPResponse $response)
    {
        // Skip if deprecated API is disabled
        $config = Config::forClass(HTTP::class);
        if ($config->get('ignoreDeprecatedCaching')) {
            return;
        }

        $cacheControlMiddleware = HTTPCacheControlMiddleware::singleton();

        // if http caching is disabled by config, disable it - used on dev environments due to frequently changing
        // templates and other data. will be overridden by forced publicCache(true) or privateCache(true) calls
        if ($config->get('disable_http_cache')) {
            Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware.defaultState/.defaultForcingLevel instead');
            $cacheControlMiddleware->disableCache();
        }

        // if no caching ajax requests, disable ajax if is ajax request
        if (!$config->get('cache_ajax_requests') && Director::is_ajax()) {
            Deprecation::notice(
                '5.0',
                'HTTP.cache_ajax_requests config is deprecated. Use HTTPCacheControlMiddleware::disableCache() instead'
            );
            $cacheControlMiddleware->disableCache();
        }

        // Pass vary to middleware
        $configVary = $config->get('vary');
        if ($configVary) {
            Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware.defaultVary instead');
            $cacheControlMiddleware->addVary($configVary);
        }

        // Pass cache_control to middleware
        $configCacheControl = $config->get('cache_control');
        if ($configCacheControl) {
            Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware API instead');

            $supportedDirectives = ['max-age', 'no-cache', 'no-store', 'must-revalidate'];
            if ($foundUnsupported = array_diff(array_keys($configCacheControl), $supportedDirectives)) {
                throw new \LogicException(
                    'Found unsupported legacy directives in HTTP.cache_control: ' .
                    implode(', ', $foundUnsupported) .
                    '. Please use HTTPCacheControlMiddleware API instead'
                );
            }

            if (isset($configCacheControl['max-age'])) {
                $cacheControlMiddleware->setMaxAge($configCacheControl['max-age']);
            }

            if (isset($configCacheControl['no-cache'])) {
                $cacheControlMiddleware->setNoCache((bool)$configCacheControl['no-cache']);
            }

            if (isset($configCacheControl['no-store'])) {
                $cacheControlMiddleware->setNoStore((bool)$configCacheControl['no-store']);
            }

            if (isset($configCacheControl['must-revalidate'])) {
                $cacheControlMiddleware->setMustRevalidate((bool)$configCacheControl['must-revalidate']);
            }
        }

        // Set modification date
        if (self::$modification_date) {
            Deprecation::notice('5.0', 'Use HTTPCacheControlMiddleware::registerModificationDate() instead');
            $cacheControlMiddleware->registerModificationDate(self::$modification_date);
        }

        // Ensure deprecated $etag property is assigned
        if (self::$etag && !$cacheControlMiddleware->hasDirective('no-store') && !$response->getHeader('ETag')) {
            Deprecation::notice('5.0', 'Etag should not be set explicitly');
            $response->addHeader('ETag', self::$etag);
        }
    }

    /**
     * Return an {@link http://www.faqs.org/rfcs/rfc2822 RFC 2822} date in the GMT timezone (a timestamp
     * is always in GMT: the number of seconds since January 1 1970 00:00:00 GMT)
     *
     * @param int $timestamp
     * @deprecated 4.2..5.0 Inline if you need this
     * @return string
     */
    public static function gmt_date($timestamp)
    {
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    /**
     * Return static variable cache_age in second
     *
     * @return int
     */
    public static function get_cache_age()
    {
        return self::$cache_age;
    }
}
