<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.3.2 or higher, preferably PHP 5.3.10.                    **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

/**
 * PHP version check. Make sure we've got at least PHP 5.3.2 in the most friendly way possible
 */

if (version_compare(phpversion(), '5.3.2', '<')) {
	header("HTTP/1.1 500 Server Error");
	echo str_replace('$PHPVersion', phpversion(), file_get_contents("sapphire/dev/install/php5-required.html"));
	die();
}

include('sapphire/dev/install/install.php5');
