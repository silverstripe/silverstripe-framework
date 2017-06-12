<?php

// Mock HTTP globals in CLI environment

// Ensure that people can't access this from a web-server
if (PHP_SAPI != "cli" && PHP_SAPI != "cgi" && PHP_SAPI != "cgi-fcgi") {
    echo "cli-script.php can't be run from a web request, you have to run it on the command-line.";
    die();
}

// We update the $_SERVER variable to contain data consistent with the rest of the application.
$_SERVER = array_merge(array(
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
), $_SERVER);

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
if (isset($_SERVER['argv'][2])) {
    call_user_func(function () {
        $args = array_slice($_SERVER['argv'], 2);
        if (!isset($_GET)) {
            $_GET = array();
        }
        if (!isset($_REQUEST)) {
            $_REQUEST = array();
        }
        foreach ($args as $arg) {
            if (strpos($arg, '=') == false) {
                $_GET['args'][] = $arg;
            } else {
                $newItems = array();
                parse_str((substr($arg, 0, 2) == '--') ? substr($arg, 2) : $arg, $newItems);
                $_GET = array_merge($_GET, $newItems);
            }
        }
        $_REQUEST = array_merge($_REQUEST, $_GET);
    });
}

// Set 'url' GET parameter
if (isset($_SERVER['argv'][1])) {
    $_REQUEST['url'] = $_SERVER['argv'][1];
    $_GET['url'] = $_SERVER['argv'][1];
}
