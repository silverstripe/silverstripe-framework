<?php

namespace SilverStripe\ORM\Connect;

use Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class TempDatabase
{
    use Injectable;

    /**
     * Connection name
     *
     * @var string
     */
    protected $name = null;

    /**
     * Create a new temp database
     *
     * @param string $name DB Connection name to use
     */
    public function __construct($name = 'default')
    {
        $this->name = $name;
    }

    /**
     * Check if the given name matches the temp_db pattern
     *
     * @param string $name
     * @return bool
     */
    protected function isDBTemp($name)
    {
        $prefix = Environment::getEnv('SS_DATABASE_PREFIX') ?: 'ss_';
        $result = preg_match(
            sprintf('/^%stmpdb_[0-9]+_[0-9]+$/i', preg_quote($prefix, '/')),
            $name
        );
        return $result === 1;
    }

    /**
     * @return Database
     */
    protected function getConn()
    {
        return DB::get_conn($this->name);
    }

    /**
     * Returns true if we are currently using a temporary database
     *
     * @return bool
     */
    public function isUsed()
    {
        $selected = $this->getConn()->getSelectedDatabase();
        return $this->isDBTemp($selected);
    }

    /**
     * @return bool
     */
    public function supportsTransactions()
    {
        return static::getConn()->supportsTransactions();
    }

    /**
     * Start a transaction for easy rollback after tests
     */
    public function startTransaction()
    {
        if (static::getConn()->supportsTransactions()) {
            static::getConn()->transactionStart();
        }
    }

    /**
     * Rollback a transaction (or trash all data if the DB doesn't support databases
     *
     * @return bool True if successfully rolled back, false otherwise. On error the DB is
     * killed and must be re-created. Note that calling rollbackTransaction() when there
     * is no transaction is counted as a failure, user code should either kill or flush the DB
     * as necessary
     */
    public function rollbackTransaction()
    {
        // Ensure a rollback can be performed
        $success = static::getConn()->supportsTransactions()
            && static::getConn()->transactionDepth();
        if (!$success) {
            return false;
        }
        try {
            // Explicit false = gnostic error from transactionRollback
            if (static::getConn()->transactionRollback() === false) {
                return false;
            }
            return true;
        } catch (DatabaseException $ex) {
            return false;
        }
    }

    /**
     * Destroy the current temp database
     */
    public function kill()
    {
        // Nothing to kill
        if (!$this->isUsed()) {
            return;
        }

        // Rollback any transactions (note: Success ignored)
        $this->rollbackTransaction();

        // Check the database actually exists
        $dbConn = $this->getConn();
        $dbName = $dbConn->getSelectedDatabase();
        if (!$dbConn->databaseExists($dbName)) {
            return;
        }

        // Some DataExtensions keep a static cache of information that needs to
        // be reset whenever the database is killed
        foreach (ClassInfo::subclassesFor(DataExtension::class) as $class) {
            $toCall = array($class, 'on_db_reset');
            if (is_callable($toCall)) {
                call_user_func($toCall);
            }
        }

        $dbConn->dropSelectedDatabase();
    }

    /**
     * Remove all content from the temporary database.
     */
    public function clearAllData()
    {
        if (!$this->isUsed()) {
            return;
        }

        $this->getConn()->clearAllData();

        // Some DataExtensions keep a static cache of information that needs to
        // be reset whenever the database is cleaned out
        $classes = array_merge(
            ClassInfo::subclassesFor(DataExtension::class),
            ClassInfo::subclassesFor(DataObject::class)
        );
        foreach ($classes as $class) {
            $toCall = array($class, 'on_db_reset');
            if (is_callable($toCall)) {
                call_user_func($toCall);
            }
        }
    }

    /**
     * Create temp DB without creating extra objects
     *
     * @return string DB name
     */
    public function build()
    {
        // Disable PHPUnit error handling
        $oldErrorHandler = set_error_handler(null);

        // Create a temporary database, and force the connection to use UTC for time
        $dbConn = $this->getConn();
        $prefix = Environment::getEnv('SS_DATABASE_PREFIX') ?: 'ss_';
        do {
            $dbname = strtolower(sprintf('%stmpdb_%s_%s', $prefix, time(), rand(1000000, 9999999)));
        } while ($dbConn->databaseExists($dbname));

        $dbConn->selectDatabase($dbname, true);

        $this->resetDBSchema();

        // Reinstate PHPUnit error handling
        set_error_handler($oldErrorHandler);

        // Ensure test db is killed on exit
        register_shutdown_function(function () {
            try {
                $this->kill();
            } catch (Exception $ex) {
                // An exception thrown while trying to remove a test database shouldn't fail a build, ignore
            }
        });

        return $dbname;
    }

    /**
     * Rebuild all database tables
     *
     * @param array $extraDataObjects
     */
    protected function rebuildTables($extraDataObjects = [])
    {
        DataObject::reset();

        // clear singletons, they're caching old extension info which is used in DatabaseAdmin->doBuild()
        Injector::inst()->unregisterObjects(DataObject::class);

        $dataClasses = ClassInfo::subclassesFor(DataObject::class);
        array_shift($dataClasses);

        $schema = $this->getConn()->getSchemaManager();
        $schema->quiet();
        $schema->schemaUpdate(function () use ($dataClasses, $extraDataObjects) {
            foreach ($dataClasses as $dataClass) {
                // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                if (class_exists($dataClass)) {
                    $SNG = singleton($dataClass);
                    if (!($SNG instanceof TestOnly)) {
                        $SNG->requireTable();
                    }
                }
            }

            // If we have additional dataobjects which need schema, do so here:
            if ($extraDataObjects) {
                foreach ($extraDataObjects as $dataClass) {
                    $SNG = singleton($dataClass);
                    if (singleton($dataClass) instanceof DataObject) {
                        $SNG->requireTable();
                    }
                }
            }
        });

        ClassInfo::reset_db_cache();
        DataObject::singleton()->flushCache();
    }

    /**
     * Clear all temp DBs on this connection
     *
     * Note: This will output results to stdout unless suppressOutput
     * is set on the current db schema
     */
    public function deleteAll()
    {
        $schema = $this->getConn()->getSchemaManager();
        foreach ($schema->databaseList() as $dbName) {
            if ($this->isDBTemp($dbName)) {
                $schema->dropDatabase($dbName);
                $schema->alterationMessage("Dropped database \"$dbName\"", 'deleted');
                flush();
            }
        }
    }

    /**
     * Reset the testing database's schema.
     *
     * @param array $extraDataObjects List of extra dataobjects to build
     */
    public function resetDBSchema(array $extraDataObjects = [])
    {
        // Skip if no DB
        if (!$this->isUsed()) {
            return;
        }

        try {
            $this->rebuildTables($extraDataObjects);
        } catch (DatabaseException $ex) {
            // In case of error during build force a hard reset
            // e.g. pgsql doesn't allow schema updates inside transactions
            $this->kill();
            $this->build();
            $this->rebuildTables($extraDataObjects);
        }
    }
}
