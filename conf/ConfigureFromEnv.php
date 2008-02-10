<?php

/**
 * Configure Sapphire from the _ss_environment.php file.
 * Usage: Put "require_once('sapphire/core/ConfigureFromEnv.php');" into your _config.php file.
 * 
 * If you include this file, you will be able to use the following defines in _ss_environment.php to control 
 * your site.
 * 
 * Your database connection will be set up using these defines:
 *  - SS_DATABASE_SERVER: The database server to use, defaulting to localhost
 *  - SS_DATABASE_USERNAME: The databsae username (mandatory)
 *  - SS_DATABASE_PASSWORD: The database password (mandatory)
 *  - SS_DATABASE_SUFFIX: A suffix to add to the database name.
 *  - SS_DATABASE_PREFIX: A prefix to add to the database name.
 * 
 * You can configure the environment with this define:
 *  - SS_ENVIRONMENT_TYPE: The environment type: dev, test or live.
 * 
 * You can configure the default admin with these defines
 *  - SS_DEFAULT_ADMIN_USERNAME: The username of the default admin - this is a non-database user with administrative privileges.
 *  - SS_DEFAULT_ADMIN_PASSWORD: The password of the default admin.
 * 
 * Email:
 *  - SS_SEND_ALL_EMAILS_TO: If you set this define, all emails will be redirected to this address.
 * 
 * @package sapphire
 * @subpackage core
 */

/*
 * _ss_environment.php handler
 */
foreach(array(
	'SS_DATABASE_PASSWORD',
	'SS_DATABASE_USERNAME', 
	'SS_ENVIRONMENT_TYPE',) as $reqDefine) {
	if(!defined($reqDefine)) user_error("$reqDefine must be defined in your _ss_environment.php.  See http://doc.silverstripe.com/doku.php?id=environment-management for more infomration", E_USER_ERROR);
}
 
Director::set_environment_type(SS_ENVIRONMENT_TYPE);

global $databaseConfig;
$databaseConfig = array(
	"type" => "MySQLDatabase",
	"server" => defined('SS_DATABASE_SERVER') ? SS_DATABASE_SERVER : 'localhost', 
	"username" => SS_DATABASE_USERNAME, 
	"password" => SS_DATABASE_PASSWORD, 
	"database" => (defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX : '')
		. $database 
		. (defined('SS_DATABASE_SUFFIX') ? SS_DATABASE_SUFFIX : ''),
);

if(defined('SS_SEND_ALL_EMAILS_TO')) {
	Email::send_all_emails_to(SS_SEND_ALL_EMAILS_TO);
}

if(defined('SS_DEFAULT_ADMIN_USERNAME')) {
	if(!defined('SS_DEFAULT_ADMIN_PASSWORD')) user_error("SS_DEFAULT_ADMIN_PASSWORD must be defined in your _ss_environment.php, if SS_DEFAULT_ADMIN_USERNAME is defined.  See http://doc.silverstripe.com/doku.php?id=environment-management for more infomration", E_USER_ERROR);
	Security::setDefaultAdmin(SS_DEFAULT_ADMIN_USERNAME, SS_DEFAULT_ADMIN_PASSWORD);
}
