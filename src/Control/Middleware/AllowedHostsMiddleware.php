<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * Secures requests by only allowing a whitelist of Host values
 */
class AllowedHostsMiddleware implements HTTPMiddleware
{
    /**
     * List of allowed hosts
     *
     * @var array
     */
    private $allowedHosts = [];

    /**
     * @return array List of allowed Host header values
     */
    public function getAllowedHosts()
    {
        return $this->allowedHosts;
    }

    /**
     * Sets the list of allowed Host header values
     * Can also specify a comma separated list
     *
     * @param array|string $allowedHosts
     * @return $this
     */
    public function setAllowedHosts($allowedHosts)
    {
        if (is_string($allowedHosts)) {
            $allowedHosts = preg_split('/ *, */', $allowedHosts ?? '');
        }
        $this->allowedHosts = $allowedHosts;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        $allowedHosts = $this->getAllowedHosts();

        // check allowed hosts
        if ($allowedHosts
            && !Director::is_cli()
            && !in_array($request->getHeader('Host'), $allowedHosts ?? [])
        ) {
            return new HTTPResponse('Invalid Host', 400);
        }

        return $delegate($request);
    }
}
