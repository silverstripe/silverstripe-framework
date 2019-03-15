<?php

namespace SilverStripe\ORM\Connect;

use PDO;
use PDOStatement;

/**
 * A result-set from a PDO database.
 */

/**
 * Class PDOQuery
 * A result-set from a PDO database.
 *
 * Fetch method will be set to FETCH_ALL by default
 * this means ALL results are fetched immediately which makes the handling of data convenient
 * the problem of this approach is that all results are stored in memory which can be a problem with large data sets
 * FETCH_ROW method should be used for cases where large data sets are used and only one row needs to be in memory
 * at one time
 * note that this option disables some features like counting the rows and seeking specific rows
 *
 * <code>
 * $results = PDOQuery::withFetchMethodFlag(function (): Query {
 *     PDOQuery::setFetchMethodFlag(PDOQuery::FETCH_METHOD_ROW);
 *
 *     return DB::query('SELECT `ID`, `Title` FROM `SiteTree` ORDER BY `ID` ASC LIMIT 10');
 * });
 * while ($row = $results->next()) {
 *    // one row at a time
 *   print_r($row);
 * }
 * </code>
 *
 * @package SilverStripe\ORM\Connect
 */
class PDOQuery extends Query
{
    const FETCH_METHOD_ALL = 'FETCH_ALL';
    const FETCH_METHOD_ROW = 'FETCH_ROW';

    /**
     * The internal MySQL handle that points to the result set.
     * @var PDOStatement
     */
    protected $statement = null;

    protected $results = null;

    /**
     * @var string
     */
    protected $fetchMedthod = self::FETCH_METHOD_ALL;

    /**
     * Global fetch method setting
     * @var string
     */
    protected static $fetchMethodFlag = self::FETCH_METHOD_ALL;

    /**
     * @param string $method
     */
    public static function setFetchMethodFlag($method)
    {
        if (!in_array($method, [static::FETCH_METHOD_ALL, static::FETCH_METHOD_ROW])) {
            return;
        }

        static::$fetchMethodFlag = $method;
    }

    /**
     * @return string
     */
    public static function getFetchMethodFlag()
    {
        return static::$fetchMethodFlag;
    }

    /**
     * Invoke a callback which may modify fetch method flag, but ensures this flag is restored
     * after completion, without modifying global state.
     *
     * The desired fetch method flag should be set by the callback directly
     *
     * @param callable $callback
     * @return mixed Result of $callback
     */
    public static function withFetchMethodFlag($callback)
    {
        $originalFlag = static::getFetchMethodFlag();
        try {
            return $callback();
        } finally {
            static::setFetchMethodFlag($originalFlag);
        }
    }

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     * @param PDOStatement $statement The internal PDOStatement containing the results
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->fetchMedthod = static::getFetchMethodFlag();

        // skip the fetching of the results as we will fetch them later
        if ($this->fetchMedthod === static::FETCH_METHOD_ROW) {
            return;
        }

        // Since no more than one PDOStatement for any one connection can be safely
        // traversed, each statement simply requests all rows at once for safety.
        // This could be re-engineered to call fetchAll on an as-needed basis
        $this->results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
    }

    public function seek($row)
    {
        // seek is not available for fetch row
        if ($this->fetchMedthod === static::FETCH_METHOD_ROW) {
            return [];
        }

        $this->rowNum = $row - 1;
        return $this->nextRecord();
    }

    public function numRecords()
    {
        // number of records is not available for fetch row
        if ($this->fetchMedthod === static::FETCH_METHOD_ROW) {
            return 0;
        }

        return count($this->results);
    }

    public function nextRecord()
    {
        if ($this->fetchMedthod === static::FETCH_METHOD_ROW) {
            $result = $this->statement->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                // close cursor after no more rows can be fetched
                $this->statement->closeCursor();

                return false;
            }

            return $result;
        }

        $index = $this->rowNum + 1;

        if (isset($this->results[$index])) {
            return $this->results[$index];
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getFetchMethod()
    {
        return $this->fetchMedthod;
    }
}
