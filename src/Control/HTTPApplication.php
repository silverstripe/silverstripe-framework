<?php

namespace SilverStripe\Control;

use SilverStripe\Control\Middleware\HTTPMiddlewareAware;
use SilverStripe\Core\Application;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPMiddleware;
use SilverStripe\Core\Kernel;

/**
 * Invokes the HTTP application within an ErrorControlChain
 */
class HTTPApplication implements Application
{
    use HTTPMiddlewareAware;

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
        $flush = $request->getVar('flush') || strpos($request->getURL(), 'dev/build') === 0;

        // Ensure boot is invoked
        return $this->execute($request, function (HTTPRequest $request) {
            return Injector::inst()->get(Director::class)->handleRequest($request);
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
