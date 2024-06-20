<?php

namespace SilverStripe\ORM\Connect;

use mysqli;
use mysqli_sql_exception;
use mysqli_stmt;
use SilverStripe\Core\Config\Config;

/**
 * Connector for MySQL using the MySQLi method
 */
class MySQLiConnector extends DBConnector
{

    /**
     * Default strong SSL cipher to be used
     *
     * @config
     * @var string
     */
    private static $ssl_cipher_default = 'DHE-RSA-AES256-SHA';

    /**
     * Connection to the MySQL database
     *
     * @var mysqli
     */
    protected $dbConn = null;

    /**
     * Name of the currently selected database
     *
     * @var string
     */
    protected $databaseName = null;

    /**
     * The most recent statement returned from MySQLiConnector->preparedQuery
     *
     * @var mysqli_stmt
     */
    protected $lastStatement = null;

    /**
     * Store the most recent statement for later use
     *
     * @param mysqli_stmt $statement
     */
    protected function setLastStatement($statement)
    {
        $this->lastStatement = $statement;
    }

    /**
     * Retrieve a prepared statement for a given SQL string
     *
     * @param string $sql
     * @param boolean $success (by reference)
     * @return mysqli_stmt
     */
    public function prepareStatement($sql, &$success)
    {
        // Record last statement for error reporting
        $statement = $this->dbConn->stmt_init();
        $this->setLastStatement($statement);
        try {
            $success = $statement->prepare($sql);
        } catch (mysqli_sql_exception $e) {
            $success = false;
            $this->databaseError($e->getMessage(), E_USER_ERROR, $sql);
        }
        return $statement;
    }

    public function connect($parameters, $selectDB = false)
    {
        // Normally $selectDB is set to false by the MySQLDatabase controller, as per convention
        $selectedDB = ($selectDB && !empty($parameters['database'])) ? $parameters['database'] : null;

        // Connection charset and collation
        $connCharset = Config::inst()->get(MySQLDatabase::class, 'connection_charset');
        $connCollation = Config::inst()->get(MySQLDatabase::class, 'connection_collation');

        $this->dbConn = mysqli_init();

        // Use native types (MysqlND only)
        if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
            $this->dbConn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

        // The alternative is not ideal, throw a notice-level error
        } else {
            user_error(
                'mysqlnd PHP library is not available, numeric values will be fetched from the DB as strings',
                E_USER_NOTICE
            );
        }

        // Set SSL parameters if they exist.
        // Must have both the SSL cert and key, or the common authority, or preferably all three.
        if ((array_key_exists('ssl_key', $parameters ?? []) && array_key_exists('ssl_cert', $parameters ?? []))
            || array_key_exists('ssl_ca', $parameters ?? [])
        ) {
            $this->dbConn->ssl_set(
                $parameters['ssl_key'] ?? null,
                $parameters['ssl_cert'] ?? null,
                $parameters['ssl_ca'] ?? null,
                dirname($parameters['ssl_ca'] ?? ''),
                array_key_exists('ssl_cipher', $parameters ?? [])
                    ? $parameters['ssl_cipher']
                    : static::config()->get('ssl_cipher_default')
            );
        }

        $this->dbConn->real_connect(
            $parameters['server'],
            $parameters['username'],
            $parameters['password'],
            $selectedDB,
            !empty($parameters['port']) ? $parameters['port'] : ini_get("mysqli.default_port")
        );

        if ($this->dbConn->connect_error) {
            $this->databaseError("Couldn't connect to MySQL database | " . $this->dbConn->connect_error);
        }

        // Set charset and collation if given and not null. Can explicitly set to empty string to omit
        $charset = isset($parameters['charset'])
                ? $parameters['charset']
                : $connCharset;

        if (!empty($charset)) {
            $this->dbConn->set_charset($charset);
        }

        $collation = isset($parameters['collation'])
            ? $parameters['collation']
            : $connCollation;

        if (!empty($collation)) {
            $this->dbConn->query("SET collation_connection = {$collation}");
        }
    }

    public function __destruct()
    {
        if (is_resource($this->dbConn)) {
            mysqli_close($this->dbConn);
            $this->dbConn = null;
        }
    }

    public function escapeString($value)
    {
        return $this->dbConn->real_escape_string($value ?? '');
    }

    public function quoteString($value)
    {
        $value = $this->escapeString($value);
        return "'$value'";
    }

    public function getVersion()
    {
        return $this->dbConn->server_info;
    }

    /**
     * Invoked before any query is executed
     *
     * @param string $sql
     */
    protected function beforeQuery($sql)
    {
        // Clear the last statement
        $this->setLastStatement(null);
    }

    public function query($sql, $errorLevel = E_USER_ERROR)
    {
        $this->beforeQuery($sql);

        $error = null;
        $handle = null;

        try {
            // Benchmark query
            $handle = $this->dbConn->query($sql ?? '', MYSQLI_STORE_RESULT);
        } catch (mysqli_sql_exception $e) {
            $error = $e->getMessage();
        } finally {
            if (!$handle || $this->dbConn->error) {
                $this->databaseError($error ?? $this->getLastError(), $errorLevel, $sql);
                return null;
            }
        }

        // Some non-select queries return true on success
        return new MySQLQuery($this, $handle);
    }

    /**
     * Prepares the list of parameters in preparation for passing to mysqli_stmt_bind_param
     *
     * @param array $parameters List of parameters
     * @param array $blobs Out parameter for list of blobs to bind separately (by reference)
     * @return array List of parameters appropriate for mysqli_stmt_bind_param function
     */
    public function parsePreparedParameters($parameters, &$blobs)
    {
        $types = '';
        $values = [];
        $blobs = [];
        $parametersCount = count($parameters ?? []);
        for ($index = 0; $index < $parametersCount; $index++) {
            $value = $parameters[$index];
            $phpType = gettype($value);

            // Allow overriding of parameter type using an associative array
            if ($phpType === 'array') {
                $phpType = $value['type'];
                $value = $value['value'];
            }

            // Convert php variable type to one that makes mysqli_stmt_bind_param happy
            // @see http://www.php.net/manual/en/mysqli-stmt.bind-param.php
            switch ($phpType) {
                case 'boolean':
                case 'integer':
                    $types .= 'i';
                    break;
                case 'float': // Not actually returnable from gettype
                case 'double':
                    $types .= 'd';
                    break;
                case 'object': // Allowed if the object or resource has a __toString method
                case 'resource':
                case 'string':
                case 'NULL': // Take care that a where clause should use "where XX is null" not "where XX = null"
                    $types .= 's';
                    break;
                case 'blob':
                    $types .= 'b';
                    // Blobs must be sent via send_long_data and set to null here
                    $blobs[] = [
                        'index' => $index,
                        'value' => $value
                    ];
                    $value = null;
                    break;
                case 'array':
                case 'unknown type':
                default:
                    throw new \InvalidArgumentException(
                        "Cannot bind parameter \"$value\" as it is an unsupported type ($phpType)"
                    );
            }
            $values[] = $value;
        }
        return array_merge([$types], $values);
    }

    /**
     * Binds a list of parameters to a statement
     *
     * @param mysqli_stmt $statement MySQLi statement
     * @param array $parameters List of parameters to pass to bind_param
     */
    public function bindParameters(mysqli_stmt $statement, array $parameters)
    {
        // Because mysqli_stmt::bind_param arguments must be passed by reference
        // we need to do a bit of hackery
        $boundNames = [];
        $parametersCount = count($parameters ?? []);
        for ($i = 0; $i < $parametersCount; $i++) {
            $boundName = "param$i";
            $$boundName = $parameters[$i];
            $boundNames[] = &$$boundName;
        }
        $statement->bind_param(...$boundNames);
    }

    public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR)
    {
        // Shortcut to basic query when not given parameters
        if (empty($parameters)) {
            return $this->query($sql, $errorLevel);
        }

        $this->beforeQuery($sql);

        // Type check, identify, and prepare parameters for passing to the statement bind function
        $parsedParameters = $this->parsePreparedParameters($parameters, $blobs);

        // Benchmark query
        $statement = $this->prepareStatement($sql, $success);
        if ($success) {
            if ($parsedParameters) {
                $this->bindParameters($statement, $parsedParameters);
            }

            // Bind any blobs given
            foreach ($blobs as $blob) {
                $statement->send_long_data($blob['index'], $blob['value']);
            }

            // Safely execute the statement
            $statement->execute();
        }

        if (!$success || $statement->error) {
            $values = $this->parameterValues($parameters);
            $this->databaseError($this->getLastError(), $errorLevel, $sql, $values);
            return null;
        }

        // Non-select queries will have no result data
        $metaData = $statement->result_metadata();
        if ($metaData) {
            return new MySQLStatement($statement, $metaData);
        }

        // Replicate normal behaviour of ->query() on non-select calls
        return new MySQLQuery($this, true);
    }

    public function selectDatabase($name)
    {
        if ($this->dbConn->select_db($name ?? '')) {
            $this->databaseName = $name;
            return true;
        }

        return false;
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
        return $this->databaseName && $this->dbConn && empty($this->dbConn->connect_error);
    }

    public function affectedRows()
    {
        return $this->dbConn->affected_rows;
    }

    public function getGeneratedID($table)
    {
        return $this->dbConn->insert_id;
    }

    public function getLastError()
    {
        // Check if a statement was used for the most recent query
        if ($this->lastStatement && $this->lastStatement->error) {
            return $this->lastStatement->error;
        }
        return $this->dbConn->error;
    }
}
