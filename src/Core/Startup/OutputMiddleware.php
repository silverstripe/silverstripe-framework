<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;

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
        try {
            $response = call_user_func($next);
        } catch (HTTPResponse_Exception $exception) {
            $response = $exception->getResponse();
        }
        if ($response) {
            $response->output();
        } elseif ($this->defaultResponse) {
            echo $this->defaultResponse;
        }
        return $response;
    }
}
