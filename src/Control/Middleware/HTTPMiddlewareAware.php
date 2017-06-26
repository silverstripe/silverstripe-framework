<?php

<<<<<<< 8438c677e2d5ed76f3e1f2cf7fc1bcb7ab5a57d5
namespace SilverStripe\Control;

/**
 * Adds middleware support to an object.
 * Provides a Middlewares property and a callMiddleware() callback
=======
namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * Allows client objects to process HTTPMiddleware
>>>>>>> API Abstract HTTPMiddleware handler into a trait
 */
trait HTTPMiddlewareAware
{
    /**
     * @var HTTPMiddleware[]
     */
<<<<<<< 8438c677e2d5ed76f3e1f2cf7fc1bcb7ab5a57d5
    protected $middlewares = [];
=======
    private $middlewares = [];
>>>>>>> API Abstract HTTPMiddleware handler into a trait

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
<<<<<<< 8438c677e2d5ed76f3e1f2cf7fc1bcb7ab5a57d5
        // Allow nulls in the middlewares array to deal with limitations of yml config
        $this->middlewares = array_filter((array)$middlewares);
=======
        $this->middlewares = $middlewares;
>>>>>>> API Abstract HTTPMiddleware handler into a trait
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
<<<<<<< 8438c677e2d5ed76f3e1f2cf7fc1bcb7ab5a57d5
     * @param $request The request to pass to the middlewares and callback
     * @param $last The callback to call after all middlewares
     * @return HTTPResponse
     */
    public function callMiddleware(HTTPRequest $request, callable $last)
=======
     * @param HTTPRequest $request
     * @param callable $last Last config to call
     * @return HTTPResponse
     */
    protected function callMiddleware(HTTPRequest $request, callable $last)
>>>>>>> API Abstract HTTPMiddleware handler into a trait
    {
        // Reverse middlewares
        $next = $last;
        /** @var HTTPMiddleware $middleware */
        foreach (array_reverse($this->getMiddlewares()) as $middleware) {
            $next = function ($request) use ($middleware, $next) {
                return $middleware->process($request, $next);
            };
        }
<<<<<<< 8438c677e2d5ed76f3e1f2cf7fc1bcb7ab5a57d5
        return $next($request);
=======
        return call_user_func($next, $request);
>>>>>>> API Abstract HTTPMiddleware handler into a trait
    }
}
