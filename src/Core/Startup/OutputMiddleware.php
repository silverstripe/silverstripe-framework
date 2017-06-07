<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\HTTPResponse;

/**
 * Emits response to the browser
 */
class OutputMiddleware
{
    protected $defaultResponse = null;

    /**
     * Construct output middleware with a default response
     * (prevent WSOD)
     *
     * @param string $defaultResponse Provide default text to echo
     * if no response could be generated
     */
    public function __construct($defaultResponse = null)
    {
        $this->defaultResponse = $defaultResponse;
    }

    public function __invoke(callable $next)
    {
        /** @var HTTPResponse $response */
        $response = call_user_func($next);
        if ($response) {
            $response->output();
        } elseif ($this->defaultResponse) {
            echo $this->defaultResponse;
        }
        return $response;
    }
}
