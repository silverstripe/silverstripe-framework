<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Util\IPUtils;

/**
 * This middleware will rewrite headers that provide IP and host details from an upstream proxy.
 */
class TrustedProxyMiddleware implements HTTPMiddleware
{
    /**
     * Comma-separated list of IP ranges that are trusted to provide proxy headers.
     * Can also be 'none' or '*' (all)
     *
     * @var string
     */
    private $trustedProxyIPs = null;

    /**
     * Array of headers from which to lookup the hostname
     *
     * @var array
     */
    private $proxyHostHeaders = [
        'X-Forwarded-Host'
    ];

    /**
     * Array of headers from which to lookup the client IP
     *
     * @var array
     */
    private $proxyIPHeaders = [
        'Client-IP',
        'X-Forwarded-For'
    ];

    /**
     * Array of headers from which to lookup the client scheme (http/https)
     *
     * @var array
     */
    private $proxySchemeHeaders = [
        'X-Forwarded-Protocol',
        'X-Forwarded-Proto',
    ];

    /**
     * Return the comma-separated list of IP ranges that are trusted to provide proxy headers
     * Can also be 'none' or '*' (all)
     *
     * @return string
     */
    public function getTrustedProxyIPs()
    {
        return $this->trustedProxyIPs;
    }

    /**
     * Set the comma-separated list of IP ranges that are trusted to provide proxy headers
     * Can also be 'none' or '*' (all)
     *
     * @param string $trustedProxyIPs
     * @return $this
     */
    public function setTrustedProxyIPs($trustedProxyIPs)
    {
        $this->trustedProxyIPs = $trustedProxyIPs;
        return $this;
    }

    /**
     * Return the array of headers from which to lookup the hostname
     *
     * @return array
     */
    public function getProxyHostHeaders()
    {
        return $this->proxyHostHeaders;
    }

    /**
     * Set the array of headers from which to lookup the hostname.
     *
     * @param array $proxyHostHeaders
     * @return $this
     */
    public function setProxyHostHeaders($proxyHostHeaders)
    {
        $this->proxyHostHeaders = $proxyHostHeaders ?: [];
        return $this;
    }

    /**
     * Return the array of headers from which to lookup the client IP
     *
     * @return array
     */
    public function getProxyIPHeaders()
    {
        return $this->proxyIPHeaders;
    }

    /**
     * Set the array of headers from which to lookup the client IP.
     *
     * @param array $proxyIPHeaders
     * @return $this
     */
    public function setProxyIPHeaders($proxyIPHeaders)
    {
        $this->proxyIPHeaders = $proxyIPHeaders ?: [];
        return $this;
    }

    /**
     * Return the array of headers from which to lookup the client scheme (http/https)
     *
     * @return array
     */
    public function getProxySchemeHeaders()
    {
        return $this->proxySchemeHeaders;
    }

    /**
     * Set array of headers from which to lookup the client scheme (http/https)
     * Can also specify comma-separated list as a single string.
     *
     * @param array $proxySchemeHeaders
     * @return $this
     */
    public function setProxySchemeHeaders($proxySchemeHeaders)
    {
        $this->proxySchemeHeaders = $proxySchemeHeaders ?: [];
        return $this;
    }

    public function process(HTTPRequest $request, callable $delegate)
    {
        // If this is a trust proxy
        if ($this->isTrustedProxy($request)) {
            // Replace host
            foreach ($this->getProxyHostHeaders() as $header) {
                $hostList = $request->getHeader($header);
                if ($hostList) {
                    $request->addHeader('Host', strtok($hostList ?? '', ','));
                    break;
                }
            }

            // Replace scheme
            foreach ($this->getProxySchemeHeaders() as $header) {
                $headerValue = $request->getHeader($header);
                if ($headerValue) {
                    $request->setScheme(strtolower($headerValue ?? ''));
                    break;
                }
            }

            // Replace IP
            foreach ($this->proxyIPHeaders as $header) {
                $headerValue = $request->getHeader($header);
                if ($headerValue) {
                    $ipHeader = $this->getIPFromHeaderValue($headerValue);
                    if ($ipHeader) {
                        $request->setIP($ipHeader);
                        break;
                    }
                }
            }
        }

        return $delegate($request);
    }

    /**
     * Determine if the current request is coming from a trusted proxy
     *
     * @param HTTPRequest $request
     * @return bool True if the request's source IP is a trusted proxy
     */
    protected function isTrustedProxy(HTTPRequest $request)
    {
        $trustedIPs = $this->getTrustedProxyIPs();

        // Disabled
        if (empty($trustedIPs) || $trustedIPs === 'none') {
            return false;
        }

        // Allow all
        if ($trustedIPs === '*') {
            return true;
        }

        // Validate IP address
        $ip = $request->getIP();
        if ($ip) {
            return IPUtils::checkIP($ip, preg_split('/\s*,\s*/', $trustedIPs ?? ''));
        }

        return false;
    }

    /**
     * Extract an IP address from a header value that has been obtained.
     * Accepts single IP or comma separated string of IPs
     *
     * @param string $headerValue The value from a trusted header
     * @return string The IP address
     */
    protected function getIPFromHeaderValue($headerValue)
    {
        // Sometimes the IP from a load balancer could be "x.x.x.x, y.y.y.y, z.z.z.z"
        // so we need to find the most likely candidate
        $ips = preg_split('/\s*,\s*/', $headerValue ?? '');

        // Prioritise filters
        $filters = [
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            FILTER_FLAG_NO_PRIV_RANGE,
            null
        ];
        foreach ($filters as $filter) {
            // Find best IP
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, $filter ?? 0)) {
                    return $ip;
                }
            }
        }
        return null;
    }
}
