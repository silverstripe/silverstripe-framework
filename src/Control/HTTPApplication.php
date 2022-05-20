<?php

namespace SilverStripe\Control;

use SilverStripe\Control\Middleware\HTTPMiddlewareAware;
use SilverStripe\Core\Application;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Startup\FlushDiscoverer;
use SilverStripe\Core\Startup\CompositeFlushDiscoverer;
use SilverStripe\Core\Startup\CallbackFlushDiscoverer;
use SilverStripe\Core\Startup\RequestFlushDiscoverer;
use SilverStripe\Core\Startup\ScheduledFlushDiscoverer;
use SilverStripe\Core\Startup\DeployFlushDiscoverer;

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

    /**
     * A custom FlushDiscoverer to be kept here
     *
     * @var FlushDiscoverer
     */
    private $flushDiscoverer = null;

    /**
     * Initialize the application with a kernel instance
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Override the default flush discovery
     *
     * @param FlushDiscoverer $discoverer
     *
     * @return $this
     */
    public function setFlushDiscoverer(FlushDiscoverer $discoverer)
    {
        $this->flushDiscoverer = $discoverer;
        return $this;
    }

    /**
     * Returns the current flush discoverer
     *
     * @param HTTPRequest $request a request to probe for flush parameters
     *
     * @return FlushDiscoverer
     */
    public function getFlushDiscoverer(HTTPRequest $request)
    {
        if ($this->flushDiscoverer) {
            return $this->flushDiscoverer;
        }

        return new CompositeFlushDiscoverer([
            new ScheduledFlushDiscoverer($this->kernel),
            new DeployFlushDiscoverer($this->kernel),
            new RequestFlushDiscoverer($request, $this->getEnvironmentType())
        ]);
    }

    /**
     * Return the current environment type (dev, test or live)
     * Only checks Kernel and Server ENV as we
     * don't have sessions initialized yet
     *
     * @return string
     */
    protected function getEnvironmentType()
    {
        $kernel_env = $this->kernel->getEnvironment();
        $server_env = Environment::getEnv('SS_ENVIRONMENT_TYPE');

        $env = !is_null($kernel_env) ? $kernel_env : $server_env;

        return $env;
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
        $flush = (bool) $this->getFlushDiscoverer($request)->shouldFlush();

        // Ensure boot is invoked
        return $this->execute($request, static function (HTTPRequest $request) {
            return Director::singleton()->handleRequest($request);
        }, $flush);
    }

    /**
     * Safely boot the application and execute the given main action
     *
     * @param HTTPRequest $request
     * @param callable $callback
     * @param bool $flush
     *
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
