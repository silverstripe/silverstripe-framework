<?php

namespace SilverStripe\Control;

use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ArrayLib;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Represents a HTTP-request, including a URL that is tokenised for parsing, and a request method
 * (GET/POST/PUT/DELETE). This is used by {@link RequestHandler} objects to decide what to do.
 *
 * Caution: objects of this class are immutable, e.g. echo $request['a']; works as expected,
 * but $request['a'] = '1'; has no effect.
 *
 * The intention is that a single HTTPRequest object can be passed from one object to another, each object calling
 * match() to get the information that they need out of the URL.  This is generally handled by
 * {@link RequestHandler::handleRequest()}.
 */
class HTTPRequest extends Request implements ArrayAccess
{
    /**
     * The non-extension parts of the passed URL as an array, originally exploded by the "/" separator.
     * All elements of the URL are loaded in here,
     * and subsequently popped out of the array by {@link shift()}.
     * Only use this structure for internal request handling purposes.
     *
     * @var array
     */
    protected $dirParts;

    /**
     * The URL extension (if present)
     *
     * @var string
     */
    protected $extension;

    /**
     * Contains an associative array of all
     * arguments matched in all calls to {@link RequestHandler->handleRequest()}.
     * It's a "historical record" that's specific to the current call of
     * {@link handleRequest()}, and is only complete once the "last call" to that method is made.
     *
     * @var array
     */
    protected $allParams = [];

    /**
     * Contains an associative array of all
     * arguments matched in the current call from {@link RequestHandler->handleRequest()},
     * as denoted with a "$"-prefix in the $url_handlers definitions.
     * Contains different states throughout its lifespan, so just useful
     * while processed in {@link RequestHandler} and to get the last
     * processes arguments.
     *
     * @var array
     */
    protected $latestParams = [];

    /**
     * Contains an associative array of all arguments
     * explicitly set in the route table for the current request.
     * Useful for passing generic arguments via custom routes.
     *
     * E.g. The "Locale" parameter would be assigned "en_NZ" below
     *
     * Director:
     *   rules:
     *     'en_NZ/$URLSegment!//$Action/$ID/$OtherID':
     *       Controller: 'ModelAsController'
     *       Locale: 'en_NZ'
     *
     * @var array
     */
    protected $routeParams = [];

    /**
     * @var int
     */
    protected $unshiftedButParsedParts = 0;

    /**
     * @var Session
     */
    protected $session;

    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->prepareDirParts();
    }

    /**
     * Allow the setting of a URL
     *
     * This is here so that RootURLController can change the URL of the request
     * without us losing all the other info attached (like headers)
     *
     * @param string $url The new URL
     * @return HTTPRequest The updated request
     */
    public function setUrl($url): static
    {
        $this->requestUri = $url;
        $this->pathInfo = $this->preparePathInfo();

        $this->prepareDirParts();

        return $this;
    }

    /**
     * Legacy method to split the url into it's subpart
     */
    private function prepareDirParts(): void
    {
        $url = $this->getUrl();

        // Remove any trailing slashes
        $url = trim($url, '/\\');

        if ($url) {
            // Strip out extension from url
            if (preg_match('/^(.*)\.([A-Za-z][A-Za-z0-9]*)$/', $url ?? '', $matches)) {
                $url = $matches[1];
            }

            $this->dirParts = preg_split('|/+|', $url ?? '');
        } else {
            $this->dirParts = [];
        }
    }

    /**
     * @return bool
     */
    public function isGET()
    {
        return $this->getMethod() === self::METHOD_GET;
    }

    /**
     * @return bool
     */
    public function isPOST()
    {
        return $this->getMethod() === self::METHOD_POST;
    }

    /**
     * @return bool
     */
    public function isPUT()
    {
        return $this->getMethod() === self::METHOD_PUT;
    }

    /**
     * @return bool
     */
    public function isDELETE()
    {
        return $this->getMethod() === self::METHOD_DELETE;
    }

    /**
     * @return bool
     */
    public function isHEAD()
    {
        return $this->getMethod() === self::METHOD_HEAD;
    }

    /**
     * @return null|string
     */
    public function getBody()
    {
        return $this->getContent();
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->query->all();
    }

    /**
     * @return array
     */
    public function postVars()
    {
        return $this->request->all();
    }

    /**
     * Returns all combined HTTP GET and POST parameters
     * passed into this request. If a parameter with the same
     * name exists in both arrays, the POST value is returned.
     *
     * @return array
     */
    public function requestVars()
    {
        return ArrayLib::array_merge_recursive($this->query->all(), $this->request->all());
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getVar($name)
    {
        if ($this->query->has($name)) {
            return $this->query->all()[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function postVar($name)
    {
        if ($this->request->has($name)) {
            return $this->request->all()[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function requestVar($name)
    {
        return $this->get($name);
    }

    /**
     * Returns a possible file extension found in parsing the URL
     * as denoted by a "."-character near the end of the URL.
     * Doesn't necessarily have to belong to an existing file,
     * as extensions can be also used for content-type-switching.
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Checks if the {@link HTTPRequest->getExtension()} on this request matches one of the more common media types
     * embedded into a webpage - e.g. css, png.
     *
     * This is useful for things like determining whether to display a fully rendered error page or not. Note that the
     * media file types is not at all comprehensive.
     *
     * @return bool
     */
    public function isMedia()
    {
        return in_array($this->getExtension(), ['css', 'js', 'jpg', 'jpeg', 'gif', 'png', 'bmp', 'ico']);
    }

    /**
     * Add a HTTP header to the response, replacing any header of the same name.
     *
     * @param string $header Example: "content-type"
     * @param string $value Example: "text/xml"
     */
    public function addHeader($header, $value)
    {
        $this->headers->set($header, $value);
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Returns a HTTP Header by name if found in the request
     *
     * @param string $header Name of the header (Insensitive to case as per <rfc2616 section 4.2 "Message Headers">)
     * @return mixed
     */
    public function getHeader($header)
    {
        return $this->headers->get($header);
    }

    /**
     * Remove an existing HTTP header by its name,
     * e.g. "Content-Type".
     *
     * @param string $header
     * @return HTTPRequest $this
     */
    public function removeHeader($header)
    {
        $this->headers->remove($header);
        return $this;
    }

    /**
     * Returns the URL used to generate the page
     *
     * @param bool $includeGetVars whether or not to include the get parameters
     * @return string
     */
    public function getURL($includeGetVars = false)
    {
        return $includeGetVars ? $this->getRequestUri() : $this->getPathInfo();
    }

    /**
     * Returns true if this request an ajax request,
     * based on custom HTTP ajax added by common JavaScript libraries,
     * or based on an explicit "ajax" request parameter.
     *
     * @return boolean
     */
    public function isAjax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Enables the existence of a key-value pair in the request to be checked using
     * array syntax, so isset($request['title']) will check for $_POST['title'] and $_GET['title']
     *
     * @param string $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->postVars[$offset]) || isset($this->getVars[$offset]);
    }

    /**
     * Access a request variable using array syntax. eg: $request['title'] instead of $request->postVar('title')
     *
     * @param string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->requestVar($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->getVars[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->getVars[$offset]);
        unset($this->postVars[$offset]);
    }

    /**
     * Construct an HTTPResponse that will deliver a file to the client.
     * Caution: Since it requires $fileData to be passed as binary data (no stream support),
     * it's only advisable to send small files through this method.
     * This function needs to be called inside the controller’s response, e.g.:
     * <code>$this->setResponse(HTTPRequest::send_file('the content', 'filename.txt'));</code>
     *
     * @static
     * @param $fileData
     * @param $fileName
     * @param null $mimeType
     * @return HTTPResponse
     */
    public static function send_file($fileData, $fileName, $mimeType = null)
    {
        // TODO Nuke this.

        if (!$mimeType) {
            $mimeType = HTTP::get_mime_type($fileName);
        }
        $response = new HTTPResponse($fileData);
        $response->addHeader("content-type", "$mimeType; name=\"" . addslashes($fileName ?? '') . "\"");
        // Note a IE-only fix that inspects this header in HTTP::add_cache_headers().
        $response->addHeader("content-disposition", "attachment; filename=\"" . addslashes($fileName ?? '') . "\"");
        $response->addHeader("content-length", strlen($fileData ?? ''));

        return $response;
    }

    /**
     * Matches a URL pattern
     * The pattern can contain a number of segments, separated by / (and an extension indicated by a .)
     *
     * The parts can be either literals, or, if they start with a $ they are interpreted as variables.
     *  - Literals must be provided in order to match
     *  - $Variables are optional
     *  - However, if you put ! at the end of a variable, then it becomes mandatory.
     *
     * For example:
     *  - admin/crm/list will match admin/crm/$Action/$ID/$OtherID, but it won't match admin/crm/$Action!/$ClassName!
     *
     * The pattern can optionally start with an HTTP method and a space.  For example, "POST $Controller/$Action".
     * This is used to define a rule that only matches on a specific HTTP method.
     *
     * @param $pattern
     * @param bool $shiftOnSuccess
     * @return array|bool
     */
    public function match($pattern, $shiftOnSuccess = false)
    {
        // Check if a specific method is required
        if (preg_match('/^([A-Za-z]+) +(.*)$/', $pattern ?? '', $matches)) {
            $requiredMethod = $matches[1];
            if ($requiredMethod != $this->getMethod()) {
                return false;
            }

            // If we get this far, we can match the URL pattern as usual.
            $pattern = $matches[2];
        }

        // Special case for the root URL controller (designated as an empty string, or a slash)
        if (!$pattern || $pattern === '/') {
            return ($this->dirParts == []) ? ['Matched' => true] : false;
        }

        // Check for the '//' marker that represents the "shifting point"
        $doubleSlashPoint = strpos($pattern ?? '', '//');
        if ($doubleSlashPoint !== false) {
            $shiftCount = substr_count(substr($pattern ?? '', 0, $doubleSlashPoint), '/') + 1;
            $pattern = str_replace('//', '/', $pattern ?? '');
            $patternParts = explode('/', $pattern ?? '');
        } else {
            $patternParts = explode('/', $pattern ?? '');
            $shiftCount = sizeof($patternParts ?? []);
        }

        // Filter out any "empty" matching parts - either from an initial / or a trailing /
        $patternParts = array_values(array_filter($patternParts ?? []));

        $arguments = [];

        foreach ($patternParts as $i => $part) {
            $part = trim($part ?? '');

            // Match a variable
            if (isset($part[0]) && $part[0] == '$') {
                // A variable ending in ! is required
                if (substr($part ?? '', -1) == '!') {
                    $varRequired = true;
                    $varName = substr($part ?? '', 1, -1);
                } else {
                    $varRequired = false;
                    $varName = substr($part ?? '', 1);
                }

                // Fail if a required variable isn't populated
                if ($varRequired && !isset($this->dirParts[$i])) {
                    return false;
                }

                /** @skipUpgrade */
                $key = "Controller";
                if ($varName === '*' || $varName === '@') {
                    if (isset($patternParts[$i + 1])) {
                        user_error(sprintf('All URL params after wildcard parameter $%s will be ignored', $varName), E_USER_WARNING);
                    }
                    if ($varName === '*') {
                        array_pop($patternParts);
                        $shiftCount = sizeof($patternParts ?? []);
                        $patternParts = array_merge($patternParts, array_slice($this->dirParts ?? [], $i ?? 0));
                        break;
                    } else {
                        array_pop($patternParts);
                        $shiftCount = sizeof($patternParts ?? []);
                        $remaining = count($this->dirParts ?? []) - $i;
                        for ($j = 1; $j <= $remaining; $j++) {
                            $arguments["$${j}"] = $this->dirParts[$j + $i - 1];
                        }
                        $patternParts = array_merge($patternParts, array_keys($arguments ?? []));
                        break;
                    }
                } else {
                    $arguments[$varName] = $this->dirParts[$i] ?? null;
                }
                if ($part == '$Controller'
                    && (
                        !ClassInfo::exists($arguments[$key])
                        || !is_subclass_of($arguments[$key], 'SilverStripe\\Control\\Controller')
                    )
                ) {
                    return false;
                }

            // Literal parts with extension
            } elseif (isset($this->dirParts[$i]) && $this->dirParts[$i] . '.' . $this->extension == $part) {
                continue;

            // Literal parts must always be there
            } elseif (!isset($this->dirParts[$i]) || $this->dirParts[$i] != $part) {
                return false;
            }
        }

        if ($shiftOnSuccess) {
            $this->shift($shiftCount);
            // We keep track of pattern parts that we looked at but didn't shift off.
            // This lets us say that we have *parsed* the whole URL even when we haven't *shifted* it all
            $this->unshiftedButParsedParts = sizeof($patternParts ?? []) - $shiftCount;
        }

        $this->latestParams = $arguments;

        // Load the arguments that actually have a value into $this->allParams
        // This ensures that previous values aren't overridden with blanks
        foreach ($arguments as $k => $v) {
            if ($v || !isset($this->allParams[$k])) {
                $this->allParams[$k] = $v;
            }
        }

        if ($arguments === []) {
            $arguments['_matched'] = true;
        }
        return $arguments;
    }

    /**
     * @return array
     */
    public function allParams()
    {
        return $this->allParams;
    }

    /**
     * Shift all the parameter values down a key space, and return the shifted value.
     *
     * @return string
     */
    public function shiftAllParams()
    {
        $keys    = array_keys($this->allParams ?? []);
        $values  = array_values($this->allParams ?? []);
        $value   = array_shift($values);

        // push additional unparsed URL parts onto the parameter stack
        if (array_key_exists($this->unshiftedButParsedParts, $this->dirParts ?? [])) {
            $values[] = $this->dirParts[$this->unshiftedButParsedParts];
        }

        foreach ($keys as $position => $key) {
            $this->allParams[$key] = isset($values[$position]) ? $values[$position] : null;
        }

        return $value;
    }

    /**
     * @return array
     */
    public function latestParams()
    {
        return $this->latestParams;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function latestParam($name)
    {
        if (isset($this->latestParams[$name])) {
            return $this->latestParams[$name];
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function routeParams()
    {
        return $this->routeParams;
    }

    /**
     * @param $params
     * @return HTTPRequest $this
     */
    public function setRouteParams($params)
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function params()
    {
        return array_merge($this->allParams, $this->routeParams);
    }

    /**
     * Finds a named URL parameter (denoted by "$"-prefix in $url_handlers)
     * from the full URL, or a parameter specified in the route table
     *
     * @param string $name
     * @return string Value of the URL parameter (if found)
     */
    public function param($name)
    {
        $params = $this->params();
        if (isset($params[$name])) {
            return $params[$name];
        } else {
            return null;
        }
    }

    /**
     * Returns the unparsed part of the original URL
     * separated by commas. This is used by {@link RequestHandler->handleRequest()}
     * to determine if further URL processing is necessary.
     *
     * @return string Partial URL
     */
    public function remaining()
    {
        return implode("/", $this->dirParts);
    }

    /**
     * Returns true if this is a URL that will match without shifting off any of the URL.
     * This is used by the request handler to prevent infinite parsing loops.
     *
     * @param string $pattern
     * @return bool
     */
    public function isEmptyPattern($pattern)
    {
        if (preg_match('/^([A-Za-z]+) +(.*)$/', $pattern ?? '', $matches)) {
            $pattern = $matches[2];
        }

        if (trim($pattern ?? '') == "") {
            return true;
        }
        return false;
    }

    /**
     * Shift one or more parts off the beginning of the URL.
     * If you specify shifting more than 1 item off, then the items will be returned as an array
     *
     * @param int $count Shift Count
     * @return string|array
     */
    public function shift($count = 1)
    {
        $return = [];

        if ($count == 1) {
            return array_shift($this->dirParts);
        }

        for ($i=0; $i<$count; $i++) {
            $value = array_shift($this->dirParts);

            if ($value === null) {
                break;
            }

            $return[] = $value;
        }

        return $return;
    }

    /**
     * Returns true if the URL has been completely parsed.
     * This will respect parsed but unshifted directory parts.
     *
     * @return bool
     */
    public function allParsed()
    {
        return sizeof($this->dirParts ?? []) <= $this->unshiftedButParsedParts;
    }

    /**
     * Returns the client IP address which originated this request.
     *
     * @return string
     */
    public function getIP()
    {
        return $this->getClientIp();
    }

    /**
     * Returns all mimetypes from the HTTP "Accept" header
     * as an array.
     *
     * @param boolean $includeQuality Don't strip away optional "quality indicators", e.g. "application/xml;q=0.9"
     *                                (Default: false)
     * @return array
     */
    public function getAcceptMimetypes($includeQuality = false)
    {
        return $this->getAcceptableContentTypes();
    }

    /**
     * @return string HTTP method (all uppercase)
     */
    public function httpMethod()
    {
        return $this->getMethod();
    }

    /**
     * Set the URL scheme (e.g. "http" or "https").
     * Equivalent to PSR-7 getUri()->getScheme(),
     *
     * @param string $scheme
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }
}
