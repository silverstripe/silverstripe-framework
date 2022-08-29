<?php

namespace SilverStripe\ORM\Connect;

use Iterator;

/**
 * A result-set from a MySQL database (using MySQLiConnector)
 * Note that this class is only used for the results of non-prepared statements
 */
class MySQLQuery extends Query
{

    /**
     * The internal MySQL handle that points to the result set.
     * Select queries will have mysqli_result as a value.
     * Non-select queries will not
     *
     * @var mixed
     */
    protected $handle;

    /**
     * Metadata about the columns of this query
     */
    protected $columns;

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     *
     * @param MySQLiConnector $database The database object that created this query.
     * @param mixed $handle the internal mysql handle that is points to the resultset.
     * Non-mysqli_result values could be given for non-select queries (e.g. true)
     */
    public function __construct($database, $handle)
    {
        $this->handle = $handle;
        if (is_object($this->handle)) {
            $this->columns = $this->handle->fetch_fields();
        }
    }

    public function __destruct()
    {
        if (is_object($this->handle)) {
            $this->handle->free();
        }
    }

    public function getIterator(): Iterator
    {
        if (is_object($this->handle)) {
            while ($data = $this->handle->fetch_assoc()) {
                yield $data;
            }
        }
    }

    public function numRecords()
    {
        if (is_object($this->handle)) {
            return $this->handle->num_rows;
        }

        return null;
    }
}
