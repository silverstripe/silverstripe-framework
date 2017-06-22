<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.5.0 or higher.                                           **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

/**
 * PHP version check. Make sure we've got at least PHP 5.5.0 in the most friendly way possible
 */
define('FRAMEWORK_NAME', 'framework');

if (version_compare(phpversion(), '5.5.0', '<')) {
    header($_SERVER['SERVER_PROTOCOL'] . " 500 Server Error");
    echo str_replace(
        array('$PHPVersion', 'sapphire'),
        array(phpversion(), FRAMEWORK_NAME),
        file_get_contents(__DIR__ . "/php5-required.html")
    );
    die();
}

include(__DIR__ . '/install5.php');
