<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Dev\Deprecation;

/**
 * TransactionManager that executes MySQL-compatible transaction control queries
 */
class MySQLTransactionManager implements TransactionManager
{
    protected $dbConn;

    protected $inTransaction = false;

    public function __construct(Database $dbConn): void
    {
        $this->dbConn = $dbConn;
    }

    public function transactionStart(bool|string $transactionMode = false, bool $sessionCharacteristics = false): bool
    {
        if ($transactionMode || $sessionCharacteristics) {
            Deprecation::notice(
                '4.4',
                '$transactionMode and $sessionCharacteristics are deprecated and will be removed in SS5'
            );
        }

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

    public function transactionEnd($chain = false): bool
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

    public function transactionRollback(string $savepoint = null): bool
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

    public function transactionSavepoint(string $savepoint): void
    {
        $this->dbConn->query("SAVEPOINT $savepoint");
    }

    public function transactionDepth()
    {
        return (int)$this->inTransaction;
    }

    public function supportsSavepoints(): bool
    {
        return true;
    }
}
