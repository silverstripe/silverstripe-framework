<?php

// Asset folder
if(!file_exists(BASE_PATH . '/assets')) {
	mkdir(BASE_PATH . '/assets', 02775);
}

// DATABASE BOOTSTRAP
if (!defined('SS_ENVIRONMENT_TYPE')) {
	define('SS_ENVIRONMENT_TYPE', 'dev');
}

if (!defined('SS_DATABASE_CLASS') && !defined('SS_DATABASE_USERNAME')) {
	// The default settings let us define the database config via environment vars
	// Database connection, including PDO and legacy ORM support
	switch(getenv('DB')) {
	case "PGSQL";
		define('SS_DATABASE_CLASS', getenv('PDO') ? 'PostgrePDODatabase' : 'PostgreSQLDatabase');
		define('SS_DATABASE_USERNAME', 'postgres');
		define('SS_DATABASE_PASSWORD', '');
		break;

	case "SQLITE":
		define('SS_DATABASE_CLASS', getenv('PDO') ? 'SQLite3PDODatabase' : 'SQLite3Database');
		define('SS_DATABASE_USERNAME', 'root');
		define('SS_DATABASE_PASSWORD', '');
		define('SS_SQLITE_DATABASE_PATH', ':memory:');
		break;

	default:
		define('SS_DATABASE_CLASS', getenv('PDO') ? 'MySQLPDODatabase' : 'MySQLDatabase');
		define('SS_DATABASE_USERNAME', 'root');
		define('SS_DATABASE_PASSWORD', '');
	}

	define('SS_DATABASE_SERVER', '127.0.0.1');
	define('SS_DATABASE_CHOOSE_NAME', true);
}

// Default database settings
global $database;
$database = '';

require_once(__DIR__ . '/../../conf/ConfigureFromEnv.php');

class Page extends SilverStripe\View\ViewableData {}
class Page_Controller extends SilverStripe\Control\Controller {}
