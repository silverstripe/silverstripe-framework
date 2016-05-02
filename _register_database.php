<?php

// Register the SilverStripe provided databases
$frameworkPath = defined('FRAMEWORK_PATH') ? FRAMEWORK_PATH : FRAMEWORK_NAME;

// Use MySQLi as default
DatabaseAdapterRegistry::register(
	array(
		'class' => 'MySQLDatabase',
		'title' => 'MySQL 5.0+ (using MySQLi)',
		'helperPath' => $frameworkPath . '/dev/install/MySQLDatabaseConfigurationHelper.php',
		'supported' => class_exists('MySQLi'),
		'missingExtensionText' =>
			'The <a href="http://www.php.net/manual/en/book.mysqli.php">MySQLi</a>
			PHP extension is not available. Please install or enable it and refresh this page.'
	)
);

// Setup MySQL PDO as alternate option
DatabaseAdapterRegistry::register(
	array(
		'class' => 'MySQLPDODatabase',
		'title' => 'MySQL 5.0+ (using PDO)',
		'helperPath' => $frameworkPath . '/dev/install/MySQLDatabaseConfigurationHelper.php',
		'supported' => (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers())),
		'missingExtensionText' =>
			'Either the <a href="http://www.php.net/manual/en/book.pdo.php">PDO Extension</a> or
			the <a href="http://www.php.net/manual/en/ref.pdo-mysql.php">MySQL PDO Driver</a>
			are unavailable. Please install or enable these and refresh this page.'
	)
);
