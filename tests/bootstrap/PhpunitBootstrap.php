<?php

namespace SilverStripe\Framework\Tests\Bootstrap;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use SilverStripe\Core\Environment;

class PhpunitBootstrap implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->init();
        $this->cli($parameters);
        $this->environment();
    }

    /**
     * This bootstraps the SilverStripe system so that phpunit can be run directly on SilverStripe tests.
     */
    protected function init(): void
    {
        if (!defined('BASE_PATH')) {
            echo "BASE_PATH hasn't been defined. This probably means that framework/Core/Constants.php hasn't been "
                . "included by Composer's autoloader.\n"
                . "Make sure the you are running your tests via vendor/bin/phpunit and your autoloader is up to date.\n";
            exit(1);
        }

        // Make sure display_errors is on
        ini_set('display_errors', 1);

        // Asset folder
        if (!file_exists(ASSETS_PATH)) {
            mkdir(ASSETS_PATH, 02775);
        }

        if (empty($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }
    }

    protected function cli(ParameterCollection $parameters): void
    {
        // Update the $_SERVER variable to contain data consistent with the rest of the application.
        $_SERVER = array_merge([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_ACCEPT' => 'text/plain;q=0.5',
            'HTTP_ACCEPT_LANGUAGE' => '*;q=0.5',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1;q=0.5',
            'SERVER_SIGNATURE' => 'Command-line PHP/' . phpversion(),
            'SERVER_SOFTWARE' => 'PHP/' . phpversion(),
            'SERVER_NAME' => 'localhost',
            'SERVER_ADDR' => '127.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'CLI',
        ], $_SERVER);

        // Handle flush parameter
        if ($parameters->has('flush')) {
            if (!isset($_GET)) {
                $_GET = [];
            }
            if (!isset($_REQUEST)) {
                $_REQUEST = [];
            }
            $_GET['flush'] = $parameters->get('flush');
            $_REQUEST = array_merge($_REQUEST, $_GET);
        }

        // Ensure Director::protocolAndHost() works
        if (empty($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }
    }

    /**
     * Bootstrap environment variables
     */
    protected function environment(): void
    {
        if (!Environment::getEnv('SS_DATABASE_CLASS') && !Environment::getEnv('SS_DATABASE_USERNAME')) {
            // The default settings let us define the database config via environment vars
            // Database connection, including legacy ORM support
            switch (Environment::getEnv('DB')) {
                case "PGSQL";
                    $pgDatabaseClass = 'PostgreSQLDatabase';
                    Environment::setEnv('SS_DATABASE_CLASS', $pgDatabaseClass);
                    Environment::setEnv('SS_DATABASE_USERNAME', 'postgres');
                    Environment::setEnv('SS_DATABASE_PASSWORD', '');
                    break;

                case "SQLITE":
                    $sqliteDatabaseClass = 'SQLite3Database';
                    Environment::setEnv('SS_DATABASE_CLASS', $sqliteDatabaseClass);
                    Environment::setEnv('SS_DATABASE_USERNAME', 'root');
                    Environment::setEnv('SS_DATABASE_PASSWORD', '');
                    Environment::setEnv('SS_SQLITE_DATABASE_PATH', ':memory:');
                    break;

                default:
                    $mysqlDatabaseClass = 'MySQLDatabase';
                    Environment::setEnv('SS_DATABASE_CLASS', $mysqlDatabaseClass);
                    Environment::setEnv('SS_DATABASE_USERNAME', 'root');
                    Environment::setEnv('SS_DATABASE_PASSWORD', '');
            }

            Environment::setEnv('SS_DATABASE_CHOOSE_NAME', 'true');
            Environment::setEnv('SS_DATABASE_SERVER', '127.0.0.1');
        }
    }
}
