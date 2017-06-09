<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestFilter;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataModel;
use SilverStripe\ORM\ValidationException;

class AuthenticationRequestFilter implements RequestFilter
{
    use Configurable;

    /**
     * @var AuthenticationHandler
     */
    protected $authenticationHandler;

    /**
     * @return AuthenticationHandler
     */
    public function getAuthenticationHandler()
    {
        return $this->authenticationHandler;
    }

    /**
     * @param AuthenticationHandler $authenticationHandler
     * @return $this
     */
    public function setAuthenticationHandler(AuthenticationHandler $authenticationHandler)
    {
        $this->authenticationHandler = $authenticationHandler;
        return $this;
    }

    /**
     * Identify the current user from the request
     *
     * @param HTTPRequest $request
     * @param Session $session
     * @param DataModel $model
     * @return bool|void
     * @throws HTTPResponse_Exception
     */
    public function preRequest(HTTPRequest $request, Session $session, DataModel $model)
    {
        try {
            $this
                ->getAuthenticationHandler()
                ->authenticateRequest($request);
        } catch (ValidationException $e) {
            throw new HTTPResponse_Exception(
                "Bad log-in details: " . $e->getMessage(),
                400
            );
        }
    }

    /**
     * No-op
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     * @param DataModel $model
     * @return bool|void
     */
    public function postRequest(HTTPRequest $request, HTTPResponse $response, DataModel $model)
    {
    }
}
