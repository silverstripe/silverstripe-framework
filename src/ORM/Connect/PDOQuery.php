<?php

namespace SilverStripe\ORM\Connect;

use PDOStatement;
use PDO;
use ArrayIterator;

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

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     * @param PDOStatement $statement The internal PDOStatement containing the results
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function getIterator()
    {
        if (is_object($this->statement)) {
            while ($data = $this->statement->fetch(PDO::FETCH_ASSOC)) {
                yield $data;
            }
        }
    }

    public function numRecords()
    {
        return $this->statement->rowCount();
    }
}
