<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\ValidationException;

class AuthenticationMiddleware implements HTTPMiddleware
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
     * @return bool|void
     * @throws HTTPResponse_Exception
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if (Security::database_is_ready()) {
            try {
                $this
                    ->getAuthenticationHandler()
                    ->authenticateRequest($request);
            } catch (ValidationException $e) {
                return new HTTPResponse(
                    "Bad log-in details: " . $e->getMessage(),
                    400
                );
            }
        }

        return $delegate($request);
    }
    }
