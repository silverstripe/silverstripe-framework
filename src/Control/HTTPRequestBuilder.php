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
        // Clean and update live global variables
        $variables = static::cleanEnvironment(Environment::getVariables());
        Environment::setVariables($variables); // Currently necessary for SSViewer, etc to work

        // Health-check prior to creating environment
        return static::createFromVariables($variables, @file_get_contents('php://input'));
    }

    /**
     * Build HTTPRequest from given variables
     *
     * @param array $variables
     * @param string $input Request body
     * @param string|null $url Provide specific url (relative to base)
     * @return HTTPRequest
     */
    public static function createFromVariables(array $variables, $input, $url = null)
    {
        // Infer URL from REQUEST_URI unless explicitly provided
        if (!isset($url)) {
            // Remove query parameters (they're retained separately through $server['_GET']
            $url = parse_url($variables['_SERVER']['REQUEST_URI'] ?? '', PHP_URL_PATH);

            // Remove base folders from the URL if webroot is hosted in a subfolder
            if (substr(strtolower($url ?? ''), 0, strlen(BASE_URL)) === strtolower(BASE_URL)) {
                $url = substr($url ?? '', strlen(BASE_URL));
            }
        }

        // Build request
        $request = new HTTPRequest(
            $variables['_SERVER']['REQUEST_METHOD'],
            $url,
            $variables['_GET'],
            $variables['_POST'],
            $input
        );

        // Set the scheme to HTTPS if needed
        if ((!empty($variables['_SERVER']['HTTPS']) && $variables['_SERVER']['HTTPS'] != 'off')
            || isset($variables['_SERVER']['SSL'])) {
            $request->setScheme('https');
        }

        // Set the client IP
        if (!empty($variables['_SERVER']['REMOTE_ADDR'])) {
            $request->setIP($variables['_SERVER']['REMOTE_ADDR']);
        }

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
    public static function extractRequestHeaders(array $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (substr($key ?? '', 0, 5) == 'HTTP_') {
                $key = substr($key ?? '', 5);
                $key = strtolower(str_replace('_', ' ', $key ?? '') ?? '');
                $key = str_replace(' ', '-', ucwords($key ?? ''));
                $headers[$key] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $server['CONTENT_LENGTH'];
        }

        // Enable HTTP Basic authentication workaround for PHP running in CGI mode with Apache
        // Depending on server configuration the auth header may be in HTTP_AUTHORIZATION or
        // REDIRECT_HTTP_AUTHORIZATION
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $server['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Ensure basic auth is available via headers
        if (isset($server['PHP_AUTH_USER']) && isset($server['PHP_AUTH_PW'])) {
            // Shift PHP_AUTH_* into headers so they are available via request
            $headers['PHP_AUTH_USER'] = $server['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = $server['PHP_AUTH_PW'];
        } elseif ($authHeader && preg_match('/Basic\s+(?<token>.*)$/i', $authHeader ?? '', $matches)) {
            list($name, $password) = explode(':', base64_decode($matches['token'] ?? '') ?? '');
            $headers['PHP_AUTH_USER'] = $name;
            $headers['PHP_AUTH_PW'] = $password;
        }

        return $headers;
    }

    /**
     * Clean up HTTP global vars for $_GET / $_REQUEST prior to bootstrapping
     *
     * @param array $variables
     * @return array Cleaned variables
     */
    public static function cleanEnvironment(array $variables)
    {
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
