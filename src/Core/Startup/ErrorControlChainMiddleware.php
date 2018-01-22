<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Application;
use SilverStripe\Security\Security;

/**
 * Decorates application bootstrapping with errorcontrolchain
 */
class ErrorControlChainMiddleware implements HTTPMiddleware
{
    /**
     * @var Application
     */
    protected $application = null;

    /**
     * Build error control chain for an application
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function process(HTTPRequest $request, callable $next)
    {
        $result = null;

        // Prepare tokens and execute chain
        $reloadToken = ParameterConfirmationToken::prepare_tokens(
            ['isTest', 'isDev', 'flush'],
            $request
        );
        $chain = new ErrorControlChain();
        $chain
            ->then(function () use ($request, $chain, $reloadToken, $next, &$result) {
                // If no redirection is necessary then we can disable error supression
                if (!$reloadToken) {
                    $chain->setSuppression(false);
                }

                try {
                    // Check if a token is requesting a redirect
                    if ($reloadToken && $reloadToken->reloadRequired()) {
                        $result = $this->safeReloadWithToken($request, $reloadToken);
                    } else {
                        // If no reload necessary, process application
                        $result = call_user_func($next, $request);
                    }
                } catch (HTTPResponse_Exception $exception) {
                    $result = $exception->getResponse();
                }
            })
            // Finally if a token was requested but there was an error while figuring out if it's allowed, do it anyway
            ->thenIfErrored(function () use ($reloadToken) {
                if ($reloadToken && $reloadToken->reloadRequiredIfError()) {
                    $result = $reloadToken->reloadWithToken();
                    $result->output();
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
     * @param ParameterConfirmationToken $reloadToken
     * @return HTTPResponse
     */
    protected function safeReloadWithToken(HTTPRequest $request, $reloadToken)
    {
        // Safe reload requires manual boot
        $this->getApplication()->getKernel()->boot(false);

        // Ensure session is started
        $request->getSession()->init($request);

        // Request with ErrorDirector
        $result = ErrorDirector::singleton()->handleRequestWithToken(
            $request,
            $reloadToken,
            $this->getApplication()->getKernel()
        );
        if ($result) {
            return $result;
        }

        // Fail and redirect the user to the login page
        $params = array_merge($request->getVars(), $reloadToken->params(false));
        $backURL = $request->getURL() . '?' . http_build_query($params);
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
