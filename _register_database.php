<?php

// Register the SilverStripe provided databases
$frameworkPath = defined('FRAMEWORK_PATH') ? FRAMEWORK_PATH : FRAMEWORK_NAME;

DatabaseAdapterRegistry::register(
	array(
		'class' => 'MySQLDatabase',
		'title' => 'MySQL 5.0+',
		'helperPath' => $frameworkPath . '/dev/install/MySQLDatabaseConfigurationHelper.php',
		'supported' => class_exists('MySQLi'),
	)
);

DatabaseAdapterRegistry::register(
	array(
		'class' => 'MSSQLDatabase',
		'title' => 'SQL Server 2008',
		'helperPath' => 'mssql/code/MSSQLDatabaseConfigurationHelper.php',
		'supported' => (function_exists('mssql_connect') || function_exists('sqlsrv_connect')),
		'missingExtensionText' => 'Neither the <a href="http://php.net/mssql">mssql</a> or <a href="http://www.microsoft.com/sqlserver/2005/en/us/PHP-Driver.aspx">sqlsrv</a> PHP extensions are available. Please install or enable one of them and refresh this page.'
	)
);

DatabaseAdapterRegistry::register(
	array(
		'class' => 'PostgreSQLDatabase',
		'title' => 'PostgreSQL 8.3+',
		'helperPath' => 'postgresql/code/PostgreSQLDatabaseConfigurationHelper.php',
		'supported' => function_exists('pg_query'),
		'missingExtensionText' => 'The <a href="http://php.net/pgsql">pgsql</a> PHP extension is not available. Please install or enable it and refresh this page.'
	)
);

DatabaseAdapterRegistry::register(
	array(
		'class' => 'SQLiteDatabase',
		'title' => 'SQLite 3.3+',
		'helperPath' => 'sqlite3/code/SQLiteDatabaseConfigurationHelper.php',
		'supported' => (class_exists('SQLite3') || class_exists('PDO')),
		'missingExtensionText' => 'The <a href="http://php.net/manual/en/book.sqlite3.php">SQLite3</a> and <a href="http://php.net/manual/en/book.pdo.php">PDO</a> classes are not available. Please install or enable one of them and refresh this page.',
		'fields' => array(
			'path' => array(
				'title' => 'Database path<br /><small>Absolute path, writeable by the webserver user.<br />Recommended to be outside of your webroot</small>',
				'default' => realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . '.db'
			),
			'database' => array(
				'title' => 'Database name', 
				'default' => 'SS_mysite',
				'attributes' => array(
					"onchange" => "this.value = this.value.replace(/[\/\\:*?&quot;<>|. \t]+/g,'');"
				)
			)
		)
	)
);
