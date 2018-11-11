<?php

namespace SilverStripe\ORM\Connect;

use PDOStatement;
use PDO;

/**
 * A result-set from a PDO database.
 */
class PDOQuery extends Query
{
    /**
     * The internal MySQL handle that points to the result set.
     * @var PDOStatement
     */
    protected $statement = null;

    protected $results = null;

    protected static $type_mapping = [
        // PGSQL
        'float8' => 'float',
        'float16' => 'float',
        'numeric' => 'float',

        // MySQL
        'NEWDECIMAL' => 'float',

        // SQlite
        'integer' => 'int',
        'double' => 'float',
    ];

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     * @param PDOStatement $statement The internal PDOStatement containing the results
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
        // Since no more than one PDOStatement for any one connection can be safely
        // traversed, each statement simply requests all rows at once for safety.
        // This could be re-engineered to call fetchAll on an as-needed basis

        $this->results = $this->typeCorrectedFetchAll($statement);

        $statement->closeCursor();
    }

    /**
     * Fetch a record form the statement with its type data corrected
     * Returns data as an array of maps
     * @return array
     */
    protected function typeCorrectedFetchAll($statement)
    {
        $columnCount = $statement->columnCount();
        $columnMeta = [];
        for ($i = 0; $i<$columnCount; $i++) {
            $columnMeta[$i] = $statement->getColumnMeta($i);
        }

        // Re-map fetched data using columnMeta
        return array_map(
            function ($rowArray) use ($columnMeta) {
                $row = [];
                foreach ($columnMeta as $i => $meta) {
                    // Coerce any column types that aren't correctly retrieved from the database
                    if (isset($meta['native_type']) && isset(self::$type_mapping[$meta['native_type']])) {
                        settype($rowArray[$i], self::$type_mapping[$meta['native_type']]);
                    }
                    $row[$meta['name']] = $rowArray[$i];
                }
                return $row;
            },
            $statement->fetchAll(PDO::FETCH_NUM)
        );
    }

    public function seek($row)
    {
        $this->rowNum = $row - 1;
        return $this->nextRecord();
    }

    public function numRecords()
    {
        return count($this->results);
    }

    public function nextRecord()
    {
        $index = $this->rowNum + 1;

        if (isset($this->results[$index])) {
            return $this->results[$index];
        } else {
            return false;
        }
    }
}
