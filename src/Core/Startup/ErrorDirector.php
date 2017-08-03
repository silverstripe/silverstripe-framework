<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Specialised Director class used by ErrorControlChain to handle error and redirect conditions
 *
 * @internal This class is experimental API and may change without warning
 */
class ErrorDirector extends Director
{
    /**
     * Redirect with token if allowed, or null if not allowed
     *
     * @param HTTPRequest $request
     * @param ParameterConfirmationToken $token
     * @param Kernel $kernel
     * @return null|HTTPResponse Redirection response, or null if not able to redirect
     */
    public function handleRequestWithToken(HTTPRequest $request, ParameterConfirmationToken $token, Kernel $kernel)
    {
        Injector::inst()->registerService($request, HTTPRequest::class);

        // Next, check if we're in dev mode, or the database doesn't have any security data, or we are admin
        $reload = function (HTTPRequest $request) use ($token, $kernel) {
            if ($kernel->getEnvironment() === Kernel::DEV || !Security::database_is_ready() || Permission::check('ADMIN')) {
                return $token->reloadWithToken();
            }
            return null;
        };

        try {
            return $this->callMiddleware($request, $reload);
        } finally {
            // Ensure registered request is un-registered
            Injector::inst()->unregisterNamedObject(HTTPRequest::class);
        }
    }
}
