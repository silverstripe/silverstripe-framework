<?php

// Fake the script name and base
global $_SERVER;
if (!$_SERVER) {
    $_SERVER = [];
}

// We update the $_SERVER variable to contain data consistent with the rest of the application.
$_SERVER = array_merge([
    'SERVER_PROTOCOL' => 'HTTP/1.1',
    'HTTP_ACCEPT' => 'text/plain;q=0.5',
    'HTTP_ACCEPT_LANGUAGE' => '*;q=0.5',
    'HTTP_ACCEPT_ENCODING' => '',
    'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1;q=0.5',
    'SERVER_SIGNATURE' => 'Command-line PHP/' . phpversion(),
    'SERVER_SOFTWARE' => 'PHP/' . phpversion(),
    'SERVER_NAME' => 'localhost',
    'SERVER_ADDR' => '127.0.0.1',
    'REMOTE_ADDR' => '127.0.0.1',
    'REQUEST_METHOD' => 'GET',
    'HTTP_USER_AGENT' => 'CLI',
], $_SERVER);

$frameworkPath = dirname(dirname(__FILE__));
$frameworkDir = basename($frameworkPath ?? '');

$_SERVER['SCRIPT_FILENAME'] = $frameworkPath . DIRECTORY_SEPARATOR . 'cli-script.php';
$_SERVER['SCRIPT_NAME'] = '.' . DIRECTORY_SEPARATOR . $frameworkDir . DIRECTORY_SEPARATOR . 'cli-script.php';

// Copied from cli-script.php, to enable same behaviour through phpunit runner.
if (isset($_SERVER['argv'][2])) {
    $args = array_slice($_SERVER['argv'] ?? [], 2);
    if (!isset($_GET)) {
        $_GET = [];
    }
    if (!isset($_REQUEST)) {
        $_REQUEST = [];
    }
    foreach ($args as $arg) {
        if (strpos($arg ?? '', '=') == false) {
            $_GET['args'][] = $arg;
        } else {
            $newItems = [];
            parse_str((substr($arg ?? '', 0, 2) == '--') ? substr($arg, 2) : $arg, $newItems);
            $_GET = array_merge($_GET, $newItems);
        }
    }
    $_REQUEST = array_merge($_REQUEST, $_GET);
}

// Ensure Director::protocolAndHost() works
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
