<?php

namespace SilverStripe\Control\Middleware;

use InvalidArgumentException;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * Secures requests by only allowing a whitelist of Host values
 */
class AllowedHostsMiddleware implements HTTPMiddleware
{
    /**
     * List of allowed hosts.
     * Can be ['*'] to allow all hosts and disable the logged warning.
     *
     * @var array
     */
    private $allowedHosts = [];

    /**
     * @return array List of allowed Host header values.
     * Note that both an empty array and ['*'] can be used to allow all hosts.
     */
    public function getAllowedHosts()
    {
        return $this->allowedHosts;
    }

    /**
     * Sets the list of allowed Host header values
     * Can also specify a comma separated list
     *
     * Note that both an empty array and ['*'] can be used to allow all hosts.
     *
     * @param array|string $allowedHosts
     * @return $this
     */
    public function setAllowedHosts($allowedHosts)
    {
        if ($allowedHosts === null) {
            $allowedHosts = [];
        } elseif (is_string($allowedHosts)) {
            $allowedHosts = preg_split('/ *, */', $allowedHosts ?? '');
        }
        if (count($allowedHosts) > 1 && in_array('*', $allowedHosts)) {
            throw new InvalidArgumentException('The wildcard "*" cannot be used in conjunction with actual hosts.');
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
            && $allowedHosts !== ['*']
            && !Director::is_cli()
            && !in_array($request->getHeader('Host'), $allowedHosts ?? [])
        ) {
            return new HTTPResponse('Invalid Host', 400);
        }

        return $delegate($request);
    }
}
