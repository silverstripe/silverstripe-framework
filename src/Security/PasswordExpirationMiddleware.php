<?php declare(strict_types=1);

namespace SilverStripe\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\Connect\DatabaseException;

/**
 * Check if authenticated user has password expired.
 * Depending on the configuration there are following outcomes:
 *   - if the current requested URL whitelisted, then allow to process further
 *   - else if the change password form URL is set, then redirect to it
 *   - else set current user to null (deauthenticate for the current request) and process further
 */
class PasswordExpirationMiddleware implements HTTPMiddleware
{
    use Configurable;

    /**
     * Session key for persisting URL of the password change form
     */
    const SESSION_KEY_REDIRECT = __CLASS__ . '.change password redirect';

    /**
     * Session key for persisting a flag allowing to process the current request
     * without performing password expiration check
     */
    const SESSION_KEY_ALLOW_CURRENT_REQUEST = __CLASS__ . '.allow current request';

    /**
     * List of URL patterns allowed for users to visit where
     * URL starts with the pattern
     *
     * @var string[]
     *
     * @config
     */
    private static $whitelisted_url_startswith = [];

    /**
     * Where users with expired passwords get redirected by default
     * when login form didn't register a custom one with
     * {@see SilverStripe\Security\AuthenticationMiddleware::setRedirect}
     *
     * @var string
     *
     * @config
     */
    private static $default_redirect = null;

    /**
     * The list of mimetypes allowing a redirect to a change password form.
     * By default this is (x)HTML
     *
     * @var string[]
     *
     * @config
     */
    private static $mimetypes_allowing_redirect = [
        '*/*',
        'text/*',
        'text/html',
        'application/xhtml+xml',
        'text/xml',
        'application/xml'
    ];

    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            if ($response = $this->checkForExpiredPassword($request)) {
                return $response;
            }
        } catch (DatabaseException $e) {
            // Database isn't ready, carry on.
        }

        return $delegate($request);
    }

    /**
     * Check if the just authenticated member has the password expired.
     * Returns a response if the current request should not be
     * processed as usual.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse|null
     */
    protected function checkForExpiredPassword(HTTPRequest $request): ?HTTPResponse
    {
        $session = $request->getSession();

        if ($session && $session->get(static::SESSION_KEY_ALLOW_CURRENT_REQUEST)) {
            // allow current request and skip the expiration check, but for only the current
            // request, so we're deleting the flag from the session so it's not affecting other
            // requests.
            // This flag would usually be set from within $handler->authenticateRequest()
            $session->clear(static::SESSION_KEY_ALLOW_CURRENT_REQUEST);

            return null;
        }

        $user = Security::getCurrentUser();
        if ($user && $user->isPasswordExpired()) {
            if ($response = $this->handleExpiredPassword($request)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Check if we have a redirect to a password change form registered
     * and redirect there if possible.
     * Otherwise, deauthenticate the user by resetting it for this request,
     * since we should treat ones with expired passwords as unauthorised.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse|null
     */
    protected function handleExpiredPassword(HTTPRequest $request): ?HTTPResponse
    {
        $session = $request->getSession();

        $sessionRedirectUrl = $session->get(static::SESSION_KEY_REDIRECT);
        $defaultRedirectUrl = static::config()->get('default_redirect');

        if ($sessionRedirectUrl || $defaultRedirectUrl) {
            $redirectUrl = $this->absoluteUrl((string) ($sessionRedirectUrl ?? $defaultRedirectUrl));
        } else {
            $redirectUrl = null;
        }

        if (!$session || !$redirectUrl) {
            Security::setCurrentUser(null);
            return null;
        }

        $currentUrl = $this->absoluteUrl($request->getURL(true));
        if ($currentUrl === $redirectUrl) {
            return null;
        }

        $allowedStartswith = static::config()->get('whitelisted_url_startswith');
        if (is_array($allowedStartswith)) {
            foreach ($allowedStartswith as $pattern) {
                $startswith = $this->absoluteUrl((string) $pattern);

                if (strncmp($currentUrl ?? '', $startswith ?? '', strlen($startswith ?? '')) === 0) {
                    return null;
                }
            }
        }

        return $this->redirectOrForbid($request, $redirectUrl);
    }

    /**
     * Builds an absolute URL for the given path, adds base url
     * if the path configured as absolute
     *
     * @param string $url
     *
     * @return string
     */
    protected static function absoluteUrl($url): string
    {
        if (substr($url ?? '', 0, 1) === '/' && substr($url ?? '', 1, 1) !== '/') {
            // add BASE_URL explicitly if not absolute
            $url = Controller::join_links(Director::absoluteBaseURL(), $url);
        } else {
            $url = Director::absoluteURL((string) $url) ?: Controller::join_links(Director::absoluteBaseURL(), $url);
        }

        if (substr($url ?? '', -1) === '/') {
            $url = substr($url ?? '', 0, -1);
        }

        return $url;
    }

    /**
     * Returns a redirect to the URL if text/html is acceptable, otherwise
     * deauthenticates the current request by Security::setCurrentUser(null)
     *
     * @param HTTPRequest $request
     * @param string $redirectUrl
     *
     * @return HTTPResponse|null
     */
    private function redirectOrForbid(HTTPRequest $request, $redirectUrl): ?HTTPResponse
    {
        $acceptableTypes = $request->getAcceptMimetypes();

        $allowedTypes = static::config()->get('mimetypes_allowing_redirect') ?? [];

        if (count(array_intersect($allowedTypes ?? [], $acceptableTypes)) > 0) {
            $redirectAllowed = true;
        } else {
            //  if browser didn't send the Accept header
            //  with mimetypes, let's redirect anyway
            $redirectAllowed = count($acceptableTypes ?? []) === 0;
        }

        if ($redirectAllowed) {
            $response = new HTTPResponse();
            $response->redirect($redirectUrl);
            return $response;
        }

        Security::setCurrentUser(null);

        return null;
    }

    /**
     * Preserve the password change URL in the session
     * That URL is to be redirected to to force users change expired passwords
     *
     * @param Session $session Session where we persist the redirect URL
     * @param string $url change password form address
     */
    public static function setRedirect(Session $session, $url)
    {
        $session->set(static::SESSION_KEY_REDIRECT, $url);
    }

    /**
     * Allow the current request to be finished without password expiration check
     *
     * @param Session $session Session where we persist the redirect URL
     */
    public static function allowCurrentRequest(Session $session)
    {
        $session->set(static::SESSION_KEY_ALLOW_CURRENT_REQUEST, true);
    }
}
