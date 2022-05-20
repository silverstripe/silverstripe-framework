<?php

namespace SilverStripe\Core;

use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\Install\DatabaseAdapterRegistry;
use SilverStripe\ORM\DB;
use Exception;

/**
 * Simple Kernel container
 */
class CoreKernel extends BaseKernel
{

    /**
     * Indicates whether the Kernel has been flushed on boot
     * Uninitialised before boot
     *
     * @var bool
     */
    private $flush;

    /**
     * @param false $flush
     * @throws HTTPResponse_Exception
     * @throws Exception
     */
    public function boot($flush = false)
    {
        $this->flush = $flush;

        $this->bootPHP();
        $this->bootManifests($flush);
        $this->bootErrorHandling();
        $this->bootDatabaseEnvVars();
        $this->bootConfigs();
        $this->bootDatabaseGlobals();
        $this->validateDatabase();

        $this->setBooted(true);
    }

    /**
     * Check that the database configuration is valid, throwing an HTTPResponse_Exception if it's not
     *
     * @throws HTTPResponse_Exception
     */
    protected function validateDatabase()
    {
        $databaseConfig = DB::getConfig();
        // Gracefully fail if no DB is configured
        if (empty($databaseConfig['database'])) {
            $msg = 'Silverstripe Framework requires a "database" key in DB::getConfig(). ' .
                'Did you forget to set SS_DATABASE_NAME or SS_DATABASE_CHOOSE_NAME in your environment?';
            $this->detectLegacyEnvironment();
            $this->redirectToInstaller($msg);
        }
    }

    /**
     * Load default database configuration from the $database and $databaseConfig globals
     */
    protected function bootDatabaseGlobals()
    {
        // Now that configs have been loaded, we can check global for database config
        global $databaseConfig;
        global $database;

        // Case 1: $databaseConfig global exists. Merge $database in as needed
        if (!empty($databaseConfig)) {
            if (!empty($database)) {
                $databaseConfig['database'] =  $this->getDatabasePrefix() . $database . $this->getDatabaseSuffix();
            }

            // Only set it if its valid, otherwise ignore $databaseConfig entirely
            if (!empty($databaseConfig['database'])) {
                DB::setConfig($databaseConfig);

                return;
            }
        }

        // Case 2: $database merged into existing config
        if (!empty($database)) {
            $existing = DB::getConfig();
            $existing['database'] = $this->getDatabasePrefix() . $database . $this->getDatabaseSuffix();

            DB::setConfig($existing);
        }
    }

    /**
     * Load default database configuration from environment variable
     */
    protected function bootDatabaseEnvVars()
    {
        // Set default database config
        $databaseConfig = $this->getDatabaseConfig();
        $databaseConfig['database'] = $this->getDatabaseName();
        DB::setConfig($databaseConfig);
    }

    /**
     * Load database config from environment
     *
     * @return array
     */
    protected function getDatabaseConfig()
    {
        /** @skipUpgrade */
        $databaseConfig = [
            "type" => Environment::getEnv('SS_DATABASE_CLASS') ?: 'MySQLDatabase',
            "server" => Environment::getEnv('SS_DATABASE_SERVER') ?: 'localhost',
            "username" => Environment::getEnv('SS_DATABASE_USERNAME') ?: null,
            "password" => Environment::getEnv('SS_DATABASE_PASSWORD') ?: null,
        ];

        // Set the port if called for
        $dbPort = Environment::getEnv('SS_DATABASE_PORT');
        if ($dbPort) {
            $databaseConfig['port'] = $dbPort;
        }

        // Set the timezone if called for
        $dbTZ = Environment::getEnv('SS_DATABASE_TIMEZONE');
        if ($dbTZ) {
            $databaseConfig['timezone'] = $dbTZ;
        }

        // For schema enabled drivers:
        $dbSchema = Environment::getEnv('SS_DATABASE_SCHEMA');
        if ($dbSchema) {
            $databaseConfig["schema"] = $dbSchema;
        }

        // For SQlite3 memory databases (mainly for testing purposes)
        $dbMemory = Environment::getEnv('SS_DATABASE_MEMORY');
        if ($dbMemory) {
            $databaseConfig["memory"] = $dbMemory;
        }

        // Allow database adapters to handle their own configuration
        DatabaseAdapterRegistry::autoconfigure($databaseConfig);
        return $databaseConfig;
    }

    /**
     * @return string
     */
    protected function getDatabasePrefix()
    {
        return Environment::getEnv('SS_DATABASE_PREFIX') ?: '';
    }

    /**
     * @return string
     */
    protected function getDatabaseSuffix()
    {
        return Environment::getEnv('SS_DATABASE_SUFFIX') ?: '';
    }

    /**
     * Get name of database
     *
     * @return string
     */
    protected function getDatabaseName()
    {
        // Check globals
        global $database;

        if (!empty($database)) {
            return $this->getDatabasePrefix() . $database . $this->getDatabaseSuffix();
        }

        global $databaseConfig;

        if (!empty($databaseConfig['database'])) {
            return $databaseConfig['database']; // Note: Already includes prefix
        }

        // Check environment
        $database = Environment::getEnv('SS_DATABASE_NAME');

        if ($database) {
            return $this->getDatabasePrefix() . $database . $this->getDatabaseSuffix();
        }

        // Auto-detect name
        $chooseName = Environment::getEnv('SS_DATABASE_CHOOSE_NAME');

        if ($chooseName) {
            // Find directory to build name from
            $loopCount = (int)$chooseName;
            $databaseDir = $this->basePath;
            for ($i = 0; $i < $loopCount-1; $i++) {
                $databaseDir = dirname($databaseDir ?? '');
            }

            // Build name
            $database = str_replace('.', '', basename($databaseDir ?? ''));
            $prefix = $this->getDatabasePrefix();

            if ($prefix) {
                $prefix = 'SS_';
            } else {
                // If no prefix, hard-code prefix into database global
                $prefix = '';
                $database = 'SS_' . $database;
            }

            return $prefix . $database;
        }

        // no DB name (may be optional for some connectors)
        return null;
    }

    /**
     * Returns whether the Kernel has been flushed on boot
     *
     * @return bool|null null if the kernel hasn't been booted yet
     */
    public function isFlushed()
    {
        return $this->flush;
    }
}
