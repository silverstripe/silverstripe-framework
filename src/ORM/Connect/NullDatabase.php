<?php

namespace SilverStripe\ORM\Connect;

use BadMethodCallException;
use Exception;

/**
 * Utility class required due to bad coupling in framework.
 * Not every framework execution should require a working database connection.
 * For example, when generating class and config manifests for deployment bundles,
 * or when generating code in a silverstripe/graphql schema build.
 *
 * This class creates the required no-ops to fulfill the contract,
 * and create exceptions as required.
 *
 * It also avoids introducing new third party core dependencies that
 * would be required with https://github.com/tractorcow/silverstripe-proxy-db.
 *
 * @internal
 */
class NullDatabase extends Database
{
    /**
     * @var string
     */
    private $errorMessage = 'Using NullDatabase, cannot interact with database';

    /**
     * @var string
     */
    private $queryErrorMessage = 'Using NullDatabase, cannot execute query: %s';

    /**
     * @param string $msg
     */
    public function setErrorMessage(string $msg): NullDatabase
    {
        $this->errorMessage = $msg;
        return $this;
    }

    /**
     * @param string $msg
     */
    public function setQueryErrorMessage(string $msg): NullDatabase
    {
        $this->queryErrorMessage = $msg;
        return $this;
    }

    /**
     * @throws NullDatabaseException
     */
    public function query($sql, $errorLevel = E_USER_ERROR)
    {
        throw new NullDatabaseException(sprintf($this->queryErrorMessage ?? '', $sql));
    }

    /**
     * @throws NullDatabaseException
     */
    public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR)
    {
        throw new NullDatabaseException(sprintf($this->queryErrorMessage ?? '', $sql));
    }

    /**
     * @throws NullDatabaseException
     */
    public function getConnector()
    {
        throw new NullDatabaseException($this->errorMessage);
    }

    /**
     * @throws NullDatabaseException
     */
    public function getSchemaManager()
    {
        throw new NullDatabaseException($this->errorMessage);
    }

    /**
     * @throws NullDatabaseException
     */
    public function getQueryBuilder()
    {
        throw new NullDatabaseException($this->errorMessage);
    }


    public function getGeneratedID($table)
    {
        // no-op
    }

    public function isActive()
    {
        return true;
    }

    public function escapeString($value)
    {
        return $value;
    }

    public function quoteString($value)
    {
        return $value;
    }

    public function escapeIdentifier($value, $separator = '.')
    {
        return $value;
    }

    protected function escapeColumnKeys($fieldValues)
    {
        return $fieldValues;
    }

    /**
     * @throws NullDatabaseException
     */
    public function manipulate($manipulation)
    {
        throw new NullDatabaseException($this->errorMessage);
    }

    /**
     * @throws NullDatabaseException
     */
    public function clearAllData()
    {
        throw new NullDatabaseException($this->errorMessage);
    }

    /**
     * @throws NullDatabaseException
     */
    public function clearTable($table)
    {
        throw new NullDatabaseException($this->errorMessage);
    }

    public function nullCheckClause($field, $isNull)
    {
        return '';
    }

    public function comparisonClause(
        $field,
        $value,
        $exact = false,
        $negate = false,
        $caseSensitive = null,
        $parameterised = false
    ) {
        return '';
    }

    public function formattedDatetimeClause($date, $format)
    {
        return '';
    }

    public function datetimeIntervalClause($date, $interval)
    {
        return '';
    }

    public function datetimeDifferenceClause($date1, $date2)
    {
        return '';
    }

    public function concatOperator()
    {
        return '';
    }

    public function supportsCollations()
    {
        return false;
    }

    public function supportsTimezoneOverride()
    {
        return false;
    }

    public function getVersion()
    {
        return '';
    }

    public function getDatabaseServer()
    {
        return '';
    }

    public function affectedRows()
    {
        return 0;
    }

    public function searchEngine(
        $classesToSearch,
        $keywords,
        $start,
        $pageLength,
        $sortBy = "Relevance DESC",
        $extraFilter = "",
        $booleanSearch = false,
        $alternativeFileFilter = "",
        $invertedMatch = false
    ) {
        // no-op
    }

    public function supportsTransactions()
    {
        return false;
    }

    public function supportsSavepoints()
    {
        return false;
    }


    public function supportsTransactionMode(string $mode): bool
    {
        return false;
    }

    public function withTransaction(
        $callback,
        $errorCallback = null,
        $transactionMode = false,
        $errorIfTransactionsUnsupported = false
    ) {
        // no-op
    }

    public function supportsExtensions($extensions)
    {
        return false;
    }

    public function transactionStart($transactionMode = false, $sessionCharacteristics = false)
    {
        // no-op
    }

    public function transactionSavepoint($savepoint)
    {
        // no-op
    }

    public function transactionRollback($savepoint = false)
    {
        // no-op
    }

    public function transactionEnd(): bool|null
    {
        return false;
    }

    public function transactionDepth()
    {
        return 0;
    }

    public function supportsLocks()
    {
        return false;
    }

    public function canLock($name)
    {
        return false;
    }

    public function getLock($name, $timeout = 5)
    {
        return false;
    }

    public function releaseLock($name)
    {
        return false;
    }

    public function connect($parameters)
    {
        // no-op
    }

    public function databaseExists($name)
    {
        return false;
    }

    public function databaseList()
    {
        return [];
    }

    public function selectDatabase($name, $create = false, $errorLevel = E_USER_ERROR)
    {
        // no-op
    }

    public function dropSelectedDatabase()
    {
        // no-op
    }

    public function getSelectedDatabase()
    {
        // no-op
    }

    public function now()
    {
        return '';
    }

    public function random()
    {
        return '';
    }
}
