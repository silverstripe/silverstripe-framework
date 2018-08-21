<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Security\RandomGenerator;

/**
 * This is used to protect dangerous GET parameters that need to be detected early in the request
 * lifecycle by generating a one-time-use token & redirecting with that token included in the
 * redirected URL
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 */
class ParameterConfirmationToken extends ConfirmationToken
{
    /**
     * The name of the parameter
     *
     * @var string
     */
    protected $parameterName = null;
    
    /**
     * The parameter given in the main request
     *
     * @var string|null The string value, or null if not provided
     */
    protected $parameter = null;

    /**
     * The parameter given in the backURL
     *
     * @var string|null
     */
    protected $parameterBackURL = null;

    /**
     * @param string $parameterName Name of the querystring parameter to check
     * @param HTTPRequest $request
     */
    public function __construct($parameterName, HTTPRequest $request)
    {
        // Store the parameter name
        $this->parameterName = $parameterName;
        $this->request = $request;

        // Store the parameter value
        $this->parameter = $request->getVar($parameterName);
        $this->parameterBackURL = $this->backURLToken($request);

        // If the token provided is valid, mark it as such
        $token = $request->getVar($parameterName . 'token');
        if ($this->checkToken($token)) {
            $this->token = $token;
        }
    }

    /**
     * Check if this token exists in the BackURL
     *
     * @param HTTPRequest $request
     * @return string Value of token in backurl, or null if not in backurl
     */
    protected function backURLToken(HTTPRequest $request)
    {
        $backURL = $request->getVar('BackURL');
        if (!strstr($backURL, '?')) {
            return null;
        }

        // Filter backURL if it contains the given request parameter
        list(,$query) = explode('?', $backURL);
        parse_str($query, $queryArgs);
        $name = $this->getName();
        if (isset($queryArgs[$name])) {
            return $queryArgs[$name];
        }
        return null;
    }

    /**
     * Get the name of this token
     *
     * @return string
     */
    public function getName()
    {
        return $this->parameterName;
    }

    /**
     * Is the parameter requested?
     * ?parameter and ?parameter=1 are both considered requested
     *
     * @return bool
     */
    public function parameterProvided()
    {
        return $this->parameter !== null;
    }

    /**
     * Is the parmeter requested in a BackURL param?
     *
     * @return bool
     */
    public function existsInReferer()
    {
        return $this->parameterBackURL !== null;
    }

    public function reloadRequired()
    {
        return $this->parameterProvided() && !$this->tokenProvided();
    }

    public function reloadRequiredIfError()
    {
        // Don't reload if token exists
        return $this->reloadRequired() || $this->existsInReferer();
    }
    
    public function suppress()
    {
        unset($_GET[$this->parameterName]);
        $this->request->offsetUnset($this->parameterName);
    }

    public function params($includeToken = true)
    {
        $params = array(
            $this->parameterName => $this->parameter,
        );
        if ($includeToken) {
            $params[$this->parameterName . 'token'] = $this->genToken();
        }
        return $params;
    }
    
    protected function redirectURL()
    {
        // If url is encoded via BackURL, defer to home page (prevent redirect to form action)
        if ($this->existsInReferer() && !$this->parameterProvided()) {
            $url = BASE_URL ?: '/';
            $params = $this->params();
        } else {
            $url = $this->currentURL();
            $params = array_merge($this->request->getVars(), $this->params());
        }

        // Merge get params with current url
        return Controller::join_links($url, '?' . http_build_query($params));
    }
}
