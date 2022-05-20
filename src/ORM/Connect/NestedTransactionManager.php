<?php

namespace SilverStripe\ORM\Connect;

/**
 * TransactionManager decorator that adds virtual nesting support.
 * Because this is managed in PHP and not the database, it has the following limitations:
 *   - Committing a nested transaction won't change anything until the parent transaction is committed
 *   - Rolling back a nested transaction means that the parent transaction must be rolled backed
 *
 * DBAL describes this behaviour nicely in their docs: https://www.doctrine-project.org/projects/doctrine-dbal/en/2.8/reference/transactions.html#transaction-nesting
 */

class NestedTransactionManager implements TransactionManager
{

    /**
     * @var int
     */
    protected $transactionNesting = 0;

    /**
     * @var TransactionManager
     */
    protected $child;

    /**
     * Set to true if all transactions must roll back to the parent
     * @var boolean
     */
    protected $mustRollback = false;

    /**
     * Create a NestedTransactionManager
     * @param TransactionManager $child The transaction manager that will handle the topmost transaction
     */
    public function __construct(TransactionManager $child)
    {
        $this->child = $child;
    }

    /**
     * Start a transaction
     * @throws DatabaseException on failure
     * @return bool True on success
     */
    public function transactionStart($transactionMode = false, $sessionCharacteristics = false)
    {
        if ($this->transactionNesting <= 0) {
            $this->transactionNesting = 1;
            $this->child->transactionStart($transactionMode, $sessionCharacteristics);
        } else {
            if ($this->child->supportsSavepoints()) {
                $this->child->transactionSavepoint("nesting" . $this->transactionNesting);
            }
            $this->transactionNesting++;
        }
    }

    public function transactionEnd($chain = false)
    {
        if ($this->mustRollback) {
            throw new DatabaseException("Child transaction was rolled back, so parent can't be committed");
        }

        if ($this->transactionNesting < 1) {
            throw new DatabaseException("Not within a transaction, so can't commit");
        }

        $this->transactionNesting--;

        if ($this->transactionNesting === 0) {
            $this->child->transactionEnd();
        }

        if ($chain) {
            return $this->transactionStart();
        }
    }

    public function transactionRollback($savepoint = null)
    {
        if ($this->transactionNesting < 1) {
            throw new DatabaseException("Not within a transaction, so can't roll back");
        }

        if ($savepoint) {
            return $this->child->transactionRollback($savepoint);
        }

        $this->transactionNesting--;

        if ($this->transactionNesting === 0) {
            $this->child->transactionRollback();
            $this->mustRollback = false;
        } else {
            if ($this->child->supportsSavepoints()) {
                $this->child->transactionRollback("nesting" . $this->transactionNesting);
                $this->mustRollback = false;

            // Without savepoints, parent transactions must roll back if a child one has
            } else {
                $this->mustRollback = true;
            }
        }
    }

    /**
     * Return the depth of the transaction.
     *
     * @return int
     */
    public function transactionDepth()
    {
        return $this->transactionNesting;
    }

    public function transactionSavepoint($savepoint)
    {
        return $this->child->transactionSavepoint($savepoint);
    }

    public function supportsSavepoints()
    {
        return $this->child->supportsSavepoints();
    }
}
