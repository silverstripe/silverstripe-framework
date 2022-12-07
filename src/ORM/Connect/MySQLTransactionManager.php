<?php

namespace SilverStripe\ORM\Connect;

/**
 * TransactionManager that executes MySQL-compatible transaction control queries
 */
class MySQLTransactionManager implements TransactionManager
{
    protected $dbConn;

    protected $inTransaction = false;

    public function __construct(Database $dbConn)
    {
        $this->dbConn = $dbConn;
    }

    public function transactionStart($transactionMode = false, $sessionCharacteristics = false)
    {
        if ($this->inTransaction) {
            throw new DatabaseException(
                "Already in transaction, can't start another. Consider decorating with NestedTransactionManager."
            );
        }

        // This sets the isolation level for the NEXT transaction, not the current one.
        if ($transactionMode) {
            $this->dbConn->query('SET TRANSACTION ' . $transactionMode);
        }

        $this->dbConn->query('START TRANSACTION');

        if ($sessionCharacteristics) {
            $this->dbConn->query('SET SESSION TRANSACTION ' . $sessionCharacteristics);
        }

        $this->inTransaction = true;
        return true;
    }

    public function transactionEnd($chain = false)
    {
        if (!$this->inTransaction) {
            throw new DatabaseException("Not in transaction, can't end.");
        }

        if ($chain) {
            user_error(
                "transactionEnd() chain argument no longer implemented. Use NestedTransactionManager",
                E_USER_WARNING
            );
        }

        $this->dbConn->query('COMMIT');

        $this->inTransaction = false;
        return true;
    }

    public function transactionRollback($savepoint = null)
    {
        if (!$this->inTransaction) {
            throw new DatabaseException("Not in transaction, can't roll back.");
        }

        if ($savepoint) {
            $this->dbConn->query("ROLLBACK TO SAVEPOINT $savepoint");
        } else {
            $this->dbConn->query('ROLLBACK');
            $this->inTransaction = false;
        }

        return true;
    }

    public function transactionSavepoint($savepoint)
    {
        $this->dbConn->query("SAVEPOINT $savepoint");
    }

    public function transactionDepth()
    {
        return (int)$this->inTransaction;
    }

    public function supportsSavepoints()
    {
        return true;
    }
}
