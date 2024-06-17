<?php

namespace SilverStripe\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

/**
 * Provides an interface to HTTP basic authentication.
 *
 * This utility class can be used to secure any request processed by SilverStripe with basic authentication.
 * To do so, {@link BasicAuth::requireLogin()} from your Controller's init() method or action handler method.
 *
 * It also has a function to protect your entire site.  See {@link BasicAuth::protect_entire_site()}
 * for more information. You can control this setting on controller-level by using {@link Controller->basicAuthEnabled}.
 *
 * CAUTION: Basic Auth is an oudated security measure which passes credentials without encryption over the network.
 * It is considered insecure unless this connection itself is secured (via HTTPS).
 * It also doesn't prevent access to web requests which aren't handled via SilverStripe (e.g. published assets).
 * Consider using additional authentication and authorisation measures to secure access (e.g. IP whitelists).
 */
class BasicAuth
{
    use Configurable;

    /**
     * Env var to set to enable basic auth
     */
    const USE_BASIC_AUTH = 'SS_USE_BASIC_AUTH';

    /**
     * Default permission code
     */
    const AUTH_PERMISSION = 'ADMIN';

    /**
     * @config
     * @var Boolean Flag set by {@link BasicAuth::protect_entire_site()}
     */
    private static $entire_site_protected = false;

    /**
     * Set to true to ignore in CLI mode
     *
     * @var bool
     */
    private static $ignore_cli = true;

    /**
     * @config
     * @var String|array Holds a {@link Permission} code that is required
     * when calling {@link protect_site_if_necessary()}. Set this value through
     * {@link protect_entire_site()}.
     */
    private static $entire_site_protected_code = 'ADMIN';

    /**
     * @config
     * @var String Message that shows in the authentication box.
     * Set this value through {@link protect_entire_site()}.
     */
    private static $entire_site_protected_message = 'SilverStripe test website. Use your CMS login.';

    /**
     * Require basic authentication.  Will request a username and password if none is given.
     *
     * Used by {@link Controller::init()}.
     *
     * @param HTTPRequest $request
     * @param string $realm
     * @param string|array $permissionCode Optional
     * @param boolean $tryUsingSessionLogin If true, then the method with authenticate against the
     *  session log-in if those credentials are disabled.
     * @return bool|Member
     * @throws HTTPResponse_Exception
     */
    public static function requireLogin(
        HTTPRequest $request,
        $realm,
        $permissionCode = null,
        $tryUsingSessionLogin = true
    ) {
        if ((Director::is_cli() && static::config()->get('ignore_cli'))) {
            return true;
        }

        $member = null;

        try {
            if ($request->getHeader('PHP_AUTH_USER') && $request->getHeader('PHP_AUTH_PW')) {
                $authenticators = Security::singleton()->getApplicableAuthenticators(Authenticator::LOGIN);

                foreach ($authenticators as $name => $authenticator) {
                    $member = $authenticator->authenticate([
                        'Email' => $request->getHeader('PHP_AUTH_USER'),
                        'Password' => $request->getHeader('PHP_AUTH_PW'),
                    ], $request);
                    if ($member instanceof Member) {
                        break;
                    }
                }
            }
        } catch (DatabaseException $e) {
            // Database isn't ready, let people in
            return true;
        }

        if (!$member && $tryUsingSessionLogin) {
            $member = Security::getCurrentUser();
        }

        // If we've failed the authentication mechanism, then show the login form
        if (!$member) {
            $response = new HTTPResponse(null, 401);
            $response->addHeader('WWW-Authenticate', "Basic realm=\"$realm\"");

            if ($request->getHeader('PHP_AUTH_USER')) {
                $response->setBody(
                    _t(
                        'SilverStripe\\Security\\BasicAuth.ERRORNOTREC',
                        "That username / password isn't recognised"
                    )
                );
            } else {
                $response->setBody(
                    _t(
                        'SilverStripe\\Security\\BasicAuth.ENTERINFO',
                        'Please enter a username and password.'
                    )
                );
            }

            // Exception is caught by RequestHandler->handleRequest() and will halt further execution
            $e = new HTTPResponse_Exception(null, 401);
            $e->setResponse($response);
            throw $e;
        }

        if ($permissionCode && !Permission::checkMember($member->ID, $permissionCode)) {
            $response = new HTTPResponse(null, 401);
            $response->addHeader('WWW-Authenticate', "Basic realm=\"$realm\"");

            if ($request->getHeader('PHP_AUTH_USER')) {
                $response->setBody(
                    _t(
                        'SilverStripe\\Security\\BasicAuth.ERRORNOTADMIN',
                        'That user is not an administrator.'
                    )
                );
            }

            // Exception is caught by RequestHandler->handleRequest() and will halt further execution
            $e = new HTTPResponse_Exception(null, 401);
            $e->setResponse($response);
            throw $e;
        }

        return $member;
    }

    /**
     * Enable protection of all requests handed by SilverStripe with basic authentication.
     *
     * This log-in uses the Member database for authentication, but doesn't interfere with the
     * regular log-in form. This can be useful for test sites, where you want to hide the site
     * away from prying eyes, but still be able to test the regular log-in features of the site.
     *
     * You can also enable this feature by adding this line to your .env. Set this to a permission
     * code you wish to require: `SS_USE_BASIC_AUTH=ADMIN`
     *
     * CAUTION: Basic Auth is an oudated security measure which passes credentials without encryption over the network.
     * It is considered insecure unless this connection itself is secured (via HTTPS).
     * It also doesn't prevent access to web requests which aren't handled via SilverStripe (e.g. published assets).
     * Consider using additional authentication and authorisation measures to secure access (e.g. IP whitelists).
     *
     * @param boolean $protect Set this to false to disable protection.
     * @param string $code {@link Permission} code that is required from the user.
     *  Defaults to "ADMIN". Set to NULL to just require a valid login, regardless
     *  of the permission codes a user has.
     * @param string $message
     */
    public static function protect_entire_site($protect = true, $code = BasicAuth::AUTH_PERMISSION, $message = null)
    {
        static::config()
            ->set('entire_site_protected', $protect)
            ->set('entire_site_protected_code', $code);
        if ($message) {
            static::config()->set('entire_site_protected_message', $message);
        }
    }

    /**
     * Call {@link BasicAuth::requireLogin()} if {@link BasicAuth::protect_entire_site()} has been called.
     * This is a helper function used by {@link Controller::init()}.
     *
     * If you want to enabled protection (rather than enforcing it),
     * please use {@link protect_entire_site()}.
     *
     * @param HTTPRequest|null $request
     * @throws HTTPResponse_Exception
     */
    public static function protect_site_if_necessary(HTTPRequest $request = null)
    {
        $config = static::config();

        // Check if site is protected
        if ($config->get('entire_site_protected')) {
            $permissionCode = $config->get('entire_site_protected_code');
        } elseif (Environment::getEnv(BasicAuth::USE_BASIC_AUTH)) {
            // Convert legacy 1 / true to ADMIN permissions
            $permissionCode = Environment::getEnv(BasicAuth::USE_BASIC_AUTH);
            if (!is_string($permissionCode) || is_numeric($permissionCode)) {
                $permissionCode = BasicAuth::AUTH_PERMISSION;
            }
        } else {
            // Not enabled
            return;
        }

        // Get request
        if (!$request && Injector::inst()->has(HTTPRequest::class)) {
            $request = Injector::inst()->get(HTTPRequest::class);
        }

        // Require login
        static::requireLogin(
            $request,
            $config->get('entire_site_protected_message'),
            $permissionCode,
            false
        );
    }
}
