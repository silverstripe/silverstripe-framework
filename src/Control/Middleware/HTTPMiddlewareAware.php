<?php

namespace SilverStripe\Control;

/**
 * Adds middleware support to an object.
 * Provides a Middlewares property and a callMiddleware() callback
 */
trait HTTPMiddlewareAware
{
    /**
     * @var HTTPMiddleware[]
     */
    protected $middlewares = [];

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
        // Allow nulls in the middlewares array to deal with limitations of yml config
        $this->middlewares = array_filter((array)$middlewares);
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
     * @param $request The request to pass to the middlewares and callback
     * @param $last The callback to call after all middlewares
     * @return HTTPResponse
     */
    public function callMiddleware(HTTPRequest $request, callable $last)
    {
        // Reverse middlewares
        $next = $last;
        /** @var HTTPMiddleware $middleware */
        foreach (array_reverse($this->getMiddlewares()) as $middleware) {
            $next = function ($request) use ($middleware, $next) {
                return $middleware->process($request, $next);
            };
        }
        return $next($request);
    }
}
