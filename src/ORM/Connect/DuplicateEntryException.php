<?php

namespace SilverStripe\ORM\Connect;

/**
 * Exception for errors related to duplicate entries (e.g. violating a unique index)
 */
class DuplicateEntryException extends DatabaseException
{
    private ?string $keyName = null;

    private ?string $duplicatedValue = null;

    /**
     * Constructs the database exception
     *
     * @param string $message The Exception message to throw.
     * @param string|null $keyName The name of the key which the error is for (e.g. index name)
     * @param string|null $duplicatedValue The value which was duplicated (e.g. combined value of multiple columns in index)
     * @param string|null $sql The SQL executed for this query
     * @param array $parameters The parameters given for this query, if any
     */
    public function __construct(
        string $message = '',
        ?string $keyName = null,
        ?string $duplicatedValue = null,
        ?string $sql = null,
        array $parameters = []
    ) {
        parent::__construct($message, sql: $sql, parameters: $parameters);
        $this->keyName = $keyName;
        $this->duplicatedValue = $duplicatedValue;
    }

    /**
     * Get the name of the key which the error is for (e.g. index name)
     */
    public function getKeyName(): ?string
    {
        return $this->keyName;
    }

    /**
     * Get the value which was duplicated (e.g. combined value of multiple columns in index)
     */
    public function getDuplicatedValue(): ?string
    {
        return $this->duplicatedValue;
    }
}
