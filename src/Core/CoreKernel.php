<?php

namespace SilverStripe\Core;

use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Dev\Install\DatabaseAdapterRegistry;
use SilverStripe\ORM\Connect\NullDatabase;
use SilverStripe\ORM\DB;
use Exception;
use InvalidArgumentException;

/**
 * Simple Kernel container
 */
class CoreKernel extends BaseKernel
{

    protected bool $bootDatabase = true;

    /**
     * Indicates whether the Kernel has been flushed on boot
     */
    private ?bool $flush = null;

    /**
     * Set whether the database should boot or not.
     */
    public function setBootDatabase(bool $bool): static
    {
        $this->bootDatabase = $bool;
        return $this;
    }

    /**
     * @throws HTTPResponse_Exception
     * @throws Exception
     */
    public function boot($flush = false)
    {
        $this->flush = $flush;

        if (!$this->bootDatabase) {
            DB::set_conn(new NullDatabase(), DB::CONN_PRIMARY);
        }

        $this->bootPHP();
        $this->bootManifests($flush);
        $this->bootErrorHandling();
        $this->bootDatabaseEnvVars();
        $this->bootConfigs();
        $this->bootDatabaseGlobals();
        $this->validateDatabase();

        $this->setBooted(true);

        // Flush everything else that can be flushed, now that we're booted.
        if ($flush) {
            foreach (ClassInfo::implementorsOf(Flushable::class) as $class) {
                /** @var Flushable|string $class */
                $class::flush();
            }
        }
    }

    /**
     * Check that the database configuration is valid, throwing an HTTPResponse_Exception if it's not
     *
     * @throws HTTPResponse_Exception
     */
    protected function validateDatabase()
    {
        if (!$this->bootDatabase) {
            return;
        }
        $databaseConfig = DB::getConfig();
        // Fail if no DB is configured
        if (empty($databaseConfig['database'])) {
            $msg = 'Silverstripe Framework requires a "database" key in DB::getConfig(). ' .
                'Did you forget to set SS_DATABASE_NAME or SS_DATABASE_CHOOSE_NAME in your environment?';
            $this->detectLegacyEnvironment();
            throw new HTTPResponse_Exception($msg, 500);
        }
    }

    /**
     * Load database configuration from the $database and $databaseConfig globals
     */
    protected function bootDatabaseGlobals()
    {
        if (!$this->bootDatabase) {
            return;
        }
        // Now that configs have been loaded, we can check global for database config
        global $databaseConfig;
        global $database;

        // Ensure global database config has prefix and suffix applied
        if (!empty($databaseConfig) && !empty($database)) {
            $databaseConfig['database'] = $this->getDatabasePrefix() . $database . $this->getDatabaseSuffix();
        }

        // Set config for primary and any replicas
        for ($i = 0; $i <= DB::MAX_REPLICAS; $i++) {
            if ($i === 0) {
                $name = DB::CONN_PRIMARY;
            } else {
                $name = DB::getReplicaConfigKey($i);
                if (!DB::hasConfig($name)) {
                    break;
                }
            }

            // Case 1: $databaseConfig global exists
            // Only set it if its valid, otherwise ignore $databaseConfig entirely
            if (!empty($databaseConfig) && !empty($databaseConfig['database'])) {
                DB::setConfig($databaseConfig, $name);
                return;
            }

            // Case 2: $databaseConfig global does not exist
            // Merge $database global into existing config
            if (!empty($database)) {
                $dbConfig = DB::getConfig($name);
                $dbConfig['database'] = $this->getDatabasePrefix() . $database . $this->getDatabaseSuffix();
                DB::setConfig($dbConfig, $name);
            }
        }
    }

    /**
     * Load database configuration from environment variables
     */
    protected function bootDatabaseEnvVars()
    {
        if (!$this->bootDatabase) {
            return;
        }
        // Set primary database config
        $databaseConfig = $this->getDatabaseConfig();
        $databaseConfig['database'] = $this->getDatabaseName();
        DB::setConfig($databaseConfig, DB::CONN_PRIMARY);

        // Set database replicas config
        for ($i = 1; $i <= DB::MAX_REPLICAS; $i++) {
            $envKey = $this->getReplicaEnvKey('SS_DATABASE_SERVER', $i);
            if (!Environment::hasEnv($envKey)) {
                break;
            }
            $replicaDatabaseConfig = $this->getDatabaseReplicaConfig($i);
            $configKey = DB::getReplicaConfigKey($i);
            DB::setConfig($replicaDatabaseConfig, $configKey);
        }
    }

    /**
     * Load database config from environment
     *
     * @return array
     */
    protected function getDatabaseConfig()
    {
        return $this->getSingleDataBaseConfig(0);
    }

    private function getDatabaseReplicaConfig(int $replica)
    {
        if ($replica <= 0) {
            throw new InvalidArgumentException('Replica number must be greater than 0');
        }
        return $this->getSingleDataBaseConfig($replica);
    }

    /**
     * Convert a database key to a replica key
     * e.g. SS_DATABASE_SERVER -> SS_DATABASE_SERVER_REPLICA_01
     *
     * @param string $key - The key to look up in the environment
     * @param int $replica - Replica number
     */
    private function getReplicaEnvKey(string $key, int $replica): string
    {
        if ($replica <= 0) {
            throw new InvalidArgumentException('Replica number must be greater than 0');
        }
        // Do not allow replicas to define keys that could lead to unexpected behaviour if
        // they do not match the primary database configuration
        if (in_array($key, ['SS_DATABASE_CLASS', 'SS_DATABASE_NAME', 'SS_DATABASE_CHOOSE_NAME'])) {
            return $key;
        }
        // Left pad replica number with a zeros to match the length of the maximum replica number
        $len = strlen((string) DB::MAX_REPLICAS);
        return $key . '_REPLICA_' . str_pad($replica, $len, '0', STR_PAD_LEFT);
    }

    /**
     * Reads a single database configuration variable from the environment
     * For replica databases, it will first attempt to find replica-specific configuration
     * before falling back to the primary configuration.
     *
     * Replicate specific configuration has `_REPLICA_01` appended to the key
     * where 01 is the replica number.
     *
     * @param string $key - The key to look up in the environment
     * @param int $replica - Replica number. Passing 0 will return the primary database configuration
     */
    private function getDatabaseConfigVariable(string $key, int $replica): string
    {
        if ($replica > 0) {
            $key = $this->getReplicaEnvKey($key, $replica);
        }
        if (Environment::hasEnv($key)) {
            return Environment::getEnv($key);
        }
        return '';
    }

    /**
     * @param int $replica - Replica number. Passing 0 will return the primary database configuration
     */
    private function getSingleDataBaseConfig(int $replica): array
    {
        $databaseConfig = [
            "type" => $this->getDatabaseConfigVariable('SS_DATABASE_CLASS', $replica) ?: 'MySQLDatabase',
            "server" => $this->getDatabaseConfigVariable('SS_DATABASE_SERVER', $replica) ?: 'localhost',
            "username" => $this->getDatabaseConfigVariable('SS_DATABASE_USERNAME', $replica) ?: null,
            "password" => $this->getDatabaseConfigVariable('SS_DATABASE_PASSWORD', $replica) ?: null,
        ];

        // Only add SSL keys in the array if there is an actual value associated with them
        $sslConf = [
            'ssl_key' => 'SS_DATABASE_SSL_KEY',
            'ssl_cert' => 'SS_DATABASE_SSL_CERT',
            'ssl_ca' => 'SS_DATABASE_SSL_CA',
            'ssl_cipher' => 'SS_DATABASE_SSL_CIPHER',
        ];
        foreach ($sslConf as $key => $envVar) {
            $envValue = $this->getDatabaseConfigVariable($envVar, $replica);
            if ($envValue) {
                $databaseConfig[$key] = $envValue;
            }
        }

        // Having only the key or cert without the other is bad configuration.
        if ((isset($databaseConfig['ssl_key']) && !isset($databaseConfig['ssl_cert']))
            || (!isset($databaseConfig['ssl_key']) && isset($databaseConfig['ssl_cert']))
        ) {
            user_error('Database SSL cert and key must both be defined to use SSL in the database.', E_USER_WARNING);
            unset($databaseConfig['ssl_key']);
            unset($databaseConfig['ssl_cert']);
        }

        // Set the port if called for
        $dbPort = $this->getDatabaseConfigVariable('SS_DATABASE_PORT', $replica);
        if ($dbPort) {
            $databaseConfig['port'] = $dbPort;
        }

        // Set the timezone if called for
        $dbTZ = $this->getDatabaseConfigVariable('SS_DATABASE_TIMEZONE', $replica);
        if ($dbTZ) {
            $databaseConfig['timezone'] = $dbTZ;
        }

        // For schema enabled drivers:
        $dbSchema = $this->getDatabaseConfigVariable('SS_DATABASE_SCHEMA', $replica);
        if ($dbSchema) {
            $databaseConfig["schema"] = $dbSchema;
        }

        // For SQlite3 memory databases (mainly for testing purposes)
        $dbMemory = $this->getDatabaseConfigVariable('SS_DATABASE_MEMORY', $replica);
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
     * Note that any replicas must have the same database name as the primary database
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

    public function isFlushed(): ?bool
    {
        return $this->flush;
    }
}
