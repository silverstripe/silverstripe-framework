<?php

namespace SilverStripe\ORM\Connect;

use ArrayIterator;
use PDO;
use PDOStatement;

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

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     * @param PDOStatement $statement The internal PDOStatement containing the results
     */
    public function __construct(PDOStatement $statement, PDOConnector $conn)
    {
        $this->statement = $statement;
        // Since no more than one PDOStatement for any one connection can be safely
        // traversed, each statement simply requests all rows at once for safety.
        // This could be re-engineered to call fetchAll on an as-needed basis

        // Special case for Postgres
        if ($conn->getDriver() == 'pgsql') {
            $this->results = $this->fetchAllPgsql($statement);
        } else {
            $this->results = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        $statement->closeCursor();
    }

    /**
     * Fetch a record form the statement with its type data corrected
     * Necessary to fix float data retrieved from PGSQL
     * Returns data as an array of maps
     * @return array
     */
    protected function fetchAllPgsql($statement)
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
                    // Coerce floats from string to float
                    // PDO PostgreSQL fails to do this
                    if (isset($meta['native_type']) && strpos($meta['native_type'], 'float') === 0) {
                        $rowArray[$i] = (float)$rowArray[$i];
                    }
                    $row[$meta['name']] = $rowArray[$i];
                }
                return $row;
            },
            $statement->fetchAll(PDO::FETCH_NUM)
        );
    }

    public function getIterator()
    {
        return new ArrayIterator($this->results);
    }

    public function numRecords()
    {
        return count($this->results);
    }
}
