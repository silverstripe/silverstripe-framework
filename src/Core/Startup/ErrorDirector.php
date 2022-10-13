<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Dev\Deprecation;
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
 *
 * @deprecated 4.12.0 Will be removed without equivalent functionality
 */
class ErrorDirector extends Director
{
    /**
     * Redirect with token if allowed, or null if not allowed
     *
     * @param HTTPRequest $request
     * @param ConfirmationTokenChain $confirmationTokenChain
     * @param Kernel $kernel
     * @return null|HTTPResponse Redirection response, or null if not able to redirect
     */
    public function __construct()
    {
        Deprecation::notice('4.12.0', 'Will be removed without equivalent functionality', Deprecation::SCOPE_CLASS);
    }

    public function handleRequestWithTokenChain(
        HTTPRequest $request,
        ConfirmationTokenChain $confirmationTokenChain,
        Kernel $kernel
    ) {
        Injector::inst()->registerService($request, HTTPRequest::class);

        // Next, check if we're in dev mode, or the database doesn't have any security data, or we are admin
        $reload = function (HTTPRequest $request) use ($confirmationTokenChain, $kernel) {
            if ($kernel->getEnvironment() === Kernel::DEV || !Security::database_is_ready() || Permission::check('ADMIN')) {
                return $confirmationTokenChain->reloadWithTokens();
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
