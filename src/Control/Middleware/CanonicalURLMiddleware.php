<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * Implements the following URL normalisation rules
 *  - redirect basic auth requests to HTTPS
 *  - force WWW, redirect to the subdomain "www."
 *  - force SSL, redirect to https
 *  - force the correct path (with vs without trailing slash)
 */
class CanonicalURLMiddleware implements HTTPMiddleware
{
    use Injectable;
    use Configurable;

    /**
     * If set, the trailing slash configuration set in {@link Controller::add_trailing_slash} is enforced
     * with a redirect.
     */
    protected bool $enforceTrailingSlashConfig = true;

    /**
     * If enforceTrailingSlashConfig is enabled, this is the list of paths that are ignored
     */
    protected array $enforceTrailingSlashConfigIgnorePaths = [
        'admin/',
        'dev/',
    ];

    /**
     * If enforceTrailingSlashConfig is enabled, this is the list of user agents that are ignored
     */
    protected array $enforceTrailingSlashConfigIgnoreUserAgents = [];

    /**
     * Set if we should redirect to WWW
     *
     * @var bool
     */
    protected $forceWWW = false;

    /**
     * Set if we should force SSL
     *
     * @var bool
     */
    protected $forceSSL = false;

    /**
     * Set if we should automatically redirect basic auth requests to HTTPS. A null value (default) will
     * cause this property to return the value of the forceSSL property.
     *
     * @var bool|null
     */
    protected $forceBasicAuthToSSL = null;

    /**
     * Redirect type
     *
     * @var int
     */
    protected $redirectType = 301;

    /**
     * Environment variables this middleware is enabled in, or a fixed boolean flag to
     * apply to all environments. cli is disabled unless present here as `cli`, or set to true
     * to force enabled.
     *
     * @var array|bool
     */
    protected $enabledEnvs = [
        CoreKernel::LIVE
    ];

    /**
     * If forceSSL is enabled, this is the list of patterns that the url must match (at least one)
     *
     * @var array Array of regexps to match against relative url
     */
    protected $forceSSLPatterns = [];

    /**
     * SSL Domain to use
     *
     * @var string
     */
    protected $forceSSLDomain = null;

    public function setEnforceTrailingSlashConfig(bool $value): static
    {
        $this->enforceTrailingSlashConfig = $value;
        return $this;
    }

    public function getEnforceTrailingSlashConfig(): bool
    {
        return $this->enforceTrailingSlashConfig;
    }

    public function setEnforceTrailingSlashConfigIgnorePaths(array $value): static
    {
        $this->enforceTrailingSlashConfigIgnorePaths = $value;
        return $this;
    }

    public function getEnforceTrailingSlashConfigIgnorePaths(): array
    {
        return $this->enforceTrailingSlashConfigIgnorePaths;
    }

    public function setEnforceTrailingSlashConfigIgnoreUserAgents(array $value): static
    {
        $this->enforceTrailingSlashConfigIgnoreUserAgents = $value;
        return $this;
    }

    public function getEnforceTrailingSlashConfigIgnoreUserAgents(): array
    {
        return $this->enforceTrailingSlashConfigIgnoreUserAgents;
    }

    /**
     * @return array
     */
    public function getForceSSLPatterns()
    {
        return $this->forceSSLPatterns ?: [];
    }

    /**
     * @param array $forceSSLPatterns
     * @return $this
     */
    public function setForceSSLPatterns($forceSSLPatterns)
    {
        $this->forceSSLPatterns = $forceSSLPatterns;
        return $this;
    }

    /**
     * @return string
     */
    public function getForceSSLDomain()
    {
        return $this->forceSSLDomain;
    }

    /**
     * @param string $forceSSLDomain
     * @return $this
     */
    public function setForceSSLDomain($forceSSLDomain)
    {
        $this->forceSSLDomain = $forceSSLDomain;
        return $this;
    }

    /**
     * @return bool
     */
    public function getForceWWW()
    {
        return $this->forceWWW;
    }

    /**
     * @param bool $forceWWW
     * @return $this
     */
    public function setForceWWW($forceWWW)
    {
        $this->forceWWW = $forceWWW;
        return $this;
    }

    /**
     * @return bool
     */
    public function getForceSSL()
    {
        return $this->forceSSL;
    }

    /**
     * @param bool $forceSSL
     * @return $this
     */
    public function setForceSSL($forceSSL)
    {
        $this->forceSSL = $forceSSL;
        return $this;
    }

    /**
     * @param bool|null $forceBasicAuth
     * @return $this
     */
    public function setForceBasicAuthToSSL($forceBasicAuth)
    {
        $this->forceBasicAuthToSSL = $forceBasicAuth;
        return $this;
    }

    /**
     * @return bool
     */
    public function getForceBasicAuthToSSL()
    {
        // Check if explicitly set
        if (isset($this->forceBasicAuthToSSL)) {
            return $this->forceBasicAuthToSSL;
        }
        // If not explicitly set, default to on if ForceSSL is on
        return $this->getForceSSL();
    }

    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        // Handle any redirects
        $redirect = $this->getRedirect($request);
        if ($redirect) {
            return $redirect;
        }

        /** @var HTTPResponse $response */
        $response = $delegate($request);
        if ($this->hasBasicAuthPrompt($response)
            && $request->getScheme() !== 'https'
            && $this->getForceBasicAuthToSSL()
        ) {
            return $this->redirectToScheme($request, 'https');
        }

        return $response;
    }

    /**
     * Given request object determine if we should redirect.
     *
     * @param HTTPRequest $request Pre-validated request object
     * @return HTTPResponse|null If a redirect is needed return the response
     */
    protected function getRedirect(HTTPRequest $request)
    {
        // Check global disable
        if (!$this->isEnabled()) {
            return null;
        }

        // Get properties of current request
        $host = $request->getHost();
        $scheme = $request->getScheme();
        $url = strtok(Environment::getEnv('REQUEST_URI'), '?');

        // Check https
        if ($this->requiresSSL($request)) {
            $scheme = 'https';

            // Promote ssl domain if configured
            $host = $this->getForceSSLDomain() ?: $host;
        }

        // Check www.
        if ($this->getForceWWW() && strpos($host ?? '', 'www.') !== 0) {
            $host = "www.{$host}";
        }

        // Check trailing Slash
        if ($this->requiresTrailingSlashRedirect($request, $url)) {
            $url = Controller::normaliseTrailingSlash($url);
        }

        // No-op if no changes
        if ($request->getScheme() === $scheme
            && $request->getHost() === $host
            && strtok(Environment::getEnv('REQUEST_URI'), '?') === $url
        ) {
            return null;
        }

        return $this->redirectToScheme($request, $scheme, $host);
    }

    /**
     * Handles redirection to canonical urls outside of the main middleware chain
     * using HTTPResponseException.
     * Will not do anything if a current HTTPRequest isn't available
     *
     * @param HTTPRequest|null $request Allow HTTPRequest to be used for the base comparison
     * @throws HTTPResponse_Exception
     */
    public function throwRedirectIfNeeded(HTTPRequest $request = null)
    {
        $request = $this->getOrValidateRequest($request);
        if (!$request) {
            return;
        }
        $response = $this->getRedirect($request);
        if ($response) {
            throw new HTTPResponse_Exception($response);
        }
    }

    /**
     * Return a valid request, if one is available, or null if none is available
     *
     * @param HTTPRequest $request
     * @return HTTPRequest|null
     */
    protected function getOrValidateRequest(HTTPRequest $request = null)
    {
        if ($request instanceof HTTPRequest) {
            return $request;
        }
        if (Injector::inst()->has(HTTPRequest::class)) {
            return Injector::inst()->get(HTTPRequest::class);
        }
        return null;
    }

    /**
     * Check if a redirect for SSL is necessary
     *
     * @param HTTPRequest $request
     * @return bool
     */
    protected function requiresSSL(HTTPRequest $request)
    {
        // Check if force SSL is enabled
        if (!$this->getForceSSL()) {
            return false;
        }

        // Already on SSL
        if ($request->getScheme() === 'https') {
            return false;
        }

        // Veto if any existing patterns fail
        $patterns = $this->getForceSSLPatterns();
        if (!$patterns) {
            return true;
        }

        // Filter redirect based on url
        $relativeURL = $request->getURL(true);
        foreach ($patterns as $pattern) {
            if (preg_match($pattern ?? '', $relativeURL ?? '')) {
                return true;
            }
        }

        // No patterns match
        return false;
    }

    /**
     * Check if a redirect for trailing slash is necessary
     */
    protected function requiresTrailingSlashRedirect(HTTPRequest $request, string $url)
    {
        // Get the URL without querystrings or fragment identifiers
        if (strpos($url, '#') !== false) {
            $url = explode('#', $url, 2)[0];
        }
        if (strpos($url, '?') !== false) {
            $url = explode('?', $url, 2)[0];
        }

        // Check if force Trailing Slash is enabled
        if ($this->getEnforceTrailingSlashConfig() !== true) {
            return false;
        }

        $requestPath = $request->getURL();

        // skip if requesting root
        if ($requestPath === '/' || $requestPath === '') {
            return false;
        }

        // Skip if is AJAX request
        if (Director::is_ajax()) {
            return false;
        }

        // Skip if request has a file extension
        if (!empty($request->getExtension())) {
            return false;
        }

        // Skip if any configured ignore paths match
        $paths = (array) $this->getEnforceTrailingSlashConfigIgnorePaths();
        if (!empty($paths)) {
            foreach ($paths as $path) {
                if (str_starts_with(
                    $this->trailingSlashForComparison($requestPath),
                    $this->trailingSlashForComparison($path)
                )) {
                    return false;
                }
            }
        }

        // Skip if any configured ignore user agents match
        $agents = (array) $this->getEnforceTrailingSlashConfigIgnoreUserAgents();
        if (!empty($agents)) {
            if (in_array(
                $request->getHeader('User-Agent'),
                $agents
            )) {
                return false;
            }
        }

        // Already using trailing slash correctly
        $addTrailingSlash = Controller::config()->uninherited('add_trailing_slash');
        $hasTrailingSlash = str_ends_with($url, '/');
        if (($addTrailingSlash && $hasTrailingSlash) || (!$addTrailingSlash && !$hasTrailingSlash)) {
            return false;
        }

        return true;
    }

    /**
     * Ensure a string has a trailing slash to that we can use str_starts_with and compare
     * paths like admin/ with administration/ and get a correct result.
     */
    private function trailingSlashForComparison(string $path): string
    {
        return trim($path, '/') . '/';
    }

    /**
     * @return int
     */
    public function getRedirectType()
    {
        return $this->redirectType;
    }

    /**
     * @param int $redirectType
     * @return $this
     */
    public function setRedirectType($redirectType)
    {
        $this->redirectType = $redirectType;
        return $this;
    }

    /**
     * Get enabled flag, or list of environments to enable in.
     *
     * @return array|bool
     */
    public function getEnabledEnvs()
    {
        return $this->enabledEnvs;
    }

    /**
     * Set enabled flag, or list of environments to enable in.
     * Note: CLI is disabled by default, so `"cli"(string)` or `true(bool)` should be specified if you wish to
     * enable for testing.
     *
     * @param array|bool $enabledEnvs
     * @return $this
     */
    public function setEnabledEnvs($enabledEnvs)
    {
        $this->enabledEnvs = $enabledEnvs;
        return $this;
    }

    /**
     * Ensure this middleware is enabled
     */
    protected function isEnabled()
    {
        // At least one redirect must be enabled
        if (!$this->getForceWWW() && !$this->getForceSSL() && $this->getEnforceTrailingSlashConfig() !== true) {
            return false;
        }

        // Filter by env vars
        $enabledEnvs = $this->getEnabledEnvs();
        if (is_bool($enabledEnvs)) {
            return $enabledEnvs;
        }

        // If CLI, EnabledEnvs must contain CLI
        if (Director::is_cli() && !in_array('cli', $enabledEnvs ?? [])) {
            return false;
        }

        // Check other envs
        return empty($enabledEnvs) || in_array(Director::get_environment_type(), $enabledEnvs ?? []);
    }

    /**
     * Determine whether the executed middlewares have added a basic authentication prompt
     *
     * @param HTTPResponse $response
     * @return bool
     */
    protected function hasBasicAuthPrompt(HTTPResponse $response = null)
    {
        if (!$response) {
            return false;
        }
        return ($response->getStatusCode() === 401 && $response->getHeader('WWW-Authenticate'));
    }

    /**
     * Redirect the current URL to the specified HTTP scheme
     *
     * @param HTTPRequest $request
     * @param string $scheme
     * @param string $host
     * @return HTTPResponse
     */
    protected function redirectToScheme(HTTPRequest $request, $scheme, $host = null)
    {
        if (!$host) {
            $host = $request->getHost();
        }

        $url = Controller::join_links("{$scheme}://{$host}", Director::baseURL(), $request->getURL(true));

        // Force redirect
        $response = HTTPResponse::create();
        $response->redirect($url, $this->getRedirectType());

        return $response;
    }
}
