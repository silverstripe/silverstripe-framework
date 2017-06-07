<?php

namespace SilverStripe\Core;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * Invokes the HTTP application within an ErrorControlChain
 */
class HTTPApplication implements Application
{
    /**
     * @var callable[]
     */
    protected $middlewares = [];

    /**
     * @return callable[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @param callable[] $middlewares
     * @return $this
     */
    public function setMiddlewares($middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @param callable $middleware
     * @return $this
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Call middleware
     *
     * @param callable $last Last config to call
     * @return HTTPResponse
     */
    protected function callMiddleware($last)
    {
        // Reverse middlewares
        $next = $last;
        foreach (array_reverse($this->getMiddlewares()) as $middleware) {
            $next = function () use ($middleware, $next) {
                return call_user_func($middleware, $next);
            };
        }
        return call_user_func($next);
    }

    /**
     * @var Kernel
     */
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Get the kernel for this application
     *
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Handle the given HTTP request
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handle(HTTPRequest $request)
    {
        // Ensure boot is invoked
        return $this->execute(function () use ($request) {
            // Start session and execute
            $request->getSession()->init();
            return Director::direct($request);
        });
    }

    /**
     * Safely boot the application and execute the given main action
     *
     * @param callable $callback
     * @return HTTPResponse
     */
    public function execute(callable $callback)
    {
        return $this->callMiddleware(function () use ($callback) {
            // Pre-request boot
            $this->getKernel()->boot();
            return call_user_func($callback);
        });
    }
}
