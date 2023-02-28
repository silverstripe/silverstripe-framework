<?php

namespace SilverStripe\Control;

use SilverStripe\Control\Middleware\HTTPMiddlewareAware;
use SilverStripe\Core\Application;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Core\Startup\FlushDiscoverer;
use SilverStripe\Core\Startup\CompositeFlushDiscoverer;
use SilverStripe\Core\Startup\CallbackFlushDiscoverer;
use SilverStripe\Core\Startup\RequestFlushDiscoverer;
use SilverStripe\Core\Startup\ScheduledFlushDiscoverer;
use SilverStripe\Core\Startup\DeployFlushDiscoverer;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\TypeCreator;

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

                // This is the earliest point we can do this and guarantee it's hit exactly once per request.
                $this->warnAboutDeprecatedSetups();

                return call_user_func($callback, $request);
            });
        } catch (HTTPResponse_Exception $ex) {
            return $ex->getResponse();
        } finally {
            $this->getKernel()->shutdown();
        }
    }

    /**
     * Trigger deprecation notices for legacy configuration which is deprecated but
     * doesn't have deprecation notices directly on the relevant API
     *
     * Don't remove this method even if it's just a no-op - we'll reuse this mechanism
     * in the future as needed.
     */
    private function warnAboutDeprecatedSetups()
    {
        // TypeCreator is a class unique to GraphQL v3 - we use it in other areas to detect
        // which version is being used.
        if (class_exists(TypeCreator::class)) {
            Deprecation::notice(
                '4.13.0',
                'silverstripe/graphql 3.x is deprecated. Upgrade to 4.x instead.'
                . ' See https://docs.silverstripe.org/en/4/upgrading/upgrading_to_graphql_4/',
                Deprecation::SCOPE_GLOBAL
            );
        }

        // The alternate_public_dir config property is deprecated, but because it's
        // always fetched it'll throw a deprecation warning whether you've set it or not.
        // There are also multiple mechanisms which can result in this bad configuration.
        if (PUBLIC_DIR !== 'public' || Director::publicDir() !== PUBLIC_DIR) {
            Deprecation::notice(
                '4.13.0',
                'Use of a public webroot other than "public" is deprecated.'
                . ' See https://docs.silverstripe.org/en/4/changelogs/4.1.0#public-folder/',
                Deprecation::SCOPE_GLOBAL
            );
        }

        // This change of defaults has no other deprecation notice being emitted currently.
        $project = new Module(BASE_PATH, BASE_PATH);
        if ($project->getResourcesDir() === '') {
            Deprecation::notice(
                '4.13.0',
                'The RESOURCES_DIR constant will change to "_resources" by default.'
                . ' See https://docs.silverstripe.org/en/5/changelogs/5.0.0/#api-general',
                Deprecation::SCOPE_GLOBAL
            );
        }
    }
}
