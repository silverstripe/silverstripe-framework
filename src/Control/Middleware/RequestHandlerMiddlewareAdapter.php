<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Dev\Debug;

/**
 * Decorates a request handler with the HTTP Middleware pattern
 */
class RequestHandlerMiddlewareAdapter extends RequestHandler
{
    use HTTPMiddlewareAware;

    /**
     * @var RequestHandler
     */
    protected $requestHandler = null;

    public function __construct(RequestHandler $handler = null): void
    {
        if ($handler) {
            $this->setRequestHandler($handler);
        }
        parent::__construct();
    }

    public function Link($action = null)
    {
        return $this->getRequestHandler()->Link($action);
    }

    /**
     * @return RequestHandler
     */
    public function getRequestHandler(): SilverStripe\Control\Tests\DirectorTest\TestController
    {
        return $this->requestHandler;
    }

    /**
     * @param RequestHandler $requestHandler
     * @return $this
     */
    public function setRequestHandler(RequestHandler $requestHandler): SilverStripe\Control\Middleware\RequestHandlerMiddlewareAdapter
    {
        $this->requestHandler = $requestHandler;
        return $this;
    }

    public function handleRequest(HTTPRequest $request): SilverStripe\Control\HTTPResponse
    {
        return $this->callMiddleware($request, function (HTTPRequest $request) {
            $this->setRequest($request);
            return $this->getRequestHandler()->handleRequest($request);
        });
    }
}
