<?php

namespace SilverStripe\ORM\Connect;

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

    public function seek($row)
    {
        if (is_object($this->handle)) {
            // Fix for https://github.com/silverstripe/silverstripe-framework/issues/9097 without breaking the seek() API
            $this->handle->data_seek($row);
            $result = $this->nextRecord();
            $this->handle->data_seek($row);
            return $result;
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
        $floatTypes = [MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_NEWDECIMAL];

        if (is_object($this->handle) && ($row = $this->handle->fetch_array(MYSQLI_NUM))) {
            $data = [];
            foreach ($row as $i => $value) {
                if (!isset($this->columns[$i])) {
                    throw new DatabaseException("Can't get metadata for column $i");
                }
                if (in_array($this->columns[$i]->type, $floatTypes ?? [])) {
                    $value = (float)$value;
                }
                $data[$this->columns[$i]->name] = $value;
            }
            return $data;
        } else {
            return false;
        }
    }
}
