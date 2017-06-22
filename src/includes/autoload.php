<?php

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
