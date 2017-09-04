<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Core\Config\Config;
use PDO;
use PDOStatement;
use InvalidArgumentException;

/**
 * PDO driver database connector
 */
class PDOConnector extends DBConnector
{

    /**
     * Should ATTR_EMULATE_PREPARES flag be used to emulate prepared statements?
     * Note: Set this to `null` via config to prevent this value being assigned
     * (will be left as DB default).
     *
     * @config
     * @var boolean
     */
    private static $emulate_prepare = false;

    /**
     * Default strong SSL cipher to be used
     *
     * @config
     * @var string
     */
    private static $ssl_cipher_default = 'DHE-RSA-AES256-SHA';

    /**
     * The PDO connection instance
     *
     * @var PDO
     */
    protected $pdoConnection = null;

    /**
     * Name of the currently selected database
     *
     * @var string
     */
    protected $databaseName = null;

    /**
     * If available, the row count of the last executed statement
     *
     * @var int|null
     */
    protected $rowCount = null;

    /**
     * Error generated by the errorInfo() method of the last PDOStatement
     *
     * @var array|null
     */
    protected $lastStatementError = null;

    /**
     * List of prepared statements, cached by SQL string
     *
     * @var array
     */
    protected $cachedStatements = array();

    /**
     * Flush all prepared statements
     */
    public function flushStatements()
    {
        $this->cachedStatements = array();
    }

    /**
     * Retrieve a prepared statement for a given SQL string, or return an already prepared version if
     * one exists for the given query
     *
     * @param string $sql
     * @return PDOStatement
     */
    public function getOrPrepareStatement($sql)
    {
        // Return cached statements
        if (isset($this->cachedStatements[$sql])) {
            return $this->cachedStatements[$sql];
        }

        // Generate new statement
        $statement = $this->pdoConnection->prepare(
            $sql,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );

        // Only cache select statements
        if (preg_match('/^(\s*)select\b/i', $sql)) {
            $this->cachedStatements[$sql] = $statement;
        }
        return $statement;
    }

    /**
     * Is PDO running in emulated mode
     *
     * @return bool|null Boolean flag if assigned a value, or null if left unassigned
     */
    public static function is_emulate_prepare()
    {
        return static::config()->get('emulate_prepare');
    }

    public function connect($parameters, $selectDB = false)
    {
        $this->flushStatements();

        // Build DSN string
        // Note that we don't select the database here until explicitly
        // requested via selectDatabase
        $driver = $parameters['driver'] . ":";
        $dsn = array();

        // Typically this is false, but some drivers will request this
        if ($selectDB) {
            // Specify complete file path immediately following driver (SQLLite3)
            if (!empty($parameters['filepath'])) {
                $dsn[] = $parameters['filepath'];
            } elseif (!empty($parameters['database'])) {
                // Some databases require a selected database at connection (SQLite3, Azure)
                if ($parameters['driver'] === 'sqlsrv') {
                    $dsn[] = "Database={$parameters['database']}";
                } else {
                    $dsn[] = "dbname={$parameters['database']}";
                }
            }
        }

        // Syntax for sql server is slightly different
        if ($parameters['driver'] === 'sqlsrv') {
            $server = $parameters['server'];
            if (!empty($parameters['port'])) {
                $server .= ",{$parameters['port']}";
            }
            $dsn[] = "Server=$server";
        } elseif ($parameters['driver'] === 'dblib') {
            $server = $parameters['server'];
            if (!empty($parameters['port'])) {
                $server .= ":{$parameters['port']}";
            }
            $dsn[] = "host={$server}";
        } else {
            if (!empty($parameters['server'])) {
                // Use Server instead of host for sqlsrv
                $dsn[] = "host={$parameters['server']}";
            }

            if (!empty($parameters['port'])) {
                $dsn[] = "port={$parameters['port']}";
            }
        }

        // Connection charset and collation
        $connCharset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'connection_charset');
        $connCollation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'connection_collation');

        // Set charset if given and not null. Can explicitly set to empty string to omit
        if ($parameters['driver'] !== 'sqlsrv') {
            $charset = isset($parameters['charset'])
                    ? $parameters['charset']
                    : $connCharset;
            if (!empty($charset)) {
                $dsn[] = "charset=$charset";
            }
        }

        // Connection commands to be run on every re-connection
        if (!isset($charset)) {
            $charset = $connCharset;
        }
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset . ' COLLATE ' . $connCollation
        );

        // Set SSL options if they are defined
        if (array_key_exists('ssl_key', $parameters) &&
            array_key_exists('ssl_cert', $parameters)
        ) {
            $options[PDO::MYSQL_ATTR_SSL_KEY] = $parameters['ssl_key'];
            $options[PDO::MYSQL_ATTR_SSL_CERT] = $parameters['ssl_cert'];
            if (array_key_exists('ssl_ca', $parameters)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $parameters['ssl_ca'];
            }
            // use default cipher if not provided
            $options[PDO::MYSQL_ATTR_SSL_CIPHER] = array_key_exists('ssl_cipher', $parameters) ? $parameters['ssl_cipher'] : self::config()->get('ssl_cipher_default');
        }

        // Set emulate prepares (unless null / default)
        $isEmulatePrepares = self::is_emulate_prepare();
        if (isset($isEmulatePrepares)) {
            $options[PDO::ATTR_EMULATE_PREPARES] = (bool)$isEmulatePrepares;
        }

        // Disable stringified fetches
        $options[PDO::ATTR_STRINGIFY_FETCHES] = false;

        // May throw a PDOException if fails
        $this->pdoConnection = new PDO(
            $driver.implode(';', $dsn),
            empty($parameters['username']) ? '' : $parameters['username'],
            empty($parameters['password']) ? '' : $parameters['password'],
            $options
        );

        // Show selected DB if requested
        if ($this->pdoConnection && $selectDB && !empty($parameters['database'])) {
            $this->databaseName = $parameters['database'];
        }
    }

    public function getVersion()
    {
        return $this->pdoConnection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function escapeString($value)
    {
        $value = $this->quoteString($value);

        // Since the PDO library quotes the value, we should remove this to maintain
        // consistency with MySQLDatabase::escapeString
        if (preg_match('/^\'(?<value>.*)\'$/', $value, $matches)) {
            $value = $matches['value'];
        }
        return $value;
    }

    public function quoteString($value)
    {
        return $this->pdoConnection->quote($value);
    }

    /**
     * Invoked before any query is executed
     *
     * @param string $sql
     */
    protected function beforeQuery($sql)
    {
        // Reset state
        $this->rowCount = 0;
        $this->lastStatementError = null;

        // Flush if necessary
        if ($this->isQueryDDL($sql)) {
            $this->flushStatements();
        }
    }

    /**
     * Executes a query that doesn't return a resultset
     *
     * @param string $sql The SQL query to execute
     * @param integer $errorLevel For errors to this query, raise PHP errors
     * using this error level.
     * @return int
     */
    public function exec($sql, $errorLevel = E_USER_ERROR)
    {
        $this->beforeQuery($sql);

        // Directly exec this query
        $result = $this->pdoConnection->exec($sql);

        // Check for errors
        if ($result !== false) {
            return $this->rowCount = $result;
        }

        $this->databaseError($this->getLastError(), $errorLevel, $sql);
        return null;
    }

    public function query($sql, $errorLevel = E_USER_ERROR)
    {
        $this->beforeQuery($sql);

        // Directly query against connection
        $statement = $this->pdoConnection->query($sql);

        // Generate results
        return $this->prepareResults($statement, $errorLevel, $sql);
    }

    /**
     * Determines the PDO::PARAM_* type for a given PHP type string
     * @param string $phpType Type of object in PHP
     * @return integer PDO Parameter constant value
     */
    public function getPDOParamType($phpType)
    {
        switch ($phpType) {
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'NULL':
                return PDO::PARAM_NULL;
            case 'integer':
                return PDO::PARAM_INT;
            case 'object': // Allowed if the object or resource has a __toString method
            case 'resource':
            case 'float': // Not actually returnable from get_type
            case 'double':
            case 'string':
                return PDO::PARAM_STR;
            case 'blob':
                return PDO::PARAM_LOB;
            case 'array':
            case 'unknown type':
            default:
                throw new InvalidArgumentException("Cannot bind parameter as it is an unsupported type ($phpType)");
        }
    }

    /**
     * Bind all parameters to a PDOStatement
     *
     * @param PDOStatement $statement
     * @param array $parameters
     */
    public function bindParameters(PDOStatement $statement, $parameters)
    {
        // Bind all parameters
        for ($index = 0; $index < count($parameters); $index++) {
            $value = $parameters[$index];
            $phpType = gettype($value);

            // Allow overriding of parameter type using an associative array
            if ($phpType === 'array') {
                $phpType = $value['type'];
                $value = $value['value'];
            }

            // Check type of parameter
            $type = $this->getPDOParamType($phpType);
            if ($type === PDO::PARAM_STR) {
                $value = strval($value);
            }

            // Bind this value
            $statement->bindValue($index+1, $value, $type);
        }
    }

    public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR)
    {
        $this->beforeQuery($sql);

        // Prepare statement
        $statement = $this->getOrPrepareStatement($sql);

        // Bind and invoke statement safely
        if ($statement) {
            $this->bindParameters($statement, $parameters);
            $statement->execute($parameters);
        }

        // Generate results
        return $this->prepareResults($statement, $errorLevel, $sql);
    }

    /**
     * Given a PDOStatement that has just been executed, generate results
     * and report any errors
     *
     * @param PDOStatement $statement
     * @param int $errorLevel
     * @param string $sql
     * @param array $parameters
     * @return PDOQuery
     */
    protected function prepareResults($statement, $errorLevel, $sql, $parameters = array())
    {

        // Record row-count and errors of last statement
        if ($this->hasError($statement)) {
            $this->lastStatementError = $statement->errorInfo();
        } elseif ($statement) {
            // Count and return results
            $this->rowCount = $statement->rowCount();
            return new PDOQuery($statement);
        }

        // Ensure statement is closed
        if ($statement) {
            $statement->closeCursor();
            unset($statement);
        }

        // Report any errors
        if ($parameters) {
            $parameters = $this->parameterValues($parameters);
        }
        $this->databaseError($this->getLastError(), $errorLevel, $sql, $parameters);
        return null;
    }

    /**
     * Determine if a resource has an attached error
     *
     * @param PDOStatement|PDO $resource the resource to check
     * @return boolean Flag indicating true if the resource has an error
     */
    protected function hasError($resource)
    {
        // No error if no resource
        if (empty($resource)) {
            return false;
        }

        // If the error code is empty the statement / connection has not been run yet
        $code = $resource->errorCode();
        if (empty($code)) {
            return false;
        }

        // Skip 'ok' and undefined 'warning' types.
        // @see http://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm
        return $code !== '00000' && $code !== '01000';
    }

    public function getLastError()
    {
        $error = null;
        if ($this->lastStatementError) {
            $error = $this->lastStatementError;
        } elseif ($this->hasError($this->pdoConnection)) {
            $error = $this->pdoConnection->errorInfo();
        }
        if ($error) {
            return sprintf("%s-%s: %s", $error[0], $error[1], $error[2]);
        }
        return null;
    }

    public function getGeneratedID($table)
    {
        return $this->pdoConnection->lastInsertId();
    }

    public function affectedRows()
    {
        return $this->rowCount;
    }

    public function selectDatabase($name)
    {
        $this->exec("USE \"{$name}\"");
        $this->databaseName = $name;
        return true;
    }

    public function getSelectedDatabase()
    {
        return $this->databaseName;
    }

    public function unloadDatabase()
    {
        $this->databaseName = null;
    }

    public function isActive()
    {
        return $this->databaseName && $this->pdoConnection;
    }
}
