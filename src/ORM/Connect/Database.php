<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\ORM\Queries\SQLInsert;
use BadMethodCallException;
use Exception;
use SilverStripe\Dev\Backtrace;

/**
 * Abstract database connectivity class.
 * Sub-classes of this implement the actual database connection libraries
 */
abstract class Database
{

    const PARTIAL_QUERY = 'partial_query';
    const FULL_QUERY = 'full_query';

    /**
     * To use, call from _config.php
     * Example:
     * <code>
     * Database::setWhitelistQueryArray([
     *      'Qualmark' => 'partial_query',
     *      'SELECT "Version" FROM "SiteTree_Live" WHERE "ID" = ?' => 'full_query',
     * ])
     * </code>
     * @var array
     */
    protected static $whitelist_array = [];

    /**
     * Database connector object
     *
     * @var DBConnector
     */
    protected $connector = null;

    /**
     * In cases where your environment does not have 'SHOW DATABASES' permission,
     * you can set this to true. Then selectDatabase() will always connect without
     * doing databaseExists() check.
     *
     * @var bool
     */
    private static $optimistic_connect = false;

    /**
     * Amount of queries executed, for debugging purposes.
     *
     * @var int
     */
    protected $queryCount = 0;

    /**
     * Get the current connector
     *
     * @return DBConnector
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Injector injection point for connector dependency
     *
     * @param DBConnector $connector
     */
    public function setConnector(DBConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Database schema manager object
     *
     * @var DBSchemaManager
     */
    protected $schemaManager = null;

    /**
     * Returns the current schema manager
     *
     * @return DBSchemaManager
     */
    public function getSchemaManager()
    {
        return $this->schemaManager;
    }

    /**
     * Injector injection point for schema manager
     *
     * @param DBSchemaManager $schemaManager
     */
    public function setSchemaManager(DBSchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;

        if ($this->schemaManager) {
            $this->schemaManager->setDatabase($this);
        }
    }

    /**
     * Query builder object
     *
     * @var DBQueryBuilder
     */
    protected $queryBuilder = null;

    /**
     * Returns the current query builder
     *
     * @return DBQueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Injector injection point for schema manager
     *
     * @param DBQueryBuilder $queryBuilder
     */
    public function setQueryBuilder(DBQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Execute the given SQL query.
     *
     * @param string $sql The SQL query to execute
     * @param int $errorLevel The level of error reporting to enable for the query
     * @return Query
     */
    public function query($sql, $errorLevel = E_USER_ERROR)
    {
        // Check if we should only preview this query
        if ($this->previewWrite($sql)) {
            return null;
        }

        // Benchmark query
        $connector = $this->connector;
        return $this->benchmarkQuery(
            $sql,
            function ($sql) use ($connector, $errorLevel) {
                return $connector->query($sql, $errorLevel);
            }
        );
    }


    /**
     * Execute the given SQL parameterised query with the specified arguments
     *
     * @param string $sql The SQL query to execute. The ? character will denote parameters.
     * @param array $parameters An ordered list of arguments.
     * @param int $errorLevel The level of error reporting to enable for the query
     * @return Query
     */
    public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR)
    {
        // Check if we should only preview this query
        if ($this->previewWrite($sql)) {
            return null;
        }

        // Benchmark query
        $connector = $this->connector;
        return $this->benchmarkQuery(
            $sql,
            function ($sql) use ($connector, $parameters, $errorLevel) {
                return $connector->preparedQuery($sql, $parameters, $errorLevel);
            },
            $parameters
        );
    }

    /**
     * Determines if the query should be previewed, and thus interrupted silently.
     * If so, this function also displays the query via the debugging system.
     * Subclasess should respect the results of this call for each query, and not
     * execute any queries that generate a true response.
     *
     * @param string $sql The query to be executed
     * @return boolean Flag indicating that the query was previewed
     */
    protected function previewWrite($sql)
    {
        // Only preview if previewWrite is set, we are in dev mode, and
        // the query is mutable
        if (isset($_REQUEST['previewwrite'])
            && Director::isDev()
            && $this->connector->isQueryMutable($sql)
        ) {
            // output preview message
            Debug::message("Will execute: $sql");
            return true;
        } else {
            return false;
        }
    }

    /**
     * Allows the display and benchmarking of queries as they are being run
     *
     * @param string $sql Query to run, and single parameter to callback
     * @param callable $callback Callback to execute code
     * @param array $parameters Parameters for any parameterised query
     * @return mixed Result of query
     */
    protected function benchmarkQuery($sql, $callback, $parameters = [])
    {
        if (isset($_REQUEST['showqueries']) && Director::isDev()) {
            $displaySql = true;
            $this->queryCount++;
            $starttime = microtime(true);
            $result = $callback($sql);
            $endtime = round(microtime(true) - $starttime, 4);
            // replace parameters as closely as possible to what we'd expect the DB to put in
            if (in_array(strtolower($_REQUEST['showqueries'] ?? ''), ['inline', 'backtrace'])) {
                $sql = DB::inline_parameters($sql, $parameters);
            } elseif (strtolower($_REQUEST['showqueries'] ?? '') === 'whitelist') {
                $displaySql = false;
                foreach (Database::$whitelist_array as $query => $searchType) {
                    $fullQuery = ($searchType === Database::FULL_QUERY && $query === $sql);
                    $partialQuery = ($searchType === Database::PARTIAL_QUERY && mb_strpos($sql ?? '', $query ?? '') !== false);
                    if (!$fullQuery && !$partialQuery) {
                        continue;
                    }
                    $sql = DB::inline_parameters($sql, $parameters);
                    $this->displayQuery($sql, $endtime);
                }
            }

            if ($displaySql) {
                $this->displayQuery($sql, $endtime);
            }

            // Show a backtrace if ?showqueries=backtrace
            if ($_REQUEST['showqueries'] === 'backtrace') {
                Backtrace::backtrace();
            }
            return $result;
        }
        return $callback($sql);
    }

    /**
     * Display query message
     *
     * @param mixed $query
     * @param float $endtime
     */
    protected function displayQuery($query, $endtime)
    {
        $queryCount = sprintf("%04d", $this->queryCount);
        Debug::message("\n$queryCount: $query\n{$endtime}s\n", false);
    }

    /**
     * Add the sql queries that need to be partially or fully matched
     *
     * @param array $whitelistArray
     */
    public static function setWhitelistQueryArray($whitelistArray)
    {
        Database::$whitelist_array = $whitelistArray;
    }

    /**
     * Get the sql queries that need to be partially or fully matched
     *
     * @return array
     */
    public static function getWhitelistQueryArray()
    {
        return Database::$whitelist_array;
    }

    /**
     * Get the autogenerated ID from the previous INSERT query.
     *
     * @param string $table The name of the table to get the generated ID for
     * @return integer the most recently generated ID for the specified table
     */
    public function getGeneratedID($table)
    {
        return $this->connector->getGeneratedID($table);
    }

    /**
     * Determines if we are connected to a server AND have a valid database
     * selected.
     *
     * @return boolean Flag indicating that a valid database is connected
     */
    public function isActive()
    {
        return $this->connector->isActive();
    }

    /**
     * Returns an escaped string. This string won't be quoted, so would be suitable
     * for appending to other quoted strings.
     *
     * @param mixed $value Value to be prepared for database query
     * @return string Prepared string
     */
    public function escapeString($value)
    {
        return $this->connector->escapeString($value);
    }

    /**
     * Wrap a string into DB-specific quotes.
     *
     * @param mixed $value Value to be prepared for database query
     * @return string Prepared string
     */
    public function quoteString($value)
    {
        return $this->connector->quoteString($value);
    }

    /**
     * Escapes an identifier (table / database name). Typically the value
     * is simply double quoted. Don't pass in already escaped identifiers in,
     * as this will double escape the value!
     *
     * @param string|array $value The identifier to escape or list of split components
     * @param string $separator Splitter for each component
     * @return string
     */
    public function escapeIdentifier($value, $separator = '.')
    {
        // Split string into components
        if (!is_array($value)) {
            $value = explode($separator ?? '', $value ?? '');
        }

        // Implode quoted column
        return '"' . implode('"' . $separator . '"', $value) . '"';
    }

    /**
     * Escapes unquoted columns keys in an associative array
     *
     * @param array $fieldValues
     * @return array List of field values with the keys as escaped column names
     */
    protected function escapeColumnKeys($fieldValues)
    {
        $out = [];
        foreach ($fieldValues as $field => $value) {
            $out[$this->escapeIdentifier($field)] = $value;
        }
        return $out;
    }

    /**
     * Execute a complex manipulation on the database.
     * A manipulation is an array of insert / or update sequences.  The keys of the array are table names,
     * and the values are map containing 'command' and 'fields'.  Command should be 'insert' or 'update',
     * and fields should be a map of field names to field values, NOT including quotes.
     *
     * The field values could also be in paramaterised format, such as
     * array('MAX(?,?)' => array(42, 69)), allowing the use of raw SQL values such as
     * array('NOW()' => array()).
     *
     * @see SQLWriteExpression::addAssignments for syntax examples
     *
     * @param array $manipulation
     */
    public function manipulate($manipulation)
    {
        if (empty($manipulation)) {
            return;
        }

        foreach ($manipulation as $table => $writeInfo) {
            if (empty($writeInfo['fields'])) {
                continue;
            }
            // Note: keys of $fieldValues are not escaped
            $fieldValues = $writeInfo['fields'];

            // Switch command type
            switch ($writeInfo['command']) {
                case "update":
                    // Build update
                    $query = new SQLUpdate("\"$table\"", $this->escapeColumnKeys($fieldValues));

                    // Set best condition to use
                    if (!empty($writeInfo['where'])) {
                        $query->addWhere($writeInfo['where']);
                    } elseif (!empty($writeInfo['id'])) {
                        $query->addWhere(['"ID"' => $writeInfo['id']]);
                    }

                    // Test to see if this update query shouldn't, in fact, be an insert
                    if ($query->toSelect()->count()) {
                        $query->execute();
                        break;
                    }
                    // ...if not, we'll skip on to the insert code

                case "insert":
                    // Ensure that the ID clause is given if possible
                    if (!isset($fieldValues['ID']) && isset($writeInfo['id'])) {
                        $fieldValues['ID'] = $writeInfo['id'];
                    }

                    // Build insert
                    $query = new SQLInsert("\"$table\"", $this->escapeColumnKeys($fieldValues));

                    $query->execute();
                    break;

                default:
                    throw new \InvalidArgumentException(
                        "SS_Database::manipulate() Can't recognise command '{$writeInfo['command']}'"
                    );
            }
        }
    }

    /**
     * Enable suppression of database messages.
     */
    public function quiet()
    {
        $this->schemaManager->quiet();
    }

    /**
     * Clear all data out of the database
     */
    public function clearAllData()
    {
        $tables = $this->getSchemaManager()->tableList();
        foreach ($tables as $table) {
            $this->clearTable($table);
        }
    }

    /**
     * Clear all data in a given table
     *
     * @param string $table Name of table
     */
    public function clearTable($table)
    {
        $this->query("TRUNCATE \"$table\"");
    }

    /**
     * Generates a WHERE clause for null comparison check
     *
     * @param string $field Quoted field name
     * @param bool $isNull Whether to check for NULL or NOT NULL
     * @return string Non-parameterised null comparison clause
     */
    public function nullCheckClause($field, $isNull)
    {
        $clause = $isNull
            ? "%s IS NULL"
            : "%s IS NOT NULL";
        return sprintf($clause ?? '', $field);
    }

    /**
     * Generate a WHERE clause for text matching.
     *
     * @param string $field Quoted field name
     * @param string $value Escaped search. Can include percentage wildcards.
     * Ignored if $parameterised is true.
     * @param boolean $exact Exact matches or wildcard support.
     * @param boolean $negate Negate the clause.
     * @param boolean $caseSensitive Enforce case sensitivity if TRUE or FALSE.
     * Fallback to default collation if set to NULL.
     * @param boolean $parameterised Insert the ? placeholder rather than the
     * given value. If this is true then $value is ignored.
     * @return String SQL
     */
    abstract public function comparisonClause(
        $field,
        $value,
        $exact = false,
        $negate = false,
        $caseSensitive = null,
        $parameterised = false
    );

    /**
     * function to return an SQL datetime expression that can be used with the adapter in use
     * used for querying a datetime in a certain format
     *
     * @param string $date to be formatted, can be either 'now', literal datetime like '1973-10-14 10:30:00' or
     *                     field name, e.g. '"SiteTree"."Created"'
     * @param string $format to be used, supported specifiers:
     * %Y = Year (four digits)
     * %m = Month (01..12)
     * %d = Day (01..31)
     * %H = Hour (00..23)
     * %i = Minutes (00..59)
     * %s = Seconds (00..59)
     * %U = unix timestamp, can only be used on it's own
     * @return string SQL datetime expression to query for a formatted datetime
     */
    abstract public function formattedDatetimeClause($date, $format);

    /**
     * function to return an SQL datetime expression that can be used with the adapter in use
     * used for querying a datetime addition
     *
     * @param string $date can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name,
     *                      e.g. '"SiteTree"."Created"'
     * @param string $interval to be added, use the format [sign][integer] [qualifier], e.g. -1 Day, +15 minutes,
     *                         +1 YEAR
     * supported qualifiers:
     * - years
     * - months
     * - days
     * - hours
     * - minutes
     * - seconds
     * This includes the singular forms as well
     * @return string SQL datetime expression to query for a datetime (YYYY-MM-DD hh:mm:ss) which is the result of
     *                the addition
     */
    abstract public function datetimeIntervalClause($date, $interval);

    /**
     * function to return an SQL datetime expression that can be used with the adapter in use
     * used for querying a datetime subtraction
     *
     * @param string $date1 can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name
     *                       e.g. '"SiteTree"."Created"'
     * @param string $date2 to be subtracted of $date1, can be either 'now', literal datetime
     *                      like '1973-10-14 10:30:00' or field name, e.g. '"SiteTree"."Created"'
     * @return string SQL datetime expression to query for the interval between $date1 and $date2 in seconds which
     *                is the result of the subtraction
     */
    abstract public function datetimeDifferenceClause($date1, $date2);

    /**
     * String operator for concatenation of strings
     *
     * @return string
     */
    public function concatOperator()
    {
        return ' || ';
    }

    /**
     * Returns true if this database supports collations
     *
     * @return boolean
     */
    abstract public function supportsCollations();

    /**
     * Can the database override timezone as a connection setting,
     * or does it use the system timezone exclusively?
     *
     * @return Boolean
     */
    abstract public function supportsTimezoneOverride();

    /**
     * Query for the version of the currently connected database
     * @return string Version of this database
     */
    public function getVersion()
    {
        return $this->connector->getVersion();
    }

    /**
     * Get the database server type (e.g. mysql, postgresql).
     * This value is passed to the connector as the 'driver' argument when
     * initiating a database connection
     *
     * @return string
     */
    abstract public function getDatabaseServer();

    /**
     * Return the number of rows affected by the previous operation.
     * @return int
     */
    public function affectedRows()
    {
        return $this->connector->affectedRows();
    }

    /**
     * The core search engine, used by this class and its subclasses to do fun stuff.
     * Searches both SiteTree and File.
     *
     * @param array $classesToSearch List of classes to search
     * @param string $keywords Keywords as a string.
     * @param integer $start Item to start returning results from
     * @param integer $pageLength Number of items per page
     * @param string $sortBy Sort order expression
     * @param string $extraFilter Additional filter
     * @param boolean $booleanSearch Flag for boolean search mode
     * @param string $alternativeFileFilter
     * @param boolean $invertedMatch
     * @return PaginatedList Search results
     */
    abstract public function searchEngine(
        $classesToSearch,
        $keywords,
        $start,
        $pageLength,
        $sortBy = "Relevance DESC",
        $extraFilter = "",
        $booleanSearch = false,
        $alternativeFileFilter = "",
        $invertedMatch = false
    );

    /**
     * Determines if this database supports Common Table Expression (aka WITH) clauses.
     * By default it is assumed that it doesn't unless this method is explicitly overridden.
     *
     * @param bool $recursive if true, checks specifically if recursive CTEs are supported.
     */
    public function supportsCteQueries(bool $recursive = false): bool
    {
        return false;
    }

    /**
     * Determines if this database supports transactions
     *
     * @return boolean Flag indicating support for transactions
     */
    abstract public function supportsTransactions();

    /**
     * Does this database support savepoints in transactions
     * By default it is assumed that they don't unless they are explicitly enabled.
     *
     * @return boolean Flag indicating support for savepoints in transactions
     */
    public function supportsSavepoints()
    {
        return false;
    }

    /**
     * Determines if the used database supports given transactionMode as an argument to startTransaction()
     * If transactions are completely unsupported, returns false.
     *
     * @param string $mode
     * @return bool
     */
    public function supportsTransactionMode(string $mode): bool
    {
        // Default implementation: assume all modes are a supported.
        return $this->supportsTransactions();
    }

    /**
     * Invoke $callback within a transaction
     *
     * @param callable $callback Callback to run
     * @param callable $errorCallback Optional callback to run after rolling back transaction.
     * @param bool|string $transactionMode Optional transaction mode to use
     * @param bool $errorIfTransactionsUnsupported If true, this method will fail if transactions are unsupported.
     * Otherwise, the $callback will potentially be invoked outside of a transaction.
     * @throws Exception
     */
    public function withTransaction(
        $callback,
        $errorCallback = null,
        $transactionMode = false,
        $errorIfTransactionsUnsupported = false
    ) {
        $supported = $this->supportsTransactions();
        if (!$supported && $errorIfTransactionsUnsupported) {
            throw new BadMethodCallException("Transactions not supported by this database.");
        }
        if ($supported) {
            $this->transactionStart($transactionMode);
        }
        try {
            call_user_func($callback);
        } catch (Exception $ex) {
            if ($supported) {
                $this->transactionRollback();
            }
            if ($errorCallback) {
                call_user_func($errorCallback);
            }
            throw $ex;
        }
        if ($supported) {
            $this->transactionEnd();
        }
    }

    /*
     * Determines if the current database connection supports a given list of extensions
     *
     * @param array $extensions List of extensions to check for support of. The key of this array
     * will be an extension name, and the value the configuration for that extension. This
     * could be one of partitions, tablespaces, or clustering
     * @return boolean Flag indicating support for all of the above
     */
    public function supportsExtensions($extensions)
    {
        return false;
    }

    /**
     * Start a prepared transaction
     * See http://developer.postgresql.org/pgdocs/postgres/sql-set-transaction.html for details on
     * transaction isolation options
     *
     * @param string|boolean $transactionMode Transaction mode, or false to ignore
     * @param string|boolean $sessionCharacteristics Session characteristics, or false to ignore
     */
    abstract public function transactionStart($transactionMode = false, $sessionCharacteristics = false);

    /**
     * Create a savepoint that you can jump back to if you encounter problems
     *
     * @param string $savepoint Name of savepoint
     */
    abstract public function transactionSavepoint($savepoint);

    /**
     * Rollback or revert to a savepoint if your queries encounter problems
     * If you encounter a problem at any point during a transaction, you may
     * need to rollback that particular query, or return to a savepoint
     *
     * @param string|boolean $savepoint Name of savepoint, or leave empty to rollback
     * to last savepoint
     * @return bool|null Boolean is returned if success state is known, or null if
     * unknown. Note: For error checking purposes null should not be treated as error.
     */
    abstract public function transactionRollback($savepoint = false);

    /**
     * Commit everything inside this transaction so far
     *
     * Boolean is returned if success state is known, or null if
     * unknown. Note: For error checking purposes null should not be treated as error.
     */
    abstract public function transactionEnd(): bool|null;

    /**
     * Return depth of current transaction
     *
     * @return int Nesting level, or 0 if not in a transaction
     */
    public function transactionDepth()
    {
        // Placeholder error for transactional DBs that don't expose depth
        if ($this->supportsTransactions()) {
            user_error(get_class($this) . " does not support transactionDepth", E_USER_WARNING);
        }
        return 0;
    }

    /**
     * Determines if the used database supports application-level locks,
     * which is different from table- or row-level locking.
     * See {@link getLock()} for details.
     *
     * @return bool Flag indicating that locking is available
     */
    public function supportsLocks()
    {
        return false;
    }

    /**
     * Returns if the lock is available.
     * See {@link supportsLocks()} to check if locking is generally supported.
     *
     * @param string $name Name of the lock
     * @return bool
     */
    public function canLock($name)
    {
        return false;
    }

    /**
     * Sets an application-level lock so that no two processes can run at the same time,
     * also called a "cooperative advisory lock".
     *
     * Return FALSE if acquiring the lock fails; otherwise return TRUE, if lock was acquired successfully.
     * Lock is automatically released if connection to the database is broken (either normally or abnormally),
     * making it less prone to deadlocks than session- or file-based locks.
     * Should be accompanied by a {@link releaseLock()} call after the logic requiring the lock has completed.
     * Can be called multiple times, in which case locks "stack" (PostgreSQL, SQL Server),
     * or auto-releases the previous lock (MySQL).
     *
     * Note that this might trigger the database to wait for the lock to be released, delaying further execution.
     *
     * @param string $name Name of lock
     * @param integer $timeout Timeout in seconds
     * @return bool
     */
    public function getLock($name, $timeout = 5)
    {
        return false;
    }

    /**
     * Remove an application-level lock file to allow another process to run
     * (if the execution aborts (e.g. due to an error) all locks are automatically released).
     *
     * @param string $name Name of the lock
     * @return bool Flag indicating whether the lock was successfully released
     */
    public function releaseLock($name)
    {
        return false;
    }

    /**
     * Instruct the database to generate a live connection
     *
     * @param array $parameters An map of parameters, which should include:
     *  - server: The server, eg, localhost
     *  - username: The username to log on with
     *  - password: The password to log on with
     *  - database: The database to connect to
     *  - charset: The character set to use. Defaults to utf8
     *  - timezone: (optional) The timezone offset. For example: +12:00, "Pacific/Auckland", or "SYSTEM"
     *  - driver: (optional) Driver name
     */
    public function connect($parameters)
    {
        // Notify connector of parameters
        $this->connector->connect($parameters);

        // SS_Database subclass maintains responsibility for selecting database
        // once connected in order to correctly handle schema queries about
        // existence of database, error handling at the correct level, etc
        if (!empty($parameters['database'])) {
            $this->selectDatabase($parameters['database'], false, false);
        }
    }

    /**
     * Determine if the database with the specified name exists
     *
     * @param string $name Name of the database to check for
     * @return bool Flag indicating whether this database exists
     */
    public function databaseExists($name)
    {
        return $this->schemaManager->databaseExists($name);
    }

    /**
     * Retrieves the list of all databases the user has access to
     *
     * @return array List of database names
     */
    public function databaseList()
    {
        return $this->schemaManager->databaseList();
    }

    /**
     * Change the connection to the specified database, optionally creating the
     * database if it doesn't exist in the current schema.
     *
     * @param string $name Name of the database
     * @param bool $create Flag indicating whether the database should be created
     * if it doesn't exist. If $create is false and the database doesn't exist
     * then an error will be raised
     * @param int|bool $errorLevel The level of error reporting to enable for the query, or false if no error
     * should be raised
     * @return bool Flag indicating success
     */
    public function selectDatabase($name, $create = false, $errorLevel = E_USER_ERROR)
    {
        // In case our live environment is locked down, we can bypass a SHOW DATABASE check
        $canConnect = Config::inst()->get(static::class, 'optimistic_connect')
            || $this->schemaManager->databaseExists($name);
        if ($canConnect) {
            return $this->connector->selectDatabase($name);
        }

        // Check DB creation permission
        if (!$create) {
            if ($errorLevel !== false) {
                user_error("Attempted to connect to non-existing database \"$name\"", $errorLevel ?? 0);
            }
            // Unselect database
            $this->connector->unloadDatabase();
            return false;
        }
        $this->schemaManager->createDatabase($name);
        return $this->connector->selectDatabase($name);
    }

    /**
     * Drop the database that this object is currently connected to.
     * Use with caution.
     */
    public function dropSelectedDatabase()
    {
        $databaseName = $this->connector->getSelectedDatabase();
        if ($databaseName) {
            $this->connector->unloadDatabase();
            $this->schemaManager->dropDatabase($databaseName);
        }
    }

    /**
     * Returns the name of the currently selected database
     *
     * @return string|null Name of the selected database, or null if none selected
     */
    public function getSelectedDatabase()
    {
        return $this->connector->getSelectedDatabase();
    }

    /**
     * Return SQL expression used to represent the current date/time
     *
     * @return string Expression for the current date/time
     */
    abstract public function now();

    /**
     * Returns the database-specific version of the random() function
     *
     * @return string Expression for a random value
     */
    abstract public function random();
}
