<?php

namespace SilverStripe\Core\Startup;

/**
 * Handle a callable object as a discoverer
 */
class CallbackFlushDiscoverer implements FlushDiscoverer
{
    /**
     * Callback incapsulating the discovery logic
     *
     * @var Callable
     */
    protected $callback;

    /**
     * Construct the discoverer from a callback
     *
     * @param Callable $callback returning FlushDiscoverer response or a timestamp
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function shouldFlush()
    {
        return call_user_func($this->callback);
    }
}
