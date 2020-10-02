<?php

namespace SilverStripe\ORM\Connect;

use Exception;

/**
 * Error class for database exceptions
 */
class DatabaseException extends Exception
{

    /**
     * The SQL that generated this error
     *
     * @var string
     */
    protected $sql = null;

    /**
     * The parameters given for this query, if any
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Returns the SQL that generated this error
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * The parameters given for this query, if any
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Constructs the database exception
     *
     * @param string $message The Exception message to throw.
     * @param integer $code The Exception code.
     * @param Exception $previous The previous exception used for the exception chaining.
     * @param string $sql The SQL executed for this query
     * @param array $parameters The parameters given for this query, if any
     */
    public function __construct($message = '', $code = 0, $previous = null, $sql = null, $parameters = [])
    {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->parameters = $parameters;
    }
}
