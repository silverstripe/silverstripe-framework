<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Application;
use SilverStripe\Control\HTTPMiddleware;
use SilverStripe\Core\Kernel;

/**
 * Invokes the HTTP application within an ErrorControlChain
 */
class HTTPApplication implements Application
{
    /**
     * @var HTTPMiddleware[]
     */
    protected $middlewares = [];

    /**
     * @var Kernel
     */
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return HTTPMiddleware[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @param HTTPMiddleware[] $middlewares
     * @return $this
     */
    public function setMiddlewares($middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @param HTTPMiddleware $middleware
     * @return $this
     */
    public function addMiddleware(HTTPMiddleware $middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Call middleware
     *
     * @param HTTPRequest $request
     * @param callable $last Last config to call
     * @return HTTPResponse
     */
    protected function callMiddleware(HTTPRequest $request, $last)
    {
        // Reverse middlewares
        $next = $last;
        /** @var HTTPMiddleware $middleware */
        foreach (array_reverse($this->getMiddlewares()) as $middleware) {
            $next = function ($request) use ($middleware, $next) {
                return $middleware->process($request, $next);
            };
        }
        return call_user_func($next, $request);
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
        $flush = $request->getVar('flush') || strpos($request->getURL(), 'dev/build') === 0;

        // Ensure boot is invoked
        return $this->execute($request, function (HTTPRequest $request) {
            // Start session and execute
            $request->getSession()->init();
            return Director::direct($request);
        }, $flush);
    }

    /**
     * Safely boot the application and execute the given main action
     *
     * @param HTTPRequest $request
     * @param callable $callback
     * @param bool $flush
     * @return HTTPResponse
     */
    public function execute(HTTPRequest $request, callable $callback, $flush = false)
    {
        try {
            return $this->callMiddleware($request, function ($request) use ($callback, $flush) {
                // Pre-request boot
                $this->getKernel()->boot($flush);
                return call_user_func($callback, $request);
            });
        } catch (HTTPResponse_Exception $ex) {
            return $ex->getResponse();
        } finally {
            $this->getKernel()->shutdown();
        }
    }
}
