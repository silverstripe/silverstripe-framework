<?php

// Check PHP version
if (version_compare(phpversion(), '5.6.0', '<')) {
    header($_SERVER['SERVER_PROTOCOL'] . " 500 Server Error");
    echo str_replace(
        '$PHPVersion',
        phpversion(),
        file_get_contents(__DIR__ . "/../Dev/Install/php5-required.html")
    );
    die();
}

// Init composer autoload
call_user_func(function () {
    $candidates = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php',
        getcwd() . '/vendor/autoload.php',
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            require_once $candidate;
            return;
        }
    }
    die("Failed to include composer's autoloader, unable to continue");
});
