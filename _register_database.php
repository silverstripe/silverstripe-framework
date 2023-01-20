<?php

// Register database adapters available in SilverStripe
use SilverStripe\Dev\Install\DatabaseAdapterRegistry;
use SilverStripe\Dev\Install\MySQLDatabaseConfigurationHelper;

// Register MySQLi as a database adapter (listed as second option in Dev/Install/config-form.html)
DatabaseAdapterRegistry::register(
    [
        'class' => 'MySQLDatabase',
        'module' => 'framework',
        'title' => 'MySQL 5.0+ (using MySQLi)',
        'helperPath' => __DIR__ . '/src/Dev/Install/MySQLDatabaseConfigurationHelper.php',
        'helperClass' => MySQLDatabaseConfigurationHelper::class,
        'supported' => class_exists('MySQLi'),
        'missingExtensionText' =>
            'The <a href="http://www.php.net/manual/en/book.mysqli.php">MySQLi</a>
			PHP extension is not available. Please install or enable it and refresh this page.'
    ]
);
