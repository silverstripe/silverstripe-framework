<?php
/**
 * Configure SilverStripe from the environment variables.
 * Usage: Put "require_once('conf/ConfigureFromEnv.php');" into your _config.php file.
 *
 * If you include this file, you will be able to use the following variables to control
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
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Install\DatabaseAdapterRegistry;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\Security;

global $database;

// No database provided
if (!isset($database) || !$database) {
    if (!($database = getenv('SS_DATABASE_NAME')) && $chooseName = getenv('SS_DATABASE_CHOOSE_NAME')) {
        $loopCount = (int)$chooseName;
        $databaseDir = BASE_PATH;
        for ($i=0; $i<$loopCount-1; $i++) {
            $databaseDir = dirname($databaseDir);
        }
        $database = getenv('SS_DATABASE_PREFIX') ?: 'SS_';
        $database .= basename($databaseDir);
        $database = str_replace('.', '', $database);
    }
}

if ($dbUser = getenv('SS_DATABASE_USERNAME')) {
    global $databaseConfig;

    // Checks if the database global is defined (if present, wraps with prefix and suffix)
    $databaseNameWrapper = function ($name) {
        if (!$name) {
            return '';
        } else {
            return (getenv('SS_DATABASE_PREFIX') ?: '')
            . $name
            . (getenv('SS_DATABASE_SUFFIX') ?: '');
        }
    };

    /** @skipUpgrade */
    $databaseConfig = array(
        "type" => getenv('SS_DATABASE_CLASS') ?: 'MySQLDatabase',
        "server" => getenv('SS_DATABASE_SERVER') ?: 'localhost',
        "username" => $dbUser,
        "password" => getenv('SS_DATABASE_PASSWORD'),
        "database" => $databaseNameWrapper($database),
    );

    // Set the port if called for
    if ($dbPort = getenv('SS_DATABASE_PORT')) {
        $databaseConfig['port'] = $dbPort;
    }

    // Set the timezone if called for
    if ($dbTZ = getenv('SS_DATABASE_TIMEZONE')) {
        $databaseConfig['timezone'] = $dbTZ;
    }

    // For schema enabled drivers:
    if ($dbSchema = getenv('SS_DATABASE_SCHEMA')) {
        $databaseConfig["schema"] = $dbSchema;
    }

    // For SQlite3 memory databases (mainly for testing purposes)
    if ($dbMemory = getenv('SS_DATABASE_MEMORY')) {
        $databaseConfig["memory"] = $dbMemory;
    }
}

if ($sendAllEmailsTo = getenv('SS_SEND_ALL_EMAILS_TO')) {
    Email::config()->send_all_emails_to = $sendAllEmailsTo;
}
if ($sendAllEmailsFrom = getenv('SS_SEND_ALL_EMAILS_FROM')) {
    Email::config()->send_all_emails_from = $sendAllEmailsFrom;
}

if ($defaultAdminUser = getenv('SS_DEFAULT_ADMIN_USERNAME')) {
    if (!$defaultAdminPass = getenv('SS_DEFAULT_ADMIN_PASSWORD')) {
        user_error(
            "SS_DEFAULT_ADMIN_PASSWORD must be defined in your environment,"
            . "if SS_DEFAULT_ADMIN_USERNAME is defined.  See "
            . "http://doc.silverstripe.org/framework/en/topics/environment-management for more information",
            E_USER_ERROR
        );
    } else {
        Security::setDefaultAdmin($defaultAdminUser, $defaultAdminPass);
    }
}
if ($useBasicAuth = getenv('SS_USE_BASIC_AUTH')) {
    BasicAuth::config()->entire_site_protected = $useBasicAuth;
}

if ($errorLog = getenv('SS_ERROR_LOG')) {
    $logger = Injector::inst()->get(LoggerInterface::class);
    if ($logger instanceof Logger) {
        $logger->pushHandler(new StreamHandler(BASE_PATH . '/' . $errorLog, Logger::WARNING));
    } else {
        user_error("SS_ERROR_LOG setting only works with Monolog, you are using another logger", E_USER_WARNING);
    }
}

// Allow database adapters to handle their own configuration
DatabaseAdapterRegistry::autoconfigure();

unset(
    $envType,
    $chooseName,
    $loopCount,
    $databaseDir,
    $i,
    $databaseNameWrapper,
    $dbUser,
    $dbPort,
    $dbTZ,
    $dbSchema,
    $dbMemory,
    $sendAllEmailsTo,
    $sendAllEmailsFrom,
    $defaultAdminUser,
    $defaultAdminPass,
    $useBasicAuth,
    $errorLog,
    $logger
);
