<?php

namespace SilverStripe\Control;

use InvalidArgumentException;
use Monolog\Handler\HandlerInterface;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;

/**
 * Represents a response returned by a controller.
 */
class HTTPResponse
{
    use Injectable;

    /**
     * @var array
     */
    protected static $status_codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Request Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a Teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Unsufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var array
     */
    protected static $redirect_codes = [
        301,
        302,
        303,
        304,
        305,
        307,
        308,
    ];

    /**
     * @var string
     */
    protected $protocolVersion = '1.0';

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $statusDescription = "OK";

    /**
     * HTTP Headers like "content-type: text/xml"
     *
     * @see http://en.wikipedia.org/wiki/List_of_HTTP_headers
     * @var array
     */
    protected $headers = [
        "content-type" => "text/html; charset=utf-8",
    ];

    /**
     * @var string
     */
    protected $body = null;

    /**
     * Create a new HTTP response
     *
     * @param string $body The body of the response
     * @param int $statusCode The numeric status code - 200, 404, etc
     * @param string $statusDescription The text to be given alongside the status code.
     *  See {@link setStatusCode()} for more information.
     * @param string $protocolVersion
     */
    public function __construct($body = null, $statusCode = null, $statusDescription = null, $protocolVersion = null)
    {
        $this->setBody($body);
        if ($statusCode) {
            $this->setStatusCode($statusCode, $statusDescription);
        }
        if (!$protocolVersion) {
            if (preg_match('/HTTP\/(?<version>\d+(\.\d+)?)/i', $_SERVER['SERVER_PROTOCOL'] ?? '', $matches)) {
                $protocolVersion = $matches['version'];
            }
        }
        if ($protocolVersion) {
            $this->setProtocolVersion($protocolVersion);
        }
    }

    /**
     * The HTTP version used to respond to this request (typically 1.0 or 1.1)
     *
     * @param string $protocolVersion
     *
     * @return $this
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
        return $this;
    }

    /**
     * @param int $code
     * @param string $description Optional. See {@link setStatusDescription()}.
     *  No newlines are allowed in the description.
     *  If omitted, will default to the standard HTTP description
     *  for the given $code value (see {@link $status_codes}).
     *
     * @return $this
     */
    public function setStatusCode($code, $description = null)
    {
        if (isset(HTTPResponse::$status_codes[$code])) {
            $this->statusCode = $code;
        } else {
            throw new InvalidArgumentException("Unrecognised HTTP status code '$code'");
        }

        if ($description) {
            $this->statusDescription = $description;
        } else {
            $this->statusDescription = HTTPResponse::$status_codes[$code];
        }
        return $this;
    }

    /**
     * The text to be given alongside the status code ("reason phrase").
     * Caution: Will be overwritten by {@link setStatusCode()}.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setStatusDescription($description)
    {
        $this->statusDescription = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string Description for a HTTP status code
     */
    public function getStatusDescription()
    {
        return str_replace(["\r", "\n"], '', $this->statusDescription ?? '');
    }

    /**
     * Returns true if this HTTP response is in error
     *
     * @return bool
     */
    public function isError()
    {
        $statusCode = $this->getStatusCode();
        return $statusCode && ($statusCode < 200 || $statusCode > 399);
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body ? (string)$body : $body; // Don't type-cast false-ish values, eg null is null not ''
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Add a HTTP header to the response, replacing any header of the same name.
     *
     * @param string $header Example: "content-type"
     * @param string $value Example: "text/xml"
     *
     * @return $this
     */
    public function addHeader($header, $value)
    {
        $header = strtolower($header ?? '');
        $this->headers[$header] = $this->sanitiseHeader($value);
        return $this;
    }

    /**
     * Return the HTTP header of the given name.
     *
     * @param string $header
     *
     * @return string
     */
    public function getHeader($header)
    {
        $header = strtolower($header ?? '');
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        }
        return null;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Remove an existing HTTP header by its name,
     * e.g. "Content-Type".
     *
     * @param string $header
     *
     * @return $this
     */
    public function removeHeader($header)
    {
        $header = strtolower($header ?? '');
        unset($this->headers[$header]);
        return $this;
    }

    /**
     * Sanitise header values to avoid possible XSS vectors
     */
    private function sanitiseHeader(string $value): string
    {
        return preg_replace('/\v/', '', $value);
    }

    public function redirect(string $dest, int $code = 302): static
    {
        if (!in_array($code, HTTPResponse::$redirect_codes)) {
            trigger_error("Invalid HTTP redirect code {$code}", E_USER_WARNING);
            $code = 302;
        }
        $this->setStatusCode($code);
        $this->addHeader('location', $dest);
        return $this;
    }

    /**
     * Send this HTTPResponse to the browser
     */
    public function output()
    {
        // Attach appropriate X-Include-JavaScript and X-Include-CSS headers
        if (Director::is_ajax()) {
            Requirements::include_in_response($this);
        }

        if ($this->isRedirect() && headers_sent()) {
            $this->htmlRedirect();
        } else {
            $this->outputHeaders();
            $this->outputBody();
        }
    }

    /**
     * Generate a browser redirect without setting headers
     */
    protected function htmlRedirect()
    {
        $headersSent = headers_sent($file, $line);
        $location = $this->getHeader('location');
        $url = Director::absoluteURL((string) $location);
        $urlATT = Convert::raw2htmlatt($url);
        $urlJS = Convert::raw2js($url);
        $title = (Director::isDev() && $headersSent)
            ? "{$urlATT}... (output started on {$file}, line {$line})"
            : "{$urlATT}...";
        echo <<<EOT
<p>Redirecting to <a href="{$urlATT}" title="Click this link if your browser does not redirect you">{$title}</a></p>
<meta http-equiv="refresh" content="1; url={$urlATT}" />
<script type="application/javascript">setTimeout(function(){
	window.location.href = "{$urlJS}";
}, 50);</script>
EOT
        ;
    }

    /**
     * Output HTTP headers to the browser
     */
    protected function outputHeaders()
    {
        $headersSent = headers_sent($file, $line);
        if (!$headersSent) {
            $method = sprintf(
                "%s %d %s",
                $_SERVER['SERVER_PROTOCOL'],
                $this->getStatusCode(),
                $this->getStatusDescription()
            );
            header($method ?? '');
            foreach ($this->getHeaders() as $header => $value) {
                header("{$header}: {$value}", true, $this->getStatusCode() ?? 0);
            }
        } elseif ($this->getStatusCode() >= 300) {
            // It's critical that these status codes are sent; we need to report a failure if not.
            user_error(
                sprintf(
                    "Couldn't set response type to %d because of output on line %s of %s",
                    $this->getStatusCode(),
                    $line,
                    $file
                ),
                E_USER_WARNING
            );
        }
    }

    /**
     * Output body of this response to the browser
     */
    protected function outputBody()
    {
        // Only show error pages or generic "friendly" errors if the status code signifies
        // an error, and the response doesn't have any body yet that might contain
        // a more specific error description.
        $body = $this->getBody();
        if ($this->isError() && empty($body)) {
            $handler = Injector::inst()->get(HandlerInterface::class);
            $formatter = $handler->getFormatter();
            echo $formatter->format([
                'code' => $this->statusCode,
            ]);
        } else {
            echo $this->body;
        }
    }

    /**
     * Returns true if this response is "finished", that is, no more script execution should be done.
     * Specifically, returns true if a redirect has already been requested
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->isRedirect() || $this->isError();
    }

    /**
     * Determine if this response is a redirect
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->getStatusCode(), HTTPResponse::$redirect_codes);
    }

    /**
     * The HTTP response represented as a raw string
     *
     * @return string
     */
    public function __toString()
    {
        $headers = [];
        foreach ($this->getHeaders() as $header => $values) {
            foreach ((array)$values as $value) {
                $headers[] = sprintf('%s: %s', $header, $value);
            }
        }
        return
            sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $this->getStatusCode(), $this->getStatusDescription()) . "\r\n" .
            implode("\r\n", $headers) . "\r\n" . "\r\n" .
            $this->getBody();
    }
}
