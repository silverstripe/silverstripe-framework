<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Confirmation;

/**
 * A rule to match a particular URL
 */
class Url implements Rule, Bypass
{
    use PathAware;

    /**
     * The HTTP methods
     *
     * @var HttpMethodBypass
     */
    private $httpMethods;

    /**
     * The list of GET parameters URL should have to match
     *
     * @var array keys are parameter names, values are strings to match or null if any
     */
    private $params = null;

    /**
     * Initialize the rule with the parameters
     *
     * @param string $path url path to check for
     * @param string[]|string|null $httpMethods to match against
     * @param string[]|null $params a list of GET parameters
     */
    public function __construct($path, $httpMethods = null, $params = null)
    {
        $this->setPath($path);
        $this->setParams($params);
        $this->httpMethods = new HttpMethodBypass();

        if (is_array($httpMethods)) {
            $this->addHttpMethods(...$httpMethods);
        } elseif (!is_null($httpMethods)) {
            $this->addHttpMethods($httpMethods);
        }
    }

    /**
     * Add HTTP methods to check against
     *
     * @param string[] ...$methods
     *
     * @return $this
     */
    public function addHttpMethods(...$methods)
    {
        $this->httpMethods->addMethods(...$methods);
        return $this;
    }

    /**
     * Returns HTTP methods to be checked
     *
     * @return string[]
     */
    public function getHttpMethods()
    {
        return $this->httpMethods->getMethods();
    }

    /**
     * Set the GET parameters
     * null to skip parameter check
     *
     * If an array of parameters provided,
     * then URL should contain ALL of them and
     * ONLY them to match. If the values in the list
     * contain strings, those will be checked
     * against parameter values accordingly. Null
     * as a value in the array matches any parameter values.
     *
     * @param string|null $params
     *
     * @return $this
     */
    public function setParams($params = null)
    {
        $this->params = $params;
        return $this;
    }

    public function checkRequestForBypass(HTTPRequest $request)
    {
        return $this->checkRequest($request);
    }

    public function getRequestConfirmationItem(HTTPRequest $request)
    {
        if (!$this->checkRequest($request)) {
            return null;
        }

        $fullPath = $request->getURL(true);
        $token = $this->generateToken($request->httpMethod(), $fullPath);

        return $this->buildConfirmationItem($token, $fullPath);
    }

    /**
     * Match the request against the rules
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function checkRequest(HTTPRequest $request)
    {
        $httpMethods = $this->getHttpMethods();

        if (count($httpMethods ?? []) && !in_array($request->httpMethod(), $httpMethods ?? [], true)) {
            return false;
        }

        if (!$this->checkPath($request->getURL())) {
            return false;
        }

        if (!is_null($this->params)) {
            $getVars = $request->getVars();

            // compare the request parameters with the declared ones
            foreach ($this->params as $key => $val) {
                if (is_null($val)) {
                    $cmp = array_key_exists($key, $getVars ?? []);
                } else {
                    $cmp = isset($getVars[$key]) && $getVars[$key] === strval($val);
                }

                if (!$cmp) {
                    return false;
                }
            }

            // check only declared parameters exist in the request
            foreach ($getVars as $key => $val) {
                if (!array_key_exists($key, $this->params ?? [])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks the given path by the rules and
     * returns true if it is matching
     *
     * @param string $path Path to be checked
     *
     * @return bool
     */
    protected function checkPath($path)
    {
        return $this->getPath() === $this->normalisePath($path);
    }

    /**
     * Generates the confirmation item
     *
     * @param string $token
     * @param string $url
     *
     * @return Confirmation\Item
     */
    protected function buildConfirmationItem($token, $url)
    {
        return new Confirmation\Item(
            $token,
            _t(__CLASS__ . '.CONFIRMATION_NAME', 'URL is protected'),
            _t(__CLASS__ . '.CONFIRMATION_DESCRIPTION', 'The URL is: "{url}"', ['url' => $url])
        );
    }

    /**
     * Generates the unique token depending on the path
     *
     * @param string $httpMethod HTTP method
     * @param string $path URL path
     *
     * @return string
     */
    protected function generateToken($httpMethod, $path)
    {
        return sprintf('%s::%s|%s', static::class, $httpMethod, $path);
    }
}
