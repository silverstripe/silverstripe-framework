<?php

// Bootstrap environment variables

use SilverStripe\Core\Environment;

/** @skipUpgrade */
if (!Environment::getEnv('SS_DATABASE_CLASS') && !Environment::getEnv('SS_DATABASE_USERNAME')) {
    // The default settings let us define the database config via environment vars
    // Database connection, including PDO and legacy ORM support
    switch (Environment::getEnv('DB')) {
        case "PGSQL";
            $pgDatabaseClass = Environment::getEnv('PDO') ? 'PostgrePDODatabase' : 'PostgreSQLDatabase';
            Environment::setEnv('SS_DATABASE_CLASS', $pgDatabaseClass);
            Environment::setEnv('SS_DATABASE_USERNAME', 'postgres');
            Environment::setEnv('SS_DATABASE_PASSWORD', '');
            break;

        case "SQLITE":
            $sqliteDatabaseClass = Environment::getEnv('PDO') ? 'SQLite3PDODatabase' : 'SQLite3Database';
            Environment::setEnv('SS_DATABASE_CLASS', $sqliteDatabaseClass);
            Environment::setEnv('SS_DATABASE_USERNAME', 'root');
            Environment::setEnv('SS_DATABASE_PASSWORD', '');
            Environment::setEnv('SS_SQLITE_DATABASE_PATH', ':memory:');
            break;

        default:
            $mysqlDatabaseClass = Environment::getEnv('PDO') ? 'MySQLPDODatabase' : 'MySQLDatabase';
            Environment::setEnv('SS_DATABASE_CLASS', $mysqlDatabaseClass);
            Environment::setEnv('SS_DATABASE_USERNAME', 'root');
            Environment::setEnv('SS_DATABASE_PASSWORD', '');
    }

    Environment::setEnv('SS_DATABASE_CHOOSE_NAME', 'true');
    Environment::setEnv('SS_DATABASE_SERVER', '127.0.0.1');
}
