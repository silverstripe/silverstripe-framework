<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLSelect;
use Exception;

/**
 * MySQL connector class.
 *
 * Supported indexes for {@link requireTable()}
 *
 * You are advised to backup your tables if changing settings on an existing database
 * `connection_charset` and `charset` should be equal, similarly so should `connection_collation` and `collation`
 */
class MySQLDatabase extends Database
{
    use Configurable;

    /**
     * Default connection charset (may be overridden in $databaseConfig)
     *
     * @config
     * @var String
     */
    private static $connection_charset = 'utf8';

    /**
     * Default connection collation
     *
     * @config
     * @var string
     */
    private static $connection_collation = 'utf8_general_ci';

    /**
     * Default charset
     *
     * @config
     * @var string
     */
    private static $charset = 'utf8';

    /**
     * Default collation
     *
     * @config
     * @var string
     */
    private static $collation = 'utf8_general_ci';

    /**
     * @var bool
     */
    protected $transactionNesting = 0;

    public function connect($parameters)
    {
        // Ensure that driver is available (required by PDO)
        if (empty($parameters['driver'])) {
            $parameters['driver'] = $this->getDatabaseServer();
        }

        // Set charset
        if (empty($parameters['charset']) && ($charset = static::config()->get('connection_charset'))) {
            $parameters['charset'] = $charset;
        }

        // Set collation
        if (empty($parameters['collation']) && ($collation = static::config()->get('connection_collation'))) {
            $parameters['collation'] = $collation;
        }

        // Notify connector of parameters
        $this->connector->connect($parameters);

        // This is important!
        $this->setSQLMode('ANSI');

        if (isset($parameters['timezone'])) {
            $this->selectTimezone($parameters['timezone']);
        }

        // SS_Database subclass maintains responsibility for selecting database
        // once connected in order to correctly handle schema queries about
        // existence of database, error handling at the correct level, etc
        if (!empty($parameters['database'])) {
            $this->selectDatabase($parameters['database'], false, false);
        }
    }

    /**
     * Sets the SQL mode
     *
     * @param string $mode Connection mode
     */
    public function setSQLMode($mode)
    {
        if (empty($mode)) {
            return;
        }
        $this->preparedQuery("SET sql_mode = ?", array($mode));
    }

    /**
     * Sets the system timezone for the database connection
     *
     * @param string $timezone
     */
    public function selectTimezone($timezone)
    {
        if (empty($timezone)) {
            return;
        }
        $this->preparedQuery("SET SESSION time_zone = ?", array($timezone));
    }

    public function supportsCollations()
    {
        return true;
    }

    public function supportsTimezoneOverride()
    {
        return true;
    }

    public function getDatabaseServer()
    {
        return "mysql";
    }

    /**
     * The core search engine, used by this class and its subclasses to do fun stuff.
     * Searches both SiteTree and File.
     *
     * @param array $classesToSearch
     * @param string $keywords Keywords as a string.
     * @param int $start
     * @param int $pageLength
     * @param string $sortBy
     * @param string $extraFilter
     * @param bool $booleanSearch
     * @param string $alternativeFileFilter
     * @param bool $invertedMatch
     * @return PaginatedList
     * @throws Exception
     */
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
        $pageClass = SiteTree::class;
        $fileClass = File::class;
        if (!class_exists($pageClass)) {
            throw new Exception('MySQLDatabase->searchEngine() requires "SiteTree" class');
        }
        if (!class_exists($fileClass)) {
            throw new Exception('MySQLDatabase->searchEngine() requires "File" class');
        }

        $keywords = $this->escapeString($keywords);
        $htmlEntityKeywords = htmlentities($keywords, ENT_NOQUOTES, 'UTF-8');

        $extraFilters = array($pageClass => '', $fileClass => '');

        $boolean = '';
        if ($booleanSearch) {
            $boolean = "IN BOOLEAN MODE";
        }

        if ($extraFilter) {
            $extraFilters[$pageClass] = " AND $extraFilter";

            if ($alternativeFileFilter) {
                $extraFilters[$fileClass] = " AND $alternativeFileFilter";
            } else {
                $extraFilters[$fileClass] = $extraFilters[$pageClass];
            }
        }

        // Always ensure that only pages with ShowInSearch = 1 can be searched
        $extraFilters[$pageClass] .= " AND ShowInSearch <> 0";

        // File.ShowInSearch was added later, keep the database driver backwards compatible
        // by checking for its existence first
        $fileTable = DataObject::getSchema()->tableName($fileClass);
        $fields = $this->getSchemaManager()->fieldList($fileTable);
        if (array_key_exists('ShowInSearch', $fields)) {
            $extraFilters[$fileClass] .= " AND ShowInSearch <> 0";
        }

        $limit = (int)$start . ", " . (int)$pageLength;

        $notMatch = $invertedMatch
                ? "NOT "
                : "";
        if ($keywords) {
            $match[$pageClass] = "
				MATCH (Title, MenuTitle, Content, MetaDescription) AGAINST ('$keywords' $boolean)
				+ MATCH (Title, MenuTitle, Content, MetaDescription) AGAINST ('$htmlEntityKeywords' $boolean)
			";
            $fileClassSQL = Convert::raw2sql($fileClass);
            $match[$fileClass] = "MATCH (Name, Title) AGAINST ('$keywords' $boolean) AND ClassName = '$fileClassSQL'";

            // We make the relevance search by converting a boolean mode search into a normal one
            $relevanceKeywords = str_replace(array('*', '+', '-'), '', $keywords);
            $htmlEntityRelevanceKeywords = str_replace(array('*', '+', '-'), '', $htmlEntityKeywords);
            $relevance[$pageClass] = "MATCH (Title, MenuTitle, Content, MetaDescription) "
                    . "AGAINST ('$relevanceKeywords') "
                    . "+ MATCH (Title, MenuTitle, Content, MetaDescription) AGAINST ('$htmlEntityRelevanceKeywords')";
            $relevance[$fileClass] = "MATCH (Name, Title) AGAINST ('$relevanceKeywords')";
        } else {
            $relevance[$pageClass] = $relevance[$fileClass] = 1;
            $match[$pageClass] = $match[$fileClass] = "1 = 1";
        }

        // Generate initial DataLists and base table names
        $lists = array();
        $sqlTables = array($pageClass => '', $fileClass => '');
        foreach ($classesToSearch as $class) {
            $lists[$class] = DataList::create($class)->where($notMatch . $match[$class] . $extraFilters[$class]);
            $sqlTables[$class] = '"' . DataObject::getSchema()->tableName($class) . '"';
        }

        $charset = static::config()->get('charset');

        // Make column selection lists
        $select = array(
            $pageClass => array(
                "ClassName", "{$sqlTables[$pageClass]}.\"ID\"", "ParentID",
                "Title", "MenuTitle", "URLSegment", "Content",
                "LastEdited", "Created",
                "Name" => "_{$charset}''",
                "Relevance" => $relevance[$pageClass], "CanViewType"
            ),
            $fileClass => array(
                "ClassName", "{$sqlTables[$fileClass]}.\"ID\"", "ParentID",
                "Title", "MenuTitle" => "_{$charset}''", "URLSegment" => "_{$charset}''", "Content" => "_{$charset}''",
                "LastEdited", "Created",
                "Name",
                "Relevance" => $relevance[$fileClass], "CanViewType" => "NULL"
            ),
        );

        // Process and combine queries
        $querySQLs = array();
        $queryParameters = array();
        $totalCount = 0;
        foreach ($lists as $class => $list) {
            /** @var SQLSelect $query */
            $query = $list->dataQuery()->query();

            // There's no need to do all that joining
            $query->setFrom($sqlTables[$class]);
            $query->setSelect($select[$class]);
            $query->setOrderBy(array());

            $querySQLs[] = $query->sql($parameters);
            $queryParameters = array_merge($queryParameters, $parameters);

            $totalCount += $query->unlimitedRowCount();
        }
        $fullQuery = implode(" UNION ", $querySQLs) . " ORDER BY $sortBy LIMIT $limit";

        // Get records
        $records = $this->preparedQuery($fullQuery, $queryParameters);

        $objects = array();

        foreach ($records as $record) {
            $objects[] = new $record['ClassName']($record);
        }

        $list = new PaginatedList(new ArrayList($objects));
        $list->setPageStart($start);
        $list->setPageLength($pageLength);
        $list->setTotalItems($totalCount);

        // The list has already been limited by the query above
        $list->setLimitItems(false);

        return $list;
    }

    public function supportsTransactions()
    {
        return true;
    }

    public function transactionStart($transactionMode = false, $sessionCharacteristics = false)
    {
        if ($this->transactionNesting > 0) {
            $this->transactionSavepoint('NESTEDTRANSACTION' . $this->transactionNesting);
        } else {
            // This sets the isolation level for the NEXT transaction, not the current one.
            if ($transactionMode) {
                $this->query('SET TRANSACTION ' . $transactionMode);
            }

            $this->query('START TRANSACTION');

            if ($sessionCharacteristics) {
                $this->query('SET SESSION TRANSACTION ' . $sessionCharacteristics);
            }
        }
        ++$this->transactionNesting;
    }

    public function transactionSavepoint($savepoint)
    {
        $this->query("SAVEPOINT $savepoint");
    }

    public function transactionRollback($savepoint = false)
    {
        // Named transaction
        if ($savepoint) {
            $this->query('ROLLBACK TO ' . $savepoint);
            return true;
        }

        // Fail if transaction isn't available
        if (!$this->transactionNesting) {
            return false;
        }
        --$this->transactionNesting;
        if ($this->transactionNesting > 0) {
            $this->transactionRollback('NESTEDTRANSACTION' . $this->transactionNesting);
        } else {
            $this->query('ROLLBACK');
        }
        return true;
    }

    public function transactionDepth()
    {
        return $this->transactionNesting;
    }

    public function transactionEnd($chain = false)
    {
        // Fail if transaction isn't available
        if (!$this->transactionNesting) {
            return false;
        }
        --$this->transactionNesting;
        if ($this->transactionNesting <= 0) {
            $this->transactionNesting = 0;
            $this->query('COMMIT AND ' . ($chain ? '' : 'NO ') . 'CHAIN');
        }
        return true;
    }

    /**
     * In error condition, set transactionNesting to zero
     */
    protected function resetTransactionNesting()
    {
        $this->transactionNesting = 0;
    }

    public function query($sql, $errorLevel = E_USER_ERROR)
    {
        $this->inspectQuery($sql);
        return parent::query($sql, $errorLevel);
    }

    public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR)
    {
        $this->inspectQuery($sql);
        return parent::preparedQuery($sql, $parameters, $errorLevel);
    }

    /**
     * Inspect a SQL query prior to execution
     *
     * @param string $sql
     */
    protected function inspectQuery($sql)
    {
        // Any DDL discards transactions.
        // See https://dev.mysql.com/doc/internals/en/transactions-notes-on-ddl-and-normal-transaction.html
        // on why we need to be over-eager
        $isDDL = $this->getConnector()->isQueryDDL($sql);
        if ($isDDL) {
            $this->resetTransactionNesting();
        }
    }

    public function comparisonClause(
        $field,
        $value,
        $exact = false,
        $negate = false,
        $caseSensitive = null,
        $parameterised = false
    ) {
        if ($exact && $caseSensitive === null) {
            $comp = ($negate) ? '!=' : '=';
        } else {
            $comp = ($caseSensitive) ? 'LIKE BINARY' : 'LIKE';
            if ($negate) {
                $comp = 'NOT ' . $comp;
            }
        }

        if ($parameterised) {
            return sprintf("%s %s ?", $field, $comp);
        } else {
            return sprintf("%s %s '%s'", $field, $comp, $value);
        }
    }

    public function formattedDatetimeClause($date, $format)
    {
        preg_match_all('/%(.)/', $format, $matches);
        foreach ($matches[1] as $match) {
            if (array_search($match, array('Y', 'm', 'd', 'H', 'i', 's', 'U')) === false) {
                user_error('formattedDatetimeClause(): unsupported format character %' . $match, E_USER_WARNING);
            }
        }

        if (preg_match('/^now$/i', $date)) {
            $date = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date)) {
            $date = "'$date'";
        }

        if ($format == '%U') {
            return "UNIX_TIMESTAMP($date)";
        }

        return "DATE_FORMAT($date, '$format')";
    }

    public function datetimeIntervalClause($date, $interval)
    {
        $interval = preg_replace('/(year|month|day|hour|minute|second)s/i', '$1', $interval);

        if (preg_match('/^now$/i', $date)) {
            $date = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date)) {
            $date = "'$date'";
        }

        return "$date + INTERVAL $interval";
    }

    public function datetimeDifferenceClause($date1, $date2)
    {
        // First date format
        if (preg_match('/^now$/i', $date1)) {
            $date1 = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date1)) {
            $date1 = "'$date1'";
        }
        // Second date format
        if (preg_match('/^now$/i', $date2)) {
            $date2 = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date2)) {
            $date2 = "'$date2'";
        }

        return "UNIX_TIMESTAMP($date1) - UNIX_TIMESTAMP($date2)";
    }

    public function supportsLocks()
    {
        return true;
    }

    public function canLock($name)
    {
        $id = $this->getLockIdentifier($name);
        return (bool) $this->query(sprintf("SELECT IS_FREE_LOCK('%s')", $id))->value();
    }

    public function getLock($name, $timeout = 5)
    {
        $id = $this->getLockIdentifier($name);

        // MySQL auto-releases existing locks on subsequent GET_LOCK() calls,
        // in contrast to PostgreSQL and SQL Server who stack the locks.
        return (bool) $this->query(sprintf("SELECT GET_LOCK('%s', %d)", $id, $timeout))->value();
    }

    public function releaseLock($name)
    {
        $id = $this->getLockIdentifier($name);
        return (bool) $this->query(sprintf("SELECT RELEASE_LOCK('%s')", $id))->value();
    }

    protected function getLockIdentifier($name)
    {
        // Prefix with database name
        $dbName = $this->connector->getSelectedDatabase() ;
        return $this->escapeString("{$dbName}_{$name}");
    }

    public function now()
    {
        // MySQL uses NOW() to return the current date/time.
        return 'NOW()';
    }

    public function random()
    {
        return 'RAND()';
    }
}
