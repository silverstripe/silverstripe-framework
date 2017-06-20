<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Environment;

class HTTPRequestBuilder
{
    /**
     * Create HTTPRequest instance from the current environment variables.
     * May throw errors if request is invalid.
     *
     * @throws HTTPResponse_Exception
     * @return HTTPRequest
     */
    public static function createFromEnvironment()
    {
        // Health-check prior to creating environment
        return static::createFromVariables(Environment::getVariables(), @file_get_contents('php://input'));
    }

    /**
     * Build HTTPRequest from given variables
     *
     * @param array $variables
     * @param string $input Request body
     * @return HTTPRequest
     */
    public static function createFromVariables(array $variables, $input)
    {
        $variables = static::cleanEnvironment($variables);

        // Strip `url` out of querystring
        $url = $variables['_GET']['url'];
        unset($variables['_GET']['url']);

        // Build request
        $request = new HTTPRequest(
            $variables['_SERVER']['REQUEST_METHOD'],
            $url,
            $variables['_GET'],
            $variables['_POST'],
            $input
        );

        // Add headers
        $headers = static::extractRequestHeaders($variables['_SERVER']);
        foreach ($headers as $header => $value) {
            $request->addHeader($header, $value);
        }

        // Initiate an empty session - doesn't initialize an actual PHP session (see HTTPApplication)
        $session = new Session(isset($variables['_SESSION']) ? $variables['_SESSION'] : null);
        $request->setSession($session);

        return $request;
    }

    /**
     * Takes a $_SERVER data array and extracts HTTP request headers.
     *
     * @param array $server
     *
     * @return array
     */
    protected static function extractRequestHeaders(array $server)
    {
        $headers = array();
        foreach ($server as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $key = substr($key, 5);
                $key = strtolower(str_replace('_', ' ', $key));
                $key = str_replace(' ', '-', ucwords($key));
                $headers[$key] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * Clean up HTTP global vars for $_GET / $_REQUEST prior to bootstrapping
     * Will also populate the $_GET['url'] var safely
     *
     * @param array $variables
     * @return array Cleaned variables
     */
    protected static function cleanEnvironment(array $variables)
    {
        // IIS will sometimes generate this.
        if (!empty($variables['_SERVER']['HTTP_X_ORIGINAL_URL'])) {
            $variables['_SERVER']['REQUEST_URI'] = $variables['_SERVER']['HTTP_X_ORIGINAL_URL'];
        }

        // Override REQUEST_METHOD
        if (isset($variables['_SERVER']['X-HTTP-Method-Override'])) {
            $variables['_SERVER']['REQUEST_METHOD'] = $variables['_SERVER']['X-HTTP-Method-Override'];
        }

        // Prevent injection of url= querystring argument by prioritising any leading url argument
        if (isset($variables['_SERVER']['QUERY_STRING']) &&
            preg_match('/^(?<url>url=[^&?]*)(?<query>.*[&?]url=.*)$/', $variables['_SERVER']['QUERY_STRING'], $results)
        ) {
            $queryString = $results['query'].'&'.$results['url'];
            parse_str($queryString, $variables['_GET']);
        }

        // Decode url from REQUEST_URI if not passed via $_GET['url']
        if (!isset($variables['_GET']['url'])) {
            $url = $variables['_SERVER']['REQUEST_URI'];

            // Querystring args need to be explicitly parsed
            if (strpos($url, '?') !== false) {
                list($url, $queryString) = explode('?', $url, 2);
                parse_str($queryString);
            }

            // Ensure $_GET['url'] is set
            $variables['_GET']['url'] = urldecode($url);
        }

        // Remove base folders from the URL if webroot is hosted in a subfolder
        if (substr(strtolower($variables['_GET']['url']), 0, strlen(BASE_URL)) === strtolower(BASE_URL)) {
            $variables['_GET']['url'] = substr($variables['_GET']['url'], strlen(BASE_URL));
        }

        // Merge $_FILES into $_POST
        $variables['_POST'] = array_merge((array)$variables['_POST'], (array)$variables['_FILES']);

        // Merge $_POST, $_GET, and $_COOKIE into $_REQUEST
        $variables['_REQUEST'] = array_merge(
            (array)$variables['_GET'],
            (array)$variables['_POST'],
            (array)$variables['_COOKIE']
        );

        return $variables;
    }
}
