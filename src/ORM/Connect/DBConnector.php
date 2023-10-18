<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\Parsers\SQLFormatter;

/**
 * Represents an object responsible for wrapping DB connector api
 */
abstract class DBConnector
{

    use Configurable;

    /**
     * List of operations to treat as write
     * Implicitly includes all ddl_operations
     *
     * @config
     * @var array
     */
    private static $write_operations = ['insert', 'update', 'delete', 'replace'];

    /**
     * List of operations to treat as DDL
     *
     * @config
     * @var array
     */
    private static $ddl_operations = ['alter', 'drop', 'create', 'truncate'];

    /**
     * Error handler for database errors.
     * All database errors will call this function to report the error.  It isn't a static function;
     * it will be called on the object itself and as such can be overridden in a subclass.
     * Subclasses should run all errors through this function.
     *
     * @param string $msg The error message.
     * @param integer $errorLevel The level of the error to throw.
     * @param string $sql The SQL related to this query
     * @param array $parameters Parameters passed to the query
     * @throws DatabaseException
     */
    protected function databaseError($msg, $errorLevel = E_USER_ERROR, $sql = null, $parameters = [])
    {
        // Prevent errors when error checking is set at zero level
        if (empty($errorLevel)) {
            return;
        }

        // Format query if given
        if (!empty($sql)) {
            $formatter = new SQLFormatter();
            $formattedSQL = $formatter->formatPlain($sql);
            $msg = "Couldn't run query:\n\n{$formattedSQL}\n\n{$msg}";
        }

        if ($errorLevel === E_USER_ERROR) {
            // Treating errors as exceptions better allows for responding to errors
            // in code, such as credential checking during installation
            throw new DatabaseException($msg, 0, null, $sql, $parameters);
        } else {
            user_error($msg ?? '', $errorLevel ?? 0);
        }
    }

    /**
     * Determine if this SQL statement is a destructive operation (write or ddl)
     *
     * @param string $sql
     * @return bool
     */
    public function isQueryMutable($sql)
    {
        $operations = array_merge(
            Config::inst()->get(static::class, 'write_operations'),
            Config::inst()->get(static::class, 'ddl_operations')
        );
        return $this->isQueryType($sql, $operations);
    }

    /**
     * Determine if this SQL statement is a DDL operation
     *
     * @param string $sql
     * @return bool
     */
    public function isQueryDDL($sql)
    {
        $operations = Config::inst()->get(static::class, 'ddl_operations');
        return $this->isQueryType($sql, $operations);
    }

    /**
     * Determine if this SQL statement is a write operation
     * (alters content but not structure)
     *
     * @param string $sql
     * @return bool
     */
    public function isQueryWrite($sql)
    {
        $operations = Config::inst()->get(static::class, 'write_operations');
        return $this->isQueryType($sql, $operations);
    }

    /**
     * Determine if a query is of the given type
     *
     * @param string $sql Raw SQL
     * @param string|array $type Type or list of types (first word in the query). Must be lowercase
     * @return bool
     */
    protected function isQueryType($sql, $type)
    {
        if (!preg_match('/^(?<operation>\w+)\b/', $sql ?? '', $matches)) {
            return false;
        }
        $operation = $matches['operation'];
        if (is_array($type)) {
            return in_array(strtolower($operation ?? ''), $type ?? []);
        }
        return strcasecmp($sql ?? '', $type ?? '') === 0;
    }

    /**
     * Extracts only the parameter values for error reporting
     *
     * @param array $parameters
     * @return array List of parameter values
     */
    protected function parameterValues($parameters)
    {
        $values = [];
        foreach ($parameters as $value) {
            $values[] = is_array($value) ? $value['value'] : $value;
        }
        return $values;
    }

    /**
     * Link this connector to the database given the specified parameters
     * Will throw an exception rather than return a success state.
     * The connector should not select the database once connected until
     * explicitly called by selectDatabase()
     *
     * @param array $parameters List of parameters such as
     * <ul>
     *   <li>type</li>
     *   <li>server</li>
     *   <li>username</li>
     *   <li>password</li>
     *   <li>database</li>
     *   <li>path</li>
     * </ul>
     * @param boolean $selectDB By default database selection should be
     * handled by the database controller (to enable database creation on the
     * fly if necessary), but some interfaces require that the database is
     * specified during connection (SQLite, Azure, etc).
     */
    abstract public function connect($parameters, $selectDB = false);

    /**
     * Query for the version of the currently connected database
     *
     * @return string Version of this database
     */
    abstract public function getVersion();

    /**
     * Given a value escape this for use in a query for the current database
     * connector. Note that this does not quote the value.
     *
     * @param string $value The value to be escaped
     * @return string The appropritaely escaped string for value
     */
    abstract public function escapeString($value);

    /**
     * Given a value escape and quote this appropriately for the current
     * database connector.
     *
     * @param string $value The value to be injected into a query
     * @return string The appropriately escaped and quoted string for $value
     */
    abstract public function quoteString($value);

    /**
     * Executes the following query with the specified error level.
     * Implementations of this function should respect previewWrite and benchmarkQuery
     *
     * @see http://php.net/manual/en/errorfunc.constants.php
     * @param string $sql The SQL query to execute
     * @param integer $errorLevel For errors to this query, raise PHP errors
     * using this error level.
     */
    abstract public function query($sql, $errorLevel = E_USER_ERROR);

    /**
     * Execute the given SQL parameterised query with the specified arguments
     *
     * @param string $sql The SQL query to execute. The ? character will denote parameters.
     * @param array $parameters An ordered list of arguments.
     * @param int $errorLevel The level of error reporting to enable for the query
     * @return Query
     */
    abstract public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR);

    /**
     * Select a database by name
     *
     * @param string $name Name of database
     * @return boolean Flag indicating success
     */
    abstract public function selectDatabase($name);

    /**
     * Retrieves the name of the currently selected database
     *
     * @return string Name of the database, or null if none selected
     */
    abstract public function getSelectedDatabase();

    /**
     * De-selects the currently selected database
     */
    abstract public function unloadDatabase();

    /**
     * Retrieves the last error generated from the database connection
     *
     * @return string The error message
     */
    abstract public function getLastError();

    /**
     * Determines the last ID generated from the specified table.
     * Note that some connectors may not be able to return $table specific responses,
     * and this parameter may be ignored.
     *
     * @param string $table The target table to return the last generated ID for
     * @return integer ID value
     */
    abstract public function getGeneratedID($table);

    /**
     * Determines the number of affected rows from the last SQL query
     *
     * @return integer Number of affected rows
     */
    abstract public function affectedRows();

    /**
     * Determines if we are connected to a server AND have a valid database
     * selected.
     *
     * @return boolean Flag indicating that a valid database is connected
     */
    abstract public function isActive();
}
