<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;

/**
 * Allows to bypass requests of a particular HTTP method
 */
class HttpMethodBypass implements Bypass
{
    /**
     * HTTP Methods to bypass
     *
     * @var string[]
     */
    private $methods = [];

    /**
     * Initialize the bypass with HTTP methods
     *
     * @param string[] ...$methods
     */
    public function __construct(...$methods)
    {
        $this->addMethods(...$methods);
    }

    /**
     * Returns the list of methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Add new HTTP methods to the list
     *
     * @param string[] ...$methods
     *
     * return $this
     */
    public function addMethods(...$methods)
    {
        // uppercase and exclude empties
        $methods = array_reduce(
            $methods ?? [],
            function ($result, $method) {
                $method = strtoupper(trim($method ?? ''));
                if (strlen($method ?? '')) {
                    $result[] = $method;
                }
                return $result;
            },
            []
        );

        foreach ($methods as $method) {
            if (!in_array($method, $this->methods ?? [], true)) {
                $this->methods[] = $method;
            }
        }

        return $this;
    }

    /**
     * Returns true if the current process is running in CLI mode
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function checkRequestForBypass(HTTPRequest $request)
    {
        return in_array($request->httpMethod(), $this->methods ?? [], true);
    }
}
