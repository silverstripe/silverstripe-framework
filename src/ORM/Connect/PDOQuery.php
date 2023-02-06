<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Dev\Deprecation;

/**
 * A result-set from a PDO database.
 *
 * @deprecated 4.13.0 Will be removed without equivalent functionality to replace it
 */
class PDOQuery extends Query
{
    /**
     * @var array
     */
    protected $results = null;

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     * @param PDOStatement $statement The internal PDOStatement containing the results
     */
    public function __construct(PDOStatementHandle $statement)
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice(
                '4.13.0',
                'Will be removed without equivalent functionality to replace it',
                Deprecation::SCOPE_CLASS
            );
        });
        // Since no more than one PDOStatement for any one connection can be safely
        // traversed, each statement simply requests all rows at once for safety.
        // This could be re-engineered to call fetchAll on an as-needed basis
        $this->results = $statement->typeCorrectedFetchAll();
        $statement->closeCursor();
    }

    public function seek($row)
    {
        $this->rowNum = $row - 1;
        return $this->nextRecord();
    }

    public function numRecords()
    {
        return count($this->results ?? []);
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
