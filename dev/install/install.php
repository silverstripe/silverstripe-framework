<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.3 or higher.                                             **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

/**
 * PHP version check. Make sure we've got at least PHP 5.3 in the most friendly way possible
 */

define('FRAMEWORK_NAME', 'framework');

$majorVersion = strtok(phpversion(),'.');
$minorVersion = strtok('.');

if($majorVersion < 5 || ($majorVersion == 5 && $minorVersion < 3)) {
	header("HTTP/1.1 500 Server Error");
	echo str_replace(array('$PHPVersion', 'sapphire'), array(phpversion(), FRAMEWORK_NAME), file_get_contents(FRAMEWORK_NAME . "/dev/install/php5-required.html"));
	die();
}

include(FRAMEWORK_NAME . '/dev/install/install.php5');
