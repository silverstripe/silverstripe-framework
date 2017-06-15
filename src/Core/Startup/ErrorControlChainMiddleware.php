<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Application;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Decorates application bootstrapping with errorcontrolchain
 */
class ErrorControlChainMiddleware
{
    /**
     * @var Application
     */
    protected $application = null;

    /**
     * @var HTTPRequest
     */
    protected $request = null;

    /**
     * Build error control chain for an application
     *
     * @param Application $application
     * @param HTTPRequest $request
     */
    public function __construct(Application $application, HTTPRequest $request)
    {
        $this->application = $application;
        $this->request = $request;
    }

    /**
     * @param callable $next
     * @return HTTPResponse
     */
    public function __invoke(callable $next)
    {
        $result = null;

        // Prepare tokens and execute chain
        $reloadToken = ParameterConfirmationToken::prepare_tokens(
            ['isTest', 'isDev', 'flush'],
            $this->getRequest()
        );
        $chain = new ErrorControlChain();
        $chain
            ->then(function () use ($chain, $reloadToken, $next, &$result) {
                // If no redirection is necessary then we can disable error supression
                if (!$reloadToken) {
                    $chain->setSuppression(false);
                }

                try {
                    // Check if a token is requesting a redirect
                    if ($reloadToken) {
                        $result = $this->safeReloadWithToken($reloadToken);
                    } else {
                        // If no reload necessary, process application
                        $result = call_user_func($next);
                    }
                } catch (HTTPResponse_Exception $exception) {
                    $result = $exception->getResponse();
                }
            })
            // Finally if a token was requested but there was an error while figuring out if it's allowed, do it anyway
            ->thenIfErrored(function () use ($reloadToken, &$result) {
                if ($reloadToken) {
                    $result = $reloadToken->reloadWithToken();
                }
            })
            ->execute();
        return $result;
    }

    /**
     * Reload application with the given token, but only if either the user is authenticated,
     * or authentication is impossible.
     *
     * @param ParameterConfirmationToken $reloadToken
     * @return HTTPResponse
     */
    protected function safeReloadWithToken($reloadToken)
    {
        // Safe reload requires manual boot
        $this->getApplication()->getKernel()->boot(false);

        // Ensure session is started
        $this->getRequest()->getSession()->init();

        // Next, check if we're in dev mode, or the database doesn't have any security data, or we are admin
        if (Director::isDev() || !Security::database_is_ready() || Permission::check('ADMIN')) {
            return $reloadToken->reloadWithToken();
        }

        // Fail and redirect the user to the login page
        $loginPage = Director::absoluteURL(Security::config()->get('login_url'));
        $loginPage .= "?BackURL=" . urlencode($this->getRequest()->getURL());
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

    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param HTTPRequest $request
     * @return $this
     */
    public function setRequest(HTTPRequest $request)
    {
        $this->request = $request;
        return $this;
    }
}
