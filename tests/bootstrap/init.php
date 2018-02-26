<?php

if (!defined('BASE_PATH')) {
    echo "BASE_PATH hasn't been defined. This probably means that framework/Core/Constants.php hasn't been " . "included by Composer's autoloader.\n" . "Make sure the you are running your tests via vendor/bin/phpunit and your autoloader is up to date.\n";
    exit(1);
}


/**
 * This bootstraps the SilverStripe system so that phpunit can be run directly on SilverStripe tests.
 */

// Make sure display_errors is on
ini_set('display_errors', 1);

// Asset folder
if (!file_exists(ASSETS_PATH)) {
    mkdir(ASSETS_PATH, 02775);
}

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
