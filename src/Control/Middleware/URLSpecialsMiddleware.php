<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\URLSpecialsMiddleware\FlushScheduler;
use SilverStripe\Control\Middleware\URLSpecialsMiddleware\SessionEnvTypeSwitcher;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\RandomGenerator;

/**
 * Check the request for the URL special variables.
 * Performs authorisation, confirmation and actions for some of those.
 *
 * WARNING: Bypasses only disable authorisation and confirmation, but not actions nor redirects
 *
 * The rules are:
 *  - flush GET parameter
 *  - isDev GET parameter
 *  - isTest GET parameter
 *  - dev/build URL
 *
 * @see https://docs.silverstripe.org/en/4/developer_guides/debugging/url_variable_tools/ special variables docs
 *
 * {@inheritdoc}
 */
class URLSpecialsMiddleware extends PermissionAwareConfirmationMiddleware
{
    use FlushScheduler;
    use SessionEnvTypeSwitcher;

    /**
     * Initializes the middleware with the required rules
     */
    public function __construct()
    {
        parent::__construct(
            new ConfirmationMiddleware\GetParameter("flush"),
            new ConfirmationMiddleware\GetParameter("isDev"),
            new ConfirmationMiddleware\GetParameter("isTest")
        );
    }

    /**
     * Looks up for the special flags passed in the request
     * and schedules the changes accordingly for the next request.
     * Returns a redirect to the same page (with a random token) if
     * there are changes introduced by the flags.
     * Returns null if there is no impact introduced by the flags.
     *
     * @param HTTPRequest $request
     *
     * @return null|HTTPResponse redirect to the same url
     */
    public function buildImpactRedirect(HTTPRequest $request)
    {
        $flush = $this->scheduleFlush($request);
        $env_type = $this->setSessionEnvType($request);

        if ($flush || $env_type) {
            // the token only purpose is to invalidate browser/proxy cache
            $request['urlspecialstoken'] = bin2hex(random_bytes(4));

            $result = new HTTPResponse();
            $result->redirect(
                Controller::join_links(
                    Director::baseURL(),
                    $request->getURL(true)
                )
            );
            return $result;
        }
    }

    protected function confirmedEffect(HTTPRequest $request)
    {
        if ($response = $this->buildImpactRedirect($request)) {
            HTTPCacheControlMiddleware::singleton()->disableCache(true);
            return $response;
        }
    }
}
