<?php

namespace SilverStripe\ORM\Connect;

use mysqli_result;
use mysqli_stmt;
use Traversable;

/**
 * Provides a record-view for mysqli prepared statements
 *
 * By default streams unbuffered data, but seek(), rewind(), or numRecords() will force the statement to
 * buffer itself and sacrifice any potential performance benefit.
 */
class MySQLStatement extends Query
{

    /**
     * The related mysqli statement object if generated using a prepared query
     *
     * @var mysqli_stmt
     */
    protected $statement;

    /**
     * Metadata result for this statement
     *
     * @var mysqli_result
     */
    protected $metadata;

    /**
     * Is the statement bound to the current resultset?
     *
     * @var bool
     */
    protected $bound = false;

    /**
     * List of column names
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Map of column types, keyed by column name
     *
     * @var array
     */
    protected $types = [];

    /**
     * List of bound variables in the current row
     *
     * @var array
     */
    protected $boundValues = [];

    /**
     * Hook the result-set given into a Query class, suitable for use by SilverStripe.
     * @param mysqli_stmt $statement The related statement, if present
     * @param mysqli_result $metadata The metadata for this statement
     */
    public function __construct($statement, $metadata)
    {
        $this->statement = $statement;
        $this->metadata = $metadata;

        // Immediately bind and buffer
        $this->bind();
    }

    public function __destruct()
    {
        $this->statement->close();
    }

    /**
     * Binds this statement to the variables
     */
    protected function bind()
    {
        $variables = [];

        // Bind each field
        while ($field = $this->metadata->fetch_field()) {
            $this->columns[] = $field->name;
            $this->types[$field->name] = $field->type;
            // Note that while boundValues isn't initialised at this point,
            // later calls to $this->statement->fetch() Will populate
            // $this->boundValues later with the next result.
            $variables[] = &$this->boundValues[$field->name];
        }

        $this->bound = true;
        $this->metadata->free();

        // Buffer all results
        $this->statement->store_result();

        call_user_func_array([$this->statement, 'bind_result'], $variables ?? []);
    }

    public function getIterator(): Traversable
    {
        while ($this->statement->fetch()) {
            // Dereferenced row
            $row = [];
            foreach ($this->boundValues as $key => $value) {
                $floatTypes = [MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_NEWDECIMAL];
                if (in_array($this->types[$key], $floatTypes ?? [])) {
                    $value = (float)$value;
                }
                $row[$key] = $value;
            }
            yield $row;
        }

        // Reset so the query can be iterated over again
        $this->rewind();
    }

    /**
     * Rewind to the first row
     */
    public function rewind(): void
    {
        // Check for the method first since $this->statement isn't strongly typed
        if (method_exists($this->statement, 'data_seek')) {
            $this->statement->data_seek(0);
        }
    }

    public function numRecords()
    {
        return $this->statement->num_rows();
    }
}
