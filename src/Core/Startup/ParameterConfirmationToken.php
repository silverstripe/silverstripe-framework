<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Security\RandomGenerator;

/**
 * Class ParameterConfirmationToken
 *
 * When you need to use a dangerous GET parameter that needs to be set before core/Core.php is
 * established, this class takes care of allowing some other code of confirming the parameter,
 * by generating a one-time-use token & redirecting with that token included in the redirected URL
 *
 * WARNING: This class is experimental and designed specifically for use pre-startup in main.php
 * It will likely be heavily refactored before the release of 3.2
 */
class ParameterConfirmationToken
{

    /**
     * The name of the parameter
     *
     * @var string
     */
    protected $parameterName = null;

    /**
     * @var HTTPRequest
     */
    protected $request = null;

    /**
     * The parameter given
     *
     * @var string|null The string value, or null if not provided
     */
    protected $parameter = null;

    /**
     * The validated and checked token for this parameter
     *
     * @var string|null A string value, or null if either not provided or invalid
     */
    protected $token = null;

    protected function pathForToken($token)
    {
        return TEMP_FOLDER.'/token_'.preg_replace('/[^a-z0-9]+/', '', $token);
    }

    /**
     * Generate a new random token and store it
     *
     * @return string Token name
     */
    protected function genToken()
    {
        // Generate a new random token (as random as possible)
        $rg = new RandomGenerator();
        $token = $rg->randomToken('md5');

        // Store a file in the session save path (safer than /tmp, as open_basedir might limit that)
        file_put_contents($this->pathForToken($token), $token);

        return $token;
    }

    /**
     * Validate a token
     *
     * @param string $token
     * @return boolean True if the token is valid
     */
    protected function checkToken($token)
    {
        if (!$token) {
            return false;
        }

        $file = $this->pathForToken($token);
        $content = null;

        if (file_exists($file)) {
            $content = file_get_contents($file);
            unlink($file);
        }

        return $content == $token;
    }

    /**
     * Create a new ParameterConfirmationToken
     *
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

        // If the token provided is valid, mark it as such
        $token = $request->getVar($parameterName.'token');
        if ($this->checkToken($token)) {
            $this->token = $token;
        }
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
     * Is the necessary token provided for this parameter?
     * A value must be provided for the token
     *
     * @return bool
     */
    public function tokenProvided()
    {
        return !empty($this->token);
    }

    /**
     * Is this parameter requested without a valid token?
     *
     * @return bool True if the parameter is given without a valid token
     */
    public function reloadRequired()
    {
        return $this->parameterProvided() && !$this->tokenProvided();
    }

    /**
     * Suppress the current parameter by unsetting it from $_GET
     */
    public function suppress()
    {
        $this->request->offsetUnset($this->parameterName);
    }

    /**
     * Determine the querystring parameters to include
     *
     * @return array List of querystring parameters with name and token parameters
     */
    public function params()
    {
        return array(
            $this->parameterName => $this->parameter,
            $this->parameterName.'token' => $this->genToken()
        );
    }

    /**
     * Get redirect url, excluding querystring
     *
     * @return string
     */
    protected function currentURL()
    {
        return Controller::join_links(
            BASE_URL,
            '/',
            $this->request->getURL(false)
        );
    }

    /**
     * Forces a reload of the request with the token included
     *
     * @return HTTPResponse
     */
    public function reloadWithToken()
    {
        // Merge get params with current url
        $params = array_merge($this->request->getVars(), $this->params());
        $location = Controller::join_links(
            $this->currentURL(),
            '?'.http_build_query($params)
        );
        $locationJS = Convert::raw2js($location);
        $locationATT = Convert::raw2att($location);
        $body = <<<HTML
<script>location.href='$locationJS';</script>
<noscript><meta http-equiv="refresh" content="0; url=$locationATT"></noscript>
You are being redirected. If you are not redirected soon, <a href="$locationATT">click here to continue the flush</a>
HTML;

        // Build response
        $result = new HTTPResponse($body);
        $result->redirect($location);
        return $result;
    }

    /**
     * Given a list of token names, suppress all tokens that have not been validated, and
     * return the non-validated token with the highest priority
     *
     * @param array $keys List of token keys in ascending priority (low to high)
     * @param HTTPRequest $request
     * @return ParameterConfirmationToken The token container for the unvalidated $key given with the highest priority
     */
    public static function prepare_tokens($keys, HTTPRequest $request)
    {
        $target = null;
        foreach ($keys as $key) {
            $token = new ParameterConfirmationToken($key, $request);
            // Validate this token
            if ($token->reloadRequired()) {
                $token->suppress();
                $target = $token;
            }
        }
        return $target;
    }
}
