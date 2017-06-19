<?php

namespace SilverStripe\Core\Startup;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\HTTPMiddleware;

/**
 * Emits response to the browser
 */
class OutputMiddleware implements HTTPMiddleware
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

    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var HTTPResponse $response */
        try {
            $response = call_user_func($delegate, $request);
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
