<?php


namespace SilverStripe\ORM;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Injected into DataQuery to augment getFinalisedQuery() with a join table
 */
class ManyManyThroughQueryManipulator implements DataQueryManipulator
{

    use Injectable;

    /**
     * DataObject that backs the joining table
     *
     * @var string
     */
    protected $joinClass;

    /**
     * Key that joins to the data class
     *
     * @var string $localKey
     */
    protected $localKey;

    /**
     * Key that joins to the parent class
     *
     * @var string $foreignKey
     */
    protected $foreignKey;

    /**
     * Build query manipulator for a given join table. Additional parameters (foreign key, etc)
     * will be infered at evaluation from query parameters set via the ManyManyThroughList
     *
     * @param string $joinClass Class name of the joined dataobject record
     * @param string $localKey The key in the join table that maps to the dataClass' PK.
     * @param string $foreignKey The key in the join table that maps to joined class' PK.
     */
    public function __construct($joinClass, $localKey, $foreignKey)
    {
        $this->setJoinClass($joinClass);
        $this->setLocalKey($localKey);
        $this->setForeignKey($foreignKey);
    }

    /**
     * @return string
     */
    public function getJoinClass()
    {
        return $this->joinClass;
    }

    /**
     * @param mixed $joinClass
     * @return $this
     */
    public function setJoinClass($joinClass)
    {
        $this->joinClass = $joinClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocalKey()
    {
        return $this->localKey;
    }

    /**
     * @param string $localKey
     * @return $this
     */
    public function setLocalKey($localKey)
    {
        $this->localKey = $localKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @param string $foreignKey
     * @return $this
     */
    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    /**
     * Get has_many relationship between parent and join table (for a given DataQuery)
     *
     * @param DataQuery $query
     * @return HasManyList
     */
    public function getParentRelationship(DataQuery $query)
    {
        // Create has_many
        $list = HasManyList::create($this->getJoinClass(), $this->getForeignKey());
        $list = $list->setDataQueryParam($this->extractInheritableQueryParameters($query));

        // Limit to given foreign key
        if ($foreignID = $query->getQueryParam('Foreign.ID')) {
            $list = $list->forForeignID($foreignID);
        }
        return $list;
    }

    /**
     * Calculate the query parameters that should be inherited from the base many_many
     * to the nested has_many list.
     *
     * @param DataQuery $query
     * @return mixed
     */
    public function extractInheritableQueryParameters(DataQuery $query)
    {
        $params = $query->getQueryParams();

        // Remove `Foreign.` query parameters for created objects,
        // as this would interfere with relations on those objects.
        foreach (array_keys($params) as $key) {
            if (stripos($key, 'Foreign.') === 0) {
                unset($params[$key]);
            }
        }

        // Get inheritable parameters from an instance of the base query dataclass
        $inst = Injector::inst()->create($query->dataClass());
        $inst->setSourceQueryParams($params);
        return $inst->getInheritableQueryParams();
    }

    /**
     * Get name of join table alias for use in queries.
     *
     * @return string
     */
    public function getJoinAlias()
    {
        return DataObject::getSchema()->tableName($this->getJoinClass());
    }

    /**
     * Invoked prior to getFinalisedQuery()
     *
     * @param DataQuery $dataQuery
     * @param array $queriedColumns
     * @param SQLSelect $sqlSelect
     */
    public function beforeGetFinalisedQuery(DataQuery $dataQuery, $queriedColumns = [], SQLSelect $sqlSelect)
    {
        // Get metadata and SQL from join table
        $hasManyRelation = $this->getParentRelationship($dataQuery);
        $joinTableSQLSelect = $hasManyRelation->dataQuery()->query();
        $joinTableSQL = $joinTableSQLSelect->sql($joinTableParameters);
        $joinTableColumns = array_keys($joinTableSQLSelect->getSelect()); // Get aliases (keys) only
        $joinTableAlias = $this->getJoinAlias();

        // Get fields to join on
        $localKey = $this->getLocalKey();
        $schema = DataObject::getSchema();
        $baseTable = $schema->baseDataClass($dataQuery->dataClass());
        $childField = $schema->sqlColumnForField($baseTable, 'ID');

        // Add select fields
        foreach ($joinTableColumns as $joinTableColumn) {
            $sqlSelect->selectField(
                "\"{$joinTableAlias}\".\"{$joinTableColumn}\"",
                "{$joinTableAlias}_{$joinTableColumn}"
            );
        }

        // Apply join and record sql for later insertion (at end of replacements)
        $sqlSelect->addInnerJoin(
            '(SELECT $$_SUBQUERY_$$)',
            "\"{$joinTableAlias}\".\"{$localKey}\" = {$childField}",
            $joinTableAlias,
            20,
            $joinTableParameters
        );
        $dataQuery->setQueryParam('Foreign.JoinTableSQL', $joinTableSQL);

        // After this join, and prior to afterGetFinalisedQuery, $sqlSelect will be populated with the
        // necessary sql rewrites (versioned, etc) that effect the base table.
        // By using a placeholder for the subquery we can protect the subquery (already rewritten)
        // from being re-written a second time. However we DO want the join predicate (above) to be rewritten.
        // See http://php.net/manual/en/function.str-replace.php#refsect1-function.str-replace-notes
        // for the reason we only add the final substitution at the end of getFinalisedQuery()
    }

    /**
     * Invoked after getFinalisedQuery()
     *
     * @param DataQuery $dataQuery
     * @param array $queriedColumns
     * @param SQLSelect $sqlQuery
     */
    public function afterGetFinalisedQuery(DataQuery $dataQuery, $queriedColumns = [], SQLSelect $sqlQuery)
    {
        // Inject final replacement after manipulation has been performed on the base dataquery
        $joinTableSQL = $dataQuery->getQueryParam('Foreign.JoinTableSQL');
        if ($joinTableSQL) {
            $sqlQuery->replaceText('SELECT $$_SUBQUERY_$$', $joinTableSQL);
            $dataQuery->setQueryParam('Foreign.JoinTableSQL', null);
        }
    }
}
