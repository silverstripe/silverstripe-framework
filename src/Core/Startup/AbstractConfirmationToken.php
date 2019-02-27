<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Security\RandomGenerator;

/**
 * Shared functionality for token-based authentication of potentially dangerous URLs or query
 * string parameters
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 *
 * @deprecated 5.0 To be removed in SilverStripe 5.0
 */
abstract class AbstractConfirmationToken
{
    /**
     * @var HTTPRequest
     */
    protected $request = null;

    /**
     * The validated and checked token for this parameter
     *
     * @var string|null A string value, or null if either not provided or invalid
     */
    protected $token = null;

    /**
     * Given a list of token names, suppress all tokens that have not been validated, and
     * return the non-validated token with the highest priority
     *
     * @param array $keys List of token keys in ascending priority (low to high)
     * @param HTTPRequest $request
     * @return static The token container for the unvalidated $key given with the highest priority
     */
    public static function prepare_tokens($keys, HTTPRequest $request)
    {
        $target = null;
        foreach ($keys as $key) {
            $token = new static($key, $request);
            // Validate this token
            if ($token->reloadRequired() || $token->reloadRequiredIfError()) {
                $token->suppress();
                $target = $token;
            }
        }
        return $target;
    }

    /**
     * Generate a local filesystem path to store a token
     *
     * @param $token
     * @return string
     */
    protected function pathForToken($token)
    {
        return TEMP_PATH . DIRECTORY_SEPARATOR . 'token_' . preg_replace('/[^a-z0-9]+/', '', $token);
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

        return $content === $token;
    }

    /**
     * Get redirect url, excluding querystring
     *
     * @return string
     */
    public function currentURL()
    {
        return Controller::join_links(Director::baseURL(), $this->request->getURL(false));
    }

    /**
     * Forces a reload of the request with the token included
     *
     * @return HTTPResponse
     */
    public function reloadWithToken()
    {
        $location = $this->redirectURL();
        $locationJS = Convert::raw2js($location);
        $locationATT = Convert::raw2att($location);
        $body = <<<HTML
<script>location.href='$locationJS';</script>
<noscript><meta http-equiv="refresh" content="0; url=$locationATT"></noscript>
You are being redirected. If you are not redirected soon, <a href="$locationATT">click here to continue</a>
HTML;

        // Build response
        $result = new HTTPResponse($body);
        $result->redirect($location);
        return $result;
    }

    /**
     * Is this parameter requested without a valid token?
     *
     * @return bool True if the parameter is given without a valid token
     */
    abstract public function reloadRequired();

    /**
     * Check if this token is provided either in the backurl, or directly,
     * but without a token
     *
     * @return bool
     */
    abstract public function reloadRequiredIfError();

    /**
     * Suppress the current parameter for the duration of this request
     */
    abstract public function suppress();

    /**
     * Determine the querystring parameters to include
     *
     * @param bool $includeToken Include the token value?
     * @return array List of querystring parameters, possibly including token parameter
     */
    abstract public function params($includeToken = true);

    /**
     * @return string
     */
    abstract public function getRedirectUrlBase();

    /**
     * @return array
     */
    abstract public function getRedirectUrlParams();

    /**
     * Get redirection URL
     *
     * @return string
     */
    abstract protected function redirectURL();
}
