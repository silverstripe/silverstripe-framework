<?php

namespace SilverStripe\Control;

/**
 * CLI specific request building logic
 */
class CLIRequestBuilder extends HTTPRequestBuilder
{
    public static function cleanEnvironment(array $variables)
    {
        // Create all blank vars
        foreach (['_REQUEST', '_GET', '_POST', '_SESSION', '_SERVER', '_COOKIE', '_ENV', '_FILES'] as $key) {
            if (!isset($variables[$key])) {
                $variables[$key] = [];
            };
        }

        // We update the $_SERVER variable to contain data consistent with the rest of the application.
        $variables['_SERVER'] = array_merge(array(
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_ACCEPT' => 'text/plain;q=0.5',
            'HTTP_ACCEPT_LANGUAGE' => '*;q=0.5',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1;q=0.5',
            'SERVER_SIGNATURE' => 'Command-line PHP/' . phpversion(),
            'SERVER_SOFTWARE' => 'PHP/' . phpversion(),
            'SERVER_ADDR' => '127.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'CLI',
        ), $variables['_SERVER']);

        /**
         * Process arguments and load them into the $_GET and $_REQUEST arrays
         * For example,
         * sake my/url somearg otherarg key=val --otherkey=val third=val&fourth=val
         *
         * Will result in the following get data:
         *   args => array('somearg', 'otherarg'),
         *   key => val
         *   otherkey => val
         *   third => val
         *   fourth => val
         */
        if (isset($variables['_SERVER']['argv'][2])) {
            $args = array_slice($variables['_SERVER']['argv'], 2);
            foreach ($args as $arg) {
                if (strpos($arg, '=') == false) {
                    $variables['_GET']['args'][] = $arg;
                } else {
                    $newItems = array();
                    parse_str((substr($arg, 0, 2) == '--') ? substr($arg, 2) : $arg, $newItems);
                    $variables['_GET'] = array_merge($variables['_GET'], $newItems);
                }
            }
            $_REQUEST = array_merge($_REQUEST, $variables['_GET']);
        }

        // Set 'url' GET parameter
        if (isset($variables['_SERVER']['argv'][1])) {
            $variables['_GET']['url'] = $variables['_SERVER']['argv'][1];
            $variables['_SERVER']['REQUEST_URI'] = $variables['_SERVER']['argv'][1];
        }

        // Parse rest of variables as standard
        return parent::cleanEnvironment($variables);
    }
}
