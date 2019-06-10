<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;

/**
 * This is used to protect dangerous URLs that need to be detected early in the request lifecycle
 * by generating a one-time-use token & redirecting with that token included in the redirected URL
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 *
 * @deprecated 5.0 To be removed in SilverStripe 5.0
 */
class URLConfirmationToken extends AbstractConfirmationToken
{
    /**
     * @var string
     */
    protected $urlToCheck;

    /**
     * @var string
     */
    protected $currentURL;

    /**
     * @var string
     */
    protected $tokenParameterName;

    /**
     * @var bool
     */
    protected $urlExistsInBackURL;

    /**
     * @param string $urlToCheck URL to check
     * @param HTTPRequest $request
     */
    public function __construct($urlToCheck, HTTPRequest $request)
    {
        $this->urlToCheck = $urlToCheck;
        $this->request = $request;
        $this->currentURL = $request->getURL(false);

        $this->tokenParameterName = preg_replace('/[^a-z0-9]/i', '', $urlToCheck) . 'token';
        $this->urlExistsInBackURL = $this->getURLExistsInBackURL($request);

        // If the token provided is valid, mark it as such
        $token = $request->getVar($this->tokenParameterName);
        if ($this->checkToken($token)) {
            $this->token = $token;
        }
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    protected function getURLExistsInBackURL(HTTPRequest $request)
    {
        $backURL = ltrim($request->getVar('BackURL'), '/');
        return (strpos($backURL, $this->urlToCheck) === 0);
    }

    /**
     * @return bool
     */
    protected function urlMatches()
    {
        return ($this->currentURL === $this->urlToCheck);
    }

    /**
     * @return string
     */
    public function getURLToCheck()
    {
        return $this->urlToCheck;
    }

    /**
     * @return bool
     */
    public function urlExistsInBackURL()
    {
        return $this->urlExistsInBackURL;
    }

    public function reloadRequired()
    {
        return $this->urlMatches() && !$this->tokenProvided();
    }

    public function reloadRequiredIfError()
    {
        return $this->reloadRequired() || $this->urlExistsInBackURL();
    }

    public function suppress()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $this->request->setURL('/');
    }

    public function params($includeToken = true)
    {
        $params = [];
        if ($includeToken) {
            $params[$this->tokenParameterName] = $this->genToken();
        }

        return $params;
    }

    public function currentURL()
    {
        return Controller::join_links(Director::baseURL(), $this->currentURL);
    }

    public function getRedirectUrlBase()
    {
        return ($this->urlExistsInBackURL && !$this->urlMatches()) ? Director::baseURL() : $this->currentURL();
    }

    public function getRedirectUrlParams()
    {
        return ($this->urlExistsInBackURL && !$this->urlMatches())
            ? $this->params()
            : array_merge($this->request->getVars(), $this->params());
    }

    protected function redirectURL()
    {
        $query = http_build_query($this->getRedirectUrlParams());
        return Controller::join_links($this->getRedirectUrlBase(), '?' . $query);
    }
}
