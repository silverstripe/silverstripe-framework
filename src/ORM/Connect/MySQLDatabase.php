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
class MySQLDatabase extends Database implements TransactionManager
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
     * SQL Mode used on connections to MySQL. Defaults to ANSI. For basic ORM
     * compatibility, this setting must always include ANSI or ANSI_QUOTES.
     *
     * @config
     * @var string
     */
    private static $sql_mode = 'ANSI';

    /**
     * Cache for getTransactionManager()
     *
     * @var TransactionManager
     */
    private $transactionManager = null;

    private int $transactionNesting = 0;

    /**
     * Default collation
     *
     * @config
     * @var string
     */
    private static $collation = 'utf8_general_ci';

    public function connect($parameters)
    {
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

        // Set sql_mode
        $this->setSQLMode(static::config()->get('sql_mode'));

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
        $this->preparedQuery("SET sql_mode = ?", [$mode]);
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
        $this->preparedQuery("SET SESSION time_zone = ?", [$timezone]);
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
     * Caution: While the $keywords argument is escaped for safe use in a query context,
     * you need to ensure that it is also a valid boolean expression when opting into $booleanSearch.
     * For example, the "asterisk" and "greater than" characters have a special meaning in this context,
     * and can only be placed in certain parts of the keywords. You will need to preprocess and sanitise
     * user input accordingly in order to avoid query errors.
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
        if (!class_exists($pageClass ?? '')) {
            throw new Exception('MySQLDatabase->searchEngine() requires "SiteTree" class');
        }
        if (!class_exists($fileClass ?? '')) {
            throw new Exception('MySQLDatabase->searchEngine() requires "File" class');
        }

        $keywords = $this->escapeString($keywords);
        $htmlEntityKeywords = htmlentities($keywords ?? '', ENT_NOQUOTES, 'UTF-8');

        $extraFilters = [$pageClass => '', $fileClass => ''];

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
        if (array_key_exists('ShowInSearch', $fields ?? [])) {
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
            $booleanChars = ['*', '+', '@', '-', '(', ')', '<', '>'];
            $relevanceKeywords = str_replace($booleanChars ?? '', '', $keywords ?? '');
            $htmlEntityRelevanceKeywords = str_replace($booleanChars ?? '', '', $htmlEntityKeywords ?? '');
            $relevance[$pageClass] = "MATCH (Title, MenuTitle, Content, MetaDescription) "
                    . "AGAINST ('$relevanceKeywords') "
                    . "+ MATCH (Title, MenuTitle, Content, MetaDescription) AGAINST ('$htmlEntityRelevanceKeywords')";
            $relevance[$fileClass] = "MATCH (Name, Title) AGAINST ('$relevanceKeywords')";
        } else {
            $relevance[$pageClass] = $relevance[$fileClass] = 1;
            $match[$pageClass] = $match[$fileClass] = "1 = 1";
        }

        // Generate initial DataLists and base table names
        $lists = [];
        $sqlTables = [$pageClass => '', $fileClass => ''];
        foreach ($classesToSearch as $class) {
            $lists[$class] = DataList::create($class)->where($notMatch . $match[$class] . $extraFilters[$class]);
            $sqlTables[$class] = '"' . DataObject::getSchema()->tableName($class) . '"';
        }

        $charset = static::config()->get('charset');

        // Make column selection lists
        $select = [
            $pageClass => [
                "ClassName", "{$sqlTables[$pageClass]}.\"ID\"", "ParentID",
                "Title", "MenuTitle", "URLSegment", "Content",
                "LastEdited", "Created",
                "Name" => "_{$charset}''",
                "Relevance" => $relevance[$pageClass], "CanViewType"
            ],
            $fileClass => [
                "ClassName", "{$sqlTables[$fileClass]}.\"ID\"", "ParentID",
                "Title", "MenuTitle" => "_{$charset}''", "URLSegment" => "_{$charset}''", "Content" => "_{$charset}''",
                "LastEdited", "Created",
                "Name",
                "Relevance" => $relevance[$fileClass], "CanViewType" => "NULL"
            ],
        ];

        // Process and combine queries
        $querySQLs = [];
        $queryParameters = [];
        $totalCount = 0;
        foreach ($lists as $class => $list) {
            $query = $list->dataQuery()->query();

            // There's no need to do all that joining
            $query->setFrom($sqlTables[$class]);
            $query->setSelect($select[$class]);
            $query->setOrderBy([]);

            $querySQLs[] = $query->sql($parameters);
            $queryParameters = array_merge($queryParameters, $parameters);

            $totalCount += $query->unlimitedRowCount();
        }
        $fullQuery = implode(" UNION ", $querySQLs) . " ORDER BY $sortBy LIMIT $limit";

        // Get records
        $records = $this->preparedQuery($fullQuery, $queryParameters);

        $objects = [];

        foreach ($records as $record) {
            $objects[] = new $record['ClassName']($record, DataObject::CREATE_HYDRATED);
        }

        $list = new PaginatedList(new ArrayList($objects));
        $list->setPageStart($start);
        $list->setPageLength($pageLength);
        $list->setTotalItems($totalCount);

        // The list has already been limited by the query above
        $list->setLimitItems(false);

        return $list;
    }

    public function supportsCteQueries(bool $recursive = false): bool
    {
        $version = $this->getVersion();
        $mariaDBVersion = $this->getMariaDBVersion($version);
        if ($mariaDBVersion) {
            // MariaDB has supported CTEs since 10.2.1, and recursive CTEs from 10.2.2
            // see https://mariadb.com/kb/en/mariadb-1021-release-notes/ and https://mariadb.com/kb/en/mariadb-1022-release-notes/
            $supportedFrom = $recursive ? '10.2.2' : '10.2.1';
            return $this->compareVersion($mariaDBVersion, $supportedFrom) >= 0;
        }
        // MySQL has supported both kinds of CTEs since 8.0.1
        // see https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-1.html
        return $this->compareVersion($version, '8.0.1') >= 0;
    }

    private function getMariaDBVersion(string $version): ?string
    {
        // MariaDB versions look like "5.5.5-10.6.8-mariadb-1:10.6.8+maria~focal"
        // or "10.8.3-MariaDB-1:10.8.3+maria~jammy"
        // The relevant part is the x.y.z-mariadb portion.
        if (!preg_match('/((\d+\.){2}\d+)-mariadb/i', $version, $matches)) {
            return null;
        }
        return $matches[1];
    }

    private function compareVersion(string $actualVersion, string $atLeastVersion): int
    {
        // Assume it's lower if it's not a proper version number
        if (!preg_match('/^(\d+\.){2}\d+$/', $actualVersion)) {
            return -1;
        }
        return version_compare($actualVersion, $atLeastVersion);
    }

    /**
     * Returns the TransactionManager to handle transactions for this database.
     *
     * @return TransactionManager
     */
    protected function getTransactionManager()
    {
        if (!$this->transactionManager) {
            $this->transactionManager = new NestedTransactionManager(new MySQLTransactionManager($this));
        }
        return $this->transactionManager;
    }
    public function supportsTransactions()
    {
        return true;
    }
    public function supportsSavepoints()
    {
        return $this->getTransactionManager()->supportsSavepoints();
    }

    public function transactionStart($transactionMode = false, $sessionCharacteristics = false)
    {
        $this->getTransactionManager()->transactionStart($transactionMode, $sessionCharacteristics);
    }

    public function transactionSavepoint($savepoint)
    {
        $this->getTransactionManager()->transactionSavepoint($savepoint);
    }

    public function transactionRollback($savepoint = false)
    {
        return $this->getTransactionManager()->transactionRollback($savepoint);
    }

    public function transactionDepth()
    {
        return $this->getTransactionManager()->transactionDepth();
    }

    public function transactionEnd():bool|null
    {
        $result = $this->getTransactionManager()->transactionEnd();

        return $result;
    }

    /**
     * In error condition, set transactionNesting to zero
     */
    protected function resetTransactionNesting()
    {
        // Check whether to use a connector's built-in transaction methods
        if ($this->connector instanceof TransactionalDBConnector) {
            if ($this->transactionNesting > 0) {
                $this->connector->transactionRollback();
            }
        }
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
        preg_match_all('/%(.)/', $format ?? '', $matches);
        foreach ($matches[1] as $match) {
            if (array_search($match, ['Y', 'm', 'd', 'H', 'i', 's', 'U']) === false) {
                user_error('formattedDatetimeClause(): unsupported format character %' . $match, E_USER_WARNING);
            }
        }

        if (preg_match('/^now$/i', $date ?? '')) {
            $date = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date ?? '')) {
            $date = "'$date'";
        }

        if ($format == '%U') {
            return "UNIX_TIMESTAMP($date)";
        }

        return "DATE_FORMAT($date, '$format')";
    }

    public function datetimeIntervalClause($date, $interval)
    {
        $interval = preg_replace('/(year|month|day|hour|minute|second)s/i', '$1', $interval ?? '');

        if (preg_match('/^now$/i', $date ?? '')) {
            $date = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date ?? '')) {
            $date = "'$date'";
        }

        return "$date + INTERVAL $interval";
    }

    public function datetimeDifferenceClause($date1, $date2)
    {
        // First date format
        if (preg_match('/^now$/i', $date1 ?? '')) {
            $date1 = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date1 ?? '')) {
            $date1 = "'$date1'";
        }
        // Second date format
        if (preg_match('/^now$/i', $date2 ?? '')) {
            $date2 = "NOW()";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i', $date2 ?? '')) {
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

        // MySQL 5.7.4 and below auto-releases existing locks on subsequent GET_LOCK() calls.
        // MySQL 5.7.5 and newer allow multiple locks per sessions even with the same name.
        // https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html#function_get-lock
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

    /**
     * Clear all data in a given table
     *
     * @param string $table Name of table
     */
    public function clearTable($table)
    {
        $this->query("DELETE FROM \"$table\"");

        // Check if resetting the auto-increment is needed
        $autoIncrement = $this->preparedQuery(
            'SELECT "AUTO_INCREMENT" FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [ $this->getSelectedDatabase(), $table]
        )->value();

        if ($autoIncrement > 1) {
            $this->query("ALTER TABLE \"$table\" AUTO_INCREMENT = 1");
        }
    }
}
