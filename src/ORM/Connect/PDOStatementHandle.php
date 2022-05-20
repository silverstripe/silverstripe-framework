<?php

namespace SilverStripe\ORM\Connect;

use PDO;
use PDOStatement;

/**
 * A handle to a PDOStatement, with cached column metadata, and type conversion
 *
 * Column metadata can't be fetched from a native PDOStatement after multiple calls in some DB backends,
 * so we wrap in this handle object, which also takes care of tidying up content types to keep in line
 * with the SilverStripe 4.4+ type expectations.
 */
class PDOStatementHandle
{

    /**
     * The statement to provide a handle to
     *
     * @var PDOStatement
     */
    private $statement;

    /**
     * Cached column metadata
     *
     * @var array
     */
    private $columnMeta = null;

    /**
     * Create a new handle.
     *
     * @param $statement The statement to provide a handle to
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Mapping of PDO-reported "native types" to PHP types
     */
    protected static $type_mapping = [
        // PGSQL
        'float8' => 'float',
        'float16' => 'float',
        'numeric' => 'float',
        'bool' => 'int', // Bools should be ints

        // MySQL
        'NEWDECIMAL' => 'float',

        // SQlite
        'integer' => 'int',
        'double' => 'float',
    ];

    /**
     * Fetch a record form the statement with its type data corrected
     * Returns data as an array of maps
     * @return array
     */
    public function typeCorrectedFetchAll()
    {
        if ($this->columnMeta === null) {
            $columnCount = $this->statement->columnCount();
            $this->columnMeta = [];
            for ($i = 0; $i<$columnCount; $i++) {
                $this->columnMeta[$i] = $this->statement->getColumnMeta($i);
            }
        }

        // Re-map fetched data using columnMeta
        return array_map(
            function ($rowArray) {
                $row = [];
                foreach ($this->columnMeta as $i => $meta) {
                    // Coerce any column types that aren't correctly retrieved from the database
                    if (isset($meta['native_type']) && isset(self::$type_mapping[$meta['native_type']])) {
                        settype($rowArray[$i], self::$type_mapping[$meta['native_type']] ?? '');
                    }
                    $row[$meta['name']] = $rowArray[$i];
                }
                return $row;
            },
            $this->statement->fetchAll(PDO::FETCH_NUM) ?? []
        );
    }

    /**
     * Closes the cursor, enabling the statement to be executed again (PDOStatement::closeCursor)
     *
     * @return bool Returns true on success
     */
    public function closeCursor()
    {
        return $this->statement->closeCursor();
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle
     * (PDOStatement::errorCode)
     *
     * @return string
     */
    public function errorCode()
    {
        return $this->statement->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on the statement handle
     * (PDOStatement::errorInfo)
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->statement->errorInfo();
    }

    /**
     * Returns the number of rows affected by the last SQL statement (PDOStatement::rowCount)
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->statement->rowCount();
    }

    /**
     * Executes a prepared statement (PDOStatement::execute)
     *
     * @param $parameters An array of values with as many elements as there are bound parameters in the SQL statement
     *                    being executed
     * @return bool Returns true on success
     */
    public function execute(array $parameters)
    {
        return $this->statement->execute($parameters);
    }

    /**
     * Return the PDOStatement that this object provides a handle to
     *
     * @return PDOStatement
     */
    public function getPDOStatement()
    {
        return $this->statement;
    }
}
