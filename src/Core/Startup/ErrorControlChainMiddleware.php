<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Application;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Security\Security;

/**
 * Decorates application bootstrapping with errorcontrolchain
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 *
 * @deprecated 5.0 To be removed in SilverStripe 5.0
 */
class ErrorControlChainMiddleware implements HTTPMiddleware
{
    /**
     * @var Application
     */
    protected $application = null;

    /**
     * Whether to keep working (legacy mode)
     *
     * @var bool
     */
    private $legacy;

    /**
     * Build error control chain for an application
     *
     * @param Application $application
     * @param bool $legacy Keep working (legacy mode)
     */
    public function __construct(Application $application, $legacy = false)
    {
        $this->application = $application;
        $this->legacy = $legacy;
        Deprecation::notice('5.0', 'ErrorControlChainMiddleware is deprecated and will be removed completely');
    }

    /**
     * @param HTTPRequest $request
     * @return ConfirmationTokenChain
     */
    protected function prepareConfirmationTokenChain(HTTPRequest $request)
    {
        $chain = new ConfirmationTokenChain();
        $chain->pushToken(new URLConfirmationToken('dev/build', $request));

        foreach (['isTest', 'isDev', 'flush'] as $parameter) {
            $chain->pushToken(new ParameterConfirmationToken($parameter, $request));
        }

        return $chain;
    }

    public function process(HTTPRequest $request, callable $next)
    {
        if (!$this->legacy) {
            return call_user_func($next, $request);
        }

        $result = null;

        // Prepare tokens and execute chain
        $confirmationTokenChain = $this->prepareConfirmationTokenChain($request);
        $errorControlChain = new ErrorControlChain();
        $errorControlChain
            ->then(function () use ($request, $errorControlChain, $confirmationTokenChain, $next, &$result) {
                if ($confirmationTokenChain->suppressionRequired()) {
                    $confirmationTokenChain->suppressTokens();
                } else {
                    // If no redirection is necessary then we can disable error supression
                    $errorControlChain->setSuppression(false);
                }

                try {
                    // Check if a token is requesting a redirect
                    if ($confirmationTokenChain && $confirmationTokenChain->reloadRequired()) {
                        $result = $this->safeReloadWithTokens($request, $confirmationTokenChain);
                    } else {
                        // If no reload necessary, process application
                        $result = call_user_func($next, $request);
                    }
                } catch (HTTPResponse_Exception $exception) {
                    $result = $exception->getResponse();
                }
            })
            // Finally if a token was requested but there was an error while figuring out if it's allowed, do it anyway
            ->thenIfErrored(function () use ($confirmationTokenChain) {
                if ($confirmationTokenChain && $confirmationTokenChain->reloadRequiredIfError()) {
                    try {
                        // Reload requires manual boot
                        $this->getApplication()->getKernel()->boot(false);
                    } finally {
                        // Given we're in an error state here, try to continue even if the kernel boot fails
                        $result = $confirmationTokenChain->reloadWithTokens();
                        $result->output();
                    }
                }
            })
            ->execute();
        return $result;
    }

    /**
     * Reload application with the given token, but only if either the user is authenticated,
     * or authentication is impossible.
     *
     * @param HTTPRequest $request
     * @param ConfirmationTokenChain $confirmationTokenChain
     * @return HTTPResponse
     */
    protected function safeReloadWithTokens(HTTPRequest $request, ConfirmationTokenChain $confirmationTokenChain)
    {
        // Safe reload requires manual boot
        $this->getApplication()->getKernel()->boot(false);

        // Ensure session is started
        $request->getSession()->init($request);

        // Request with ErrorDirector
        $result = ErrorDirector::singleton()->handleRequestWithTokenChain(
            $request,
            $confirmationTokenChain,
            $this->getApplication()->getKernel()
        );
        if ($result) {
            return $result;
        }

        // Fail and redirect the user to the login page
        $params = array_merge($request->getVars(), $confirmationTokenChain->params(false));
        $backURL = $confirmationTokenChain->getRedirectUrlBase() . '?' . http_build_query($params);
        $loginPage = Director::absoluteURL(Security::config()->get('login_url'));
        $loginPage .= "?BackURL=" . urlencode($backURL);
        $result = new HTTPResponse();
        $result->redirect($loginPage);
        return $result;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Application $application
     * @return $this
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
        return $this;
    }
}
