<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Environment;

/**
 * The default flush discovery implementation
 *
 * - if request has `flush` or URL is `dev/build`
 * - AND in CLI or DEV mode
 * - then flush
 */
class RequestFlushDiscoverer implements FlushDiscoverer
{
    /**
     * Environment type (dev, test or live)
     *
     * @var string
     */
    protected $env;

    /**
     * Active request instance (session is not initialized yet!)
     *
     * @var HTTPRequest
     */
    protected $request;

    /**
     * Initialize it with active Request and Kernel
     *
     * @param HTTPRequest $request instance of the request (session is not initialized yet!)
     * @param string $env Environment type (dev, test or live)
     */
    public function __construct(HTTPRequest $request, $env)
    {
        $this->env = $env;
        $this->request = $request;
    }

    /**
     * Checks whether the request contains any flush indicators
     *
     *
     * @return null|bool flush or don't care
     */
    protected function lookupRequest()
    {
        $request = $this->request;

        $getVar = array_key_exists('flush', $request->getVars() ?? []);
        $devBuild = $request->getURL() === 'dev/build';

        // WARNING!
        // We specifically return `null` and not `false` here so that
        // it does not override other FlushDiscoverers
        return ($getVar || $devBuild) ? true : null;
    }

    /**
     * Checks for permission to flush
     *
     * Startup flush through a request is only allowed
     * to CLI or DEV modes for security reasons
     *
     * @return bool|null true for allow, false for denying, or null if don't care
     */
    protected function isAllowed()
    {
        // WARNING!
        // We specifically return `null` and not `false` here so that
        // it does not override other FlushDiscoverers
        return (Environment::isCli() || $this->env === Kernel::DEV) ? true : null;
    }

    public function shouldFlush()
    {
        if (!$allowed = $this->isAllowed()) {
            return $allowed;
        }

        return $this->lookupRequest();
    }
}
