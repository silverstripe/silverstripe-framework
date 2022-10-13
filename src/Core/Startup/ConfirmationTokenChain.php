<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;

/**
 * A chain of confirmation tokens to be validated on each request. This allows the application to
 * check multiple tokens at once without having to potentially redirect the user for each of them
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 *
 * @deprecated 4.12.0 Will be removed without equivalent functionality
 */
class ConfirmationTokenChain
{
    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * @param AbstractConfirmationToken $token
     */
    public function __construct()
    {
        Deprecation::notice('4.12.0', 'Will be removed without equivalent functionality', Deprecation::SCOPE_CLASS);
    }

    public function pushToken(AbstractConfirmationToken $token)
    {
        $this->tokens[] = $token;
    }

    /**
     * Collect all tokens that require a redirect
     *
     * @return \Generator
     */
    protected function filteredTokens()
    {
        foreach ($this->tokens as $token) {
            if ($token->reloadRequired() || $token->reloadRequiredIfError()) {
                yield $token;
            }
        }
    }

    /**
     * @return bool
     */
    public function suppressionRequired()
    {
        foreach ($this->tokens as $token) {
            if ($token->reloadRequired()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Suppress URLs & GET vars from tokens that require a redirect
     */
    public function suppressTokens()
    {
        foreach ($this->filteredTokens() as $token) {
            $token->suppress();
        }
    }

    /**
     * @return bool
     */
    public function reloadRequired()
    {
        foreach ($this->tokens as $token) {
            if ($token->reloadRequired()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function reloadRequiredIfError()
    {
        foreach ($this->tokens as $token) {
            if ($token->reloadRequiredIfError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $includeToken
     * @return array
     */
    public function params($includeToken = true)
    {
        $params = [];
        foreach ($this->tokens as $token) {
            $params = array_merge($params, $token->params($includeToken));
        }

        return $params;
    }

    /**
     * Fetch the URL we want to redirect to, excluding query string parameters. This may
     * be the same URL (with a token to be added outside this method), or to a different
     * URL if the current one has been suppressed
     *
     * @return string
     */
    public function getRedirectUrlBase()
    {
        // URLConfirmationTokens may alter the URL to suppress the URL they're protecting,
        // so we need to ensure they're inspected last and therefore take priority
        $tokens = iterator_to_array($this->filteredTokens(), false);
        usort($tokens, function ($a, $b) {
            return ($a instanceof URLConfirmationToken) ? 1 : 0;
        });

        $urlBase = Director::baseURL();
        foreach ($tokens as $token) {
            $urlBase = $token->getRedirectUrlBase();
        }

        return $urlBase;
    }

    /**
     * Collate GET vars from all token providers that need to apply a token
     *
     * @return array
     */
    public function getRedirectUrlParams()
    {
        $params = $_GET;
        unset($params['url']); // CLIRequestBuilder may add this
        foreach ($this->filteredTokens() as $token) {
            $params = array_merge($params, $token->params());
        }

        return $params;
    }

    /**
     * @return string
     */
    protected function redirectURL()
    {
        $params = http_build_query($this->getRedirectUrlParams() ?? []);
        return Controller::join_links($this->getRedirectUrlBase(), '?' . $params);
    }

    /**
     * @return HTTPResponse
     */
    public function reloadWithTokens()
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
}
