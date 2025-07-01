<?php

namespace SilverStripe\ORM\Connect;

/**
 * Exception for errors related to setting values on a generated column
 */
class GeneratedColumnValueException extends DatabaseException
{
    private ?string $column = null;

    private ?string $table = null;

    /**
     * Constructs the database exception
     *
     * @param string $message The Exception message to throw.
     * @param string|null $column The name of the column which the error is for
     * @param string|null $table The name of the table which the error is for
     * @param string|null $sql The SQL executed for this query
     * @param array $parameters The parameters given for this query, if any
     */
    public function __construct(
        string $message = '',
        ?string $column = null,
        ?string $table = null,
        ?string $sql = null,
        array $parameters = []
    ) {
        parent::__construct($message, sql: $sql, parameters: $parameters);
        $this->column = $column;
        $this->table = $table;
    }

    /**
     * Get the name of the column which the error is for
     */
    public function getColumn(): ?string
    {
        return $this->column;
    }

    /**
     * Get the name of the table which the error is for
     */
    public function getTable(): ?string
    {
        return $this->table;
    }
}
