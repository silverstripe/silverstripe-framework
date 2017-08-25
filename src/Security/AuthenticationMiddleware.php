<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\Connect\DatabaseException;

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
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            $this
                ->getAuthenticationHandler()
                ->authenticateRequest($request);
        } catch (ValidationException $e) {
            return new HTTPResponse(
                "Bad log-in details: " . $e->getMessage(),
                400
            );
        } catch (DatabaseException $e) {
            // Database isn't ready, carry on.
        }

        return $delegate($request);
    }
}
