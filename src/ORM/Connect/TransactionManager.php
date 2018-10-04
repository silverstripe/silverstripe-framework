<?php

namespace SilverStripe\ORM\Connect;

/**
 * Represents an object that is capable of controlling transactions
 */
interface TransactionManager
{
    /**
     * Start a prepared transaction
     *
     * @param string|boolean $transactionMode Transaction mode, or false to ignore. Deprecated and will be removed in SS5.
     * @param string|boolean $sessionCharacteristics Session characteristics, or false to ignore. Deprecated and will be removed in SS5.
     * @throws DatabaseException on failure
     * @return bool True on success
     */
    public function transactionStart($transactionMode = false, $sessionCharacteristics = false);

    /**
     * Complete a transaction
     *
     * @throws DatabaseException on failure
     * @return bool True on success
     */
    public function transactionEnd();

    /**
     * Roll-back a transaction
     *
     * @param string $savepoint If set, roll-back to the named savepoint
     * @throws DatabaseException on failure
     * @return bool True on success
     */
    public function transactionRollback($savepoint = null);

    /**
     * Create a new savepoint
     *
     * @param string $savepoint The savepoint name
     * @throws DatabaseException on failure
     */
    public function transactionSavepoint($savepoint);

    /**
     * Return the depth of the transaction
     * For unnested transactions returns 1 while in a transaction, 0 otherwise
     *
     * @return int
     */
    public function transactionDepth();

    /**
     * Return true if savepoints are supported by this transaction manager
     *
     * @return boolean
     */
    public function supportsSavepoints();
}
