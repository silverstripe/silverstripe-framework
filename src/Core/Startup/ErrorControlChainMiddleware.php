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
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
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

    /**
     * @param HTTPRequest $request
     * @return ConfirmationToken|null
     */
    protected function prepareConfirmationTokenIfRequired(HTTPRequest $request)
    {
        $token = URLConfirmationToken::prepare_tokens(['dev/build'], $request);

        if (!$token) {
            $token = ParameterConfirmationToken::prepare_tokens(
                ['isTest', 'isDev', 'flush'],
                $request
            );
        }

        return $token;
    }

    public function process(HTTPRequest $request, callable $next)
    {
        $result = null;

        // Prepare tokens and execute chain
        $confirmationToken = $this->prepareConfirmationTokenIfRequired($request);
        $chain = new ErrorControlChain();
        $chain
            ->then(function () use ($request, $chain, $confirmationToken, $next, &$result) {
                // If no redirection is necessary then we can disable error supression
                if (!$confirmationToken) {
                    $chain->setSuppression(false);
                }

                try {
                    // Check if a token is requesting a redirect
                    if ($confirmationToken && $confirmationToken->reloadRequired()) {
                        $result = $this->safeReloadWithToken($request, $confirmationToken);
                    } else {
                        // If no reload necessary, process application
                        $result = call_user_func($next, $request);
                    }
                } catch (HTTPResponse_Exception $exception) {
                    $result = $exception->getResponse();
                }
            })
            // Finally if a token was requested but there was an error while figuring out if it's allowed, do it anyway
            ->thenIfErrored(function () use ($confirmationToken) {
                if ($confirmationToken && $confirmationToken->reloadRequiredIfError()) {
                    $result = $confirmationToken->reloadWithToken();
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
        $backURL = $reloadToken->currentURL() . '?' . http_build_query($params);
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
