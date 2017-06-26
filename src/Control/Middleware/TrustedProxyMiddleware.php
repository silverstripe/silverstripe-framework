<?php

namespace SilverStripe\Control;

/**
 * This middleware will rewrite headers that provide IP and host details from an upstream proxy.
 */
class TrustedProxyMiddleware implements HTTPMiddleware
{

    private $trustedProxyIPs = null;

    private $proxyHostHeaders = [
        'X-Forwarded-Host'
    ];

    private $proxyIPHeaders = [
        'Client-IP',
        'X-Forwarded-For'
    ];

    private $proxySchemeHeaders = [
        'X-Forwarded-Protocol',
        'X-Forwarded-Proto',
    ];

    /**
     * Return the comma-separated list of IP ranges that are trusted to provide proxy headers
     *
     * @return string
     */
    public function getTrustedProxyIPs()
    {
        return $this->trustedProxyIPs;
    }

    /**
     * Set the comma-separated list of IP ranges that are trusted to provide proxy headers
     *
     * @param $trustedProxyIPs string
     */
    public function setTrustedProxyIPs($trustedProxyIPs)
    {
        $this->trustedProxyIPs = $trustedProxyIPs;
    }

    /**
     * Return the comma-separated list of headers from which to lookup the hostname
     *
     * @return string
     */
    public function getProxyHostHeaders()
    {
        return $this->proxyHostHeaders;
    }

    /**
     * Set the comma-separated list of headers from which to lookup the hostname
     *
     * @param $proxyHostHeaders string
     */
    public function setProxyHostHeaders($proxyHostHeaders)
    {
        $this->proxyHostHeaders = $proxyHostHeaders;
    }

    /**
     * Return the comma-separated list of headers from which to lookup the client IP
     *
     * @return string
     */
    public function getProxyIPHeaders()
    {
        return $this->proxyIPHeaders;
    }

    /**
     * Set the comma-separated list of headers from which to lookup the client IP
     *
     * @param $proxyIPHeaders string
     */
    public function setProxyIPHeaders($proxyIPHeaders)
    {
        $this->proxyIPHeaders = $proxyIPHeaders;
    }

    /**
     * Return the comma-separated list of headers from which to lookup the client scheme (http/https)
     *
     * @return string
     */
    public function getProxySchemeHeaders()
    {
        return $this->proxySchemeHeaders;
    }

    /**
     * Set the comma-separated list of headers from which to lookup the client scheme (http/https)
     *
     * @param $proxySchemeHeaders string
     */
    public function setProxySchemeHeaders($proxySchemeHeaders)
    {
        $this->proxySchemeHeaders = $proxySchemeHeaders;
    }

    public function process(HTTPRequest $request, callable $delegate)
    {
        // If this is a trust proxy
        if ($this->isTrustedProxy($request)) {
            // Replace host
            foreach ($this->proxyHostHeaders as $header) {
                $hostList = $request->getHeader($header);
                if ($hostList) {
                    $request->setHeader('Host', strtok($hostList, ','));
                    break;
                }
            }

            // Replace scheme
            foreach ($this->proxySchemeHeaders as $header) {
                $scheme = $request->getHeader($header);
                if ($scheme) {
                    $request->setScheme(strtolower($scheme));
                    break;
                }
            }

            // Replace IP
            foreach ($this->proxyIPHeaders as $header) {
                $ipHeader = $this->getIPFromHeaderValue($request->getHeader($header));
                if ($ipHeader) {
                    $request->setIP($ipHeader);
                    break;
                }
            }
        }

        return $delegate($request);
    }

    /**
     * Determine if the current request is coming from a trusted proxy
     *
     * @return boolean True if the request's source IP is a trusted proxy
     */
    protected function isTrustedProxy($request)
    {
        // Disabled
        if (empty($this->trustedProxyIPs) || $trustedIPs === 'none') {
            return false;
        }

        // Allow all
        if ($trustedIPs === '*') {
            return true;
        }

        // Validate IP address
        if ($ip = $request->getIP()) {
            return IPUtils::checkIP($ip, explode(',', $trustedIPs));
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
        if (strpos($headerValue, ',') !== false) {
            //sometimes the IP from a load balancer could be "x.x.x.x, y.y.y.y, z.z.z.z" so we need to find the most
            // likely candidate
            $ips = explode(',', $headerValue);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                } else {
                    return null;
                }
            }
        }
        return $headerValue;
    }
}
