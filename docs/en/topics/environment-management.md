# Environment management

As website developers, we noticed that we had a few problems.  You may have the same problems:

*  On our development laptops, we have a number of sites, but the database connection details are the same for each of
them.  Why should we have to go through the installation process and re-enter them each time?
*  Each of those sites needed to be in development mode when we were editing them on our laptops, but in production mode
when we deploy them to our servers.  Additionally, our production host's database connection details will likely be
different than our local server.

SilverStripe comes with a solution to this: the `_ss_environment.php` file.  You can put a single `_ss_environment.php`
file in your "projects" folder on your development box, and it will be used by each of your development sites.

## Setting up your development machine with _ss_environment.php

In this example, we assume that you are managing multiple projects as subfolders of `~/Sites/`, and that you can visit
these at `http://localhost/`.  For example, you might have a project at `~/Sites/myproject/`, and visit it at
`http://localhost/myproject/`.

Create a new file, `~/Sites/_ss_environment.php`.  Put the following content in it, editing the values of the
"SS_DATABASE_..." and "SS_DEFAULT_ADMIN_..." defines as appropriate.

	:::php
	<?php
	/* What kind of environment is this: development, test, or live (ie, production)? */
	define('SS_ENVIRONMENT_TYPE', 'dev/test/live');
	
	/* Database connection */
	define('SS_DATABASE_SERVER', 'localhost');
	define('SS_DATABASE_USERNAME', 'root');
	define('SS_DATABASE_PASSWORD', '');
	
	/* Configure a default username and password to access the CMS on all sites in this environment. */
	define('SS_DEFAULT_ADMIN_USERNAME', 'username');
	define('SS_DEFAULT_ADMIN_PASSWORD', 'password');


Now, edit each of your site's configuration file, usually `mysite/_config.php`.  Delete all mention
of `$databaseConfig` and `Director::set_dev_servers`, and instead make sure that you file starts like this.

	:::php
	<?php
	
	global $project;
	$project = 'mysite';
	
	global $database;
	$database = '(databasename)';
	
	// Use _ss_environment.php file for configuration
	require_once("conf/ConfigureFromEnv.php");


## How it works

The mechanism by which the `_ss_environment.php` files work is quite simple.  Here's how it works:

*  At the beginning of SilverStripe's execution, the `_ss_environment.php` file is searched for, and if it is found, it's
included.  SilverStripe looks in all the parent folders of framework up to the server root (using the REAL location of
the dir - see PHP realpath()):
*  The `_ss_environment.php` file sets a number of "define()".
*  "conf/ConfigureFromEnv.php" is included from within your `mysite/_config.php`.  This file has a number of regular
configuration commands that use those defines as their arguments.  If you are curious, open up
`framework/conf/ConfigureFromEnv.php` and see for yourself!

### An Example

This is my `_ss_environment.php` file. I have it placed in `/var`, as each of the sites are in a subfolder of `/var`.

	:::php
	<?php
	// These three defines set the database connection details.
	define('SS_DATABASE_SERVER', 'localhost');
	define('SS_DATABASE_USERNAME', 'root');
	define('SS_DATABASE_PASSWORD', '<password>');
	
	// This sets a prefix, which is prepended to the $database variable. This is
	// helpful mainly on shared hosts, when every database has a prefix.
	define('SS_DATABASE_PREFIX', 'simon_');
	
	// These two lines are a bit complicated. If I'm connecting to the server from
	// 127.0.0.1 or MyIP and I'm using a browser with a + in the UserAgent, the site
	// is put in dev mode, otherwise it is put in live mode. Most sites would only
	// need to put the site in either dev or live mode, thus wont need the IP checks
	if(isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || ($_SERVER['REMOTE_ADDR'] == '<MyIP>' 
	&& strpos($_SERVER['HTTP_USER_AGENT'], '+') !== false))) 
		define('SS_ENVIRONMENT_TYPE', 'dev');
	else 
		define('SS_ENVIRONMENT_TYPE', 'live');
	
	// These two defines sets a default login which, when used, will always log
	// you in as an admin, even creating one if none exist.
	define('SS_DEFAULT_ADMIN_USERNAME', '<email>');
	define('SS_DEFAULT_ADMIN_PASSWORD', '<password>');
	
	// This causes errors to be written to the silverstripe.log file in the same directory as this file, so /var.
	// Before PHP 5.3.0, you'll need to use dirname(__FILE__) instead of __DIR__
	define('SS_ERROR_LOG', __DIR__ . '/silverstripe.log');
	
	// This is used by sake to know which directory points to which URL
	global $_FILE_TO_URL_MAPPING;
	$_FILE_TO_URL_MAPPING['/var/www'] = 'http://simon.geek.nz';

## Available Constants

| Name  | Description |
| ----  | ----------- |
| `TEMP_FOLDER` | Absolute file path to store temporary files such as cached templates or the class manifest. Needs to be writeable by the webserver user. Defaults to *silverstripe-cache* in the webroot, and falls back to *sys_get_temp_dir()*. See *getTempFolder()* in *framework/core/TempPath.php* |
| `SS_DATABASE_CLASS` | The database class to use, MySQLDatabase, MSSQLDatabase, etc. defaults to MySQLDatabase|
| `SS_DATABASE_SERVER`| The database server to use, defaulting to localhost|
| `SS_DATABASE_USERNAME`| The database username (mandatory)|
| `SS_DATABASE_PASSWORD`| The database password (mandatory)|
| `SS_DATABASE_PORT`|     The database port|
| `SS_DATABASE_SUFFIX`|   A suffix to add to the database name.|
| `SS_DATABASE_PREFIX`|   A prefix to add to the database name.|
| `SS_DATABASE_TIMEZONE`| Set the database timezone to something other than the system timezone.
| `SS_DATABASE_NAME` | Set the database name. Assumes the `$database` global variable in your config is missing or empty. |
| `SS_DATABASE_CHOOSE_NAME`| Boolean/Int.  If set, then the system will choose a default database name for you if one isn't give in the $database variable.  The database name will be "SS_" followed by the name of the folder into which you have installed SilverStripe.  If this is enabled, it means that the phpinstaller will work out of the box without the installer needing to alter any files.  This helps prevent accidental changes to the environment. If `SS_DATABASE_CHOOSE_NAME` is an integer greater than one, then an ancestor folder will be used for the  database name.  This is handy for a site that's hosted from /sites/examplesite/www or /buildbot/allmodules-2.3/build. If it's 2, the parent folder will be chosen; if it's 3 the grandparent, and so on.|
| `SS_ENVIRONMENT_TYPE`| The environment type: dev, test or live.|
| `SS_DEFAULT_ADMIN_USERNAME`| The username of the default admin. This is a user with administrative privileges.|
| `SS_DEFAULT_ADMIN_PASSWORD`| The password of the default admin. This will not be stored in the database.|
| `SS_USE_BASIC_AUTH`| Protect the site with basic auth (good for test sites).<br/>When using CGI/FastCGI with Apache, you will have to add the `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]` rewrite rule to your `.htaccess` file|
| `SS_SEND_ALL_EMAILS_TO`| If you set this define, all emails will be redirected to this address.|
| `SS_SEND_ALL_EMAILS_FROM`| If you set this define, all emails will be send from this address.|
| `SS_ERROR_LOG` | |
