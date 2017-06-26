<?php

namespace SilverStripe\Control;

/**
 * Secures requests by only allowing a whitelist of Host values
 */
class AllowedHostsMiddleware implements HTTPMiddleware
{

    private $allowedHosts = null;

    /**
     * @return string A comma-separted list of allowed Host header values
     */
    public function getAllowedHosts()
    {
        return $this->allowedHosts;
    }

    /**
     * @param $allowedHosts string A comma-separted list of allowed Host header values
     */
    public function setAllowedHosts($allowedHosts)
    {
        $this->allowedHosts = $allowedHosts;
    }

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if ($this->allowedHosts && !Director::is_cli()) {
            $allowedHosts = preg_split('/ *, */', $this->allowedHosts);

            // check allowed hosts
            if (!in_array($request->getHeader('Host'), $allowedHosts)) {
                return new HTTPResponse('Invalid Host', 400);
            }
        }

        return $delegate($request);
    }
}
