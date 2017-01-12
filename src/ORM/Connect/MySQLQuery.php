<?php

namespace SilverStripe\ORM\Connect;

/**
 * A result-set from a MySQL database (using MySQLiConnector)
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
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     *
     * @param MySQLiConnector $database The database object that created this query.
     * @param mixed $handle the internal mysql handle that is points to the resultset.
     * Non-mysqli_result values could be given for non-select queries (e.g. true)
     */
    public function __construct($database, $handle)
    {
        $this->handle = $handle;
    }

    public function __destruct()
    {
        if (is_object($this->handle)) {
            $this->handle->free();
        }
    }

    public function seek($row)
    {
        if (is_object($this->handle)) {
            $this->handle->data_seek($row);
            return $this->handle->fetch_assoc();
        }
        return null;
    }

    public function numRecords()
    {
        if (is_object($this->handle)) {
            return $this->handle->num_rows;
        }
        return null;
    }

    public function nextRecord()
    {
        if (is_object($this->handle) && ($data = $this->handle->fetch_assoc())) {
            return $data;
        } else {
            return false;
        }
    }
}
