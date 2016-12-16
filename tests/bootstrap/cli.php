<?php

// Fake the script name and base
global $_SERVER;
if (!$_SERVER) {
    $_SERVER = array();
}

$frameworkPath = dirname(dirname(__FILE__));
$frameworkDir = basename($frameworkPath);

$_SERVER['SCRIPT_FILENAME'] = $frameworkPath . DIRECTORY_SEPARATOR . 'cli-script.php';
$_SERVER['SCRIPT_NAME'] = '.' . DIRECTORY_SEPARATOR . $frameworkDir . DIRECTORY_SEPARATOR . 'cli-script.php';

// Copied from cli-script.php, to enable same behaviour through phpunit runner.
if (isset($_SERVER['argv'][2])) {
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
}

// Ensure Director::protocolAndHost() works
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
