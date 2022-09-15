<?php

namespace SilverStripe\ORM\Connect;

/**
 * Represents an object that is capable of controlling transactions.
 *
 * The TransactionManager might be the database connection itself, calling queries to orchestrate
 * transactions, or a connector.
 *
 * Generally speaking you should rely on your Database object to manage the creation of a TansactionManager
 * for you; unless you are building new database connectors this should be treated as an internal API.
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
     * Return true if savepoints are supported by this transaction manager.
     * Savepoints aren't supported by all database connectors and should be
     * used with caution.
     *
     * @return boolean
     */
    public function supportsSavepoints();
}
