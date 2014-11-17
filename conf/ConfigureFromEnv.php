<?php

/**
 * Configure SilverStripe from the _ss_environment.php file.
 * Usage: Put "require_once('conf/ConfigureFromEnv.php');" into your _config.php file.
 *
 * If you include this file, you will be able to use the following defines in _ss_environment.php to control
 * your site.
 *
 * Your database connection will be set up using these defines:
 *  - SS_DATABASE_CLASS:    The database class to use, MySQLDatabase, MSSQLDatabase, etc. defaults to
 *                          MySQLDatabase
 *  - SS_DATABASE_SERVER:   The database server to use, defaulting to localhost
 *  - SS_DATABASE_USERNAME: The database username (mandatory)
 *  - SS_DATABASE_PASSWORD: The database password (mandatory)
 *  - SS_DATABASE_PORT:     The database port
 *  - SS_DATABASE_SUFFIX:   A suffix to add to the database name.
 *  - SS_DATABASE_PREFIX:   A prefix to add to the database name.
 *  - SS_DATABASE_TIMEZONE: Set the database timezone to something other than the system timezone.
 *  - SS_DATABASE_MEMORY:   Use in-memory state if possible. Useful for testing, currently only
 *                          supported by the SQLite database adapter.
 *
 * There is one more setting that is intended to be used by people who work on SilverStripe.
 *  - SS_DATABASE_CHOOSE_NAME: Boolean/Int.  If set, then the system will choose a default database name for you if
 *    one isn't give in the $database variable.  The database name will be "SS_" followed by the name of the folder
 *    into which you have installed SilverStripe.  If this is enabled, it means that the phpinstaller will work out of
 *    the box without the installer needing to alter any files.  This helps prevent accidental changes to the
 *    environment.
 *
 *    If SS_DATABASE_CHOOSE_NAME is an integer greater than one, then an ancestor folder will be used for the database
 *    name.  This is handy for a site that's hosted from /sites/examplesite/www or /buildbot/allmodules-2.3/build.  If
 *    it's 2, the parent folder will be chosen; if it's 3 the grandparent, and so on.
 *
 * You can configure the environment with this define:
 *
 *  - SS_ENVIRONMENT_TYPE: The environment type: dev, test or live.
 *
 * You can configure the default admin with these defines:
 *
 *  - SS_DEFAULT_ADMIN_USERNAME: The username of the default admin - this is a non-database user with administrative
 *    privileges.
 *  - SS_DEFAULT_ADMIN_PASSWORD: The password of the default admin.
 *  - SS_USE_BASIC_AUTH: Protect the site with basic auth (good for test sites)
 *
 * Email:
 *  - SS_SEND_ALL_EMAILS_TO: If you set this define, all emails will be redirected to this address.
 *  - SS_SEND_ALL_EMAILS_FROM: If you set this define, all emails will be send from this address.
 *
 * @package framework
 * @subpackage core
 */

/*
 * _ss_environment.php handler
 */
if(defined('SS_ENVIRONMENT_FILE')) {
	// Only perform validation if SS_ENVIRONMENT_FILE is actually set, which is to say, there is an
	// _ss_environment.php file
	foreach(array(
		'SS_DATABASE_PASSWORD',
		'SS_DATABASE_USERNAME',
		'SS_ENVIRONMENT_TYPE',) as $reqDefine) {
		if(!defined($reqDefine)) {
			user_error("$reqDefine must be defined in your _ss_environment.php."
				. "See http://doc.silverstripe.org/framework/en/topics/environment-management for more information",
				E_USER_ERROR);
		}
	}
}

if(defined('SS_ENVIRONMENT_TYPE')) {
	Config::inst()->update('Director', 'environment_type', SS_ENVIRONMENT_TYPE);
}

global $database;

// No database provided
if(!isset($database) || !$database) {
	if(defined('SS_DATABASE_NAME')) {
		$database = SS_DATABASE_NAME;
	} else if(defined('SS_DATABASE_CHOOSE_NAME') && SS_DATABASE_CHOOSE_NAME) {
		$loopCount = (int)SS_DATABASE_CHOOSE_NAME;
		$databaseDir = BASE_PATH;
		for($i=0;$i<$loopCount-1;$i++) $databaseDir = dirname($databaseDir);
		$database = "SS_" . basename($databaseDir);
		$database = str_replace('.','',$database);
	}
}

if(defined('SS_DATABASE_USERNAME') && defined('SS_DATABASE_PASSWORD')) {
	global $databaseConfig;
	$databaseConfig = array(
		"type" => defined('SS_DATABASE_CLASS') ? SS_DATABASE_CLASS : "MySQLDatabase",
		"server" => defined('SS_DATABASE_SERVER') ? SS_DATABASE_SERVER : 'localhost',
		"username" => SS_DATABASE_USERNAME,
		"password" => SS_DATABASE_PASSWORD,
		"database" => (defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : '')
			. $database
			. (defined('SS_DATABASE_SUFFIX') ? SS_DATABASE_SUFFIX : ''),
	);

	// Set the port if called for
	if(defined('SS_DATABASE_PORT')) {
		$databaseConfig['port'] = SS_DATABASE_PORT;
	}

	// Set the timezone if called for
	if (defined('SS_DATABASE_TIMEZONE')) {
		$databaseConfig['timezone'] = SS_DATABASE_TIMEZONE;
	}

	// For schema enabled drivers:
	if(defined('SS_DATABASE_SCHEMA'))
		$databaseConfig["schema"] = SS_DATABASE_SCHEMA;

	// For SQlite3 memory databases (mainly for testing purposes)
	if(defined('SS_DATABASE_MEMORY'))
		$databaseConfig["memory"] = SS_DATABASE_MEMORY;
}

if(defined('SS_SEND_ALL_EMAILS_TO')) {
	Config::inst()->update("Email","send_all_emails_to", SS_SEND_ALL_EMAILS_TO);
}
if(defined('SS_SEND_ALL_EMAILS_FROM')) {
	Config::inst()->update("Email","send_all_emails_from", SS_SEND_ALL_EMAILS_FROM);
}

if(defined('SS_DEFAULT_ADMIN_USERNAME')) {
	if(!defined('SS_DEFAULT_ADMIN_PASSWORD')) {
		user_error("SS_DEFAULT_ADMIN_PASSWORD must be defined in your _ss_environment.php,"
			. "if SS_DEFAULT_ADMIN_USERNAME is defined.  See "
			. "http://doc.silverstripe.org/framework/en/topics/environment-management for more information",
			E_USER_ERROR);
	}
	Security::setDefaultAdmin(SS_DEFAULT_ADMIN_USERNAME, SS_DEFAULT_ADMIN_PASSWORD);
}
if(defined('SS_USE_BASIC_AUTH') && SS_USE_BASIC_AUTH) {
	Config::inst()->update('BasicAuth', 'entire_site_protected', SS_USE_BASIC_AUTH);
}

if(defined('SS_ERROR_LOG')) {
	SS_Log::add_writer(new SS_LogFileWriter(BASE_PATH . '/' . SS_ERROR_LOG), SS_Log::WARN, '<=');
}

// Allow database adapters to handle their own configuration
DatabaseAdapterRegistry::autoconfigure();
