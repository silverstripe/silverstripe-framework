<?php


namespace SilverStripe\ORM;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Injected into DataQuery to augment getFinalisedQuery() with a join table
 *
 * @template TJoin of DataObject
 */
class ManyManyThroughQueryManipulator implements DataQueryManipulator
{

    use Injectable;

    /**
     * DataObject that backs the joining table
     *
     * @var class-string<TJoin>
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
     * Foreign class 'from' property. Normally not needed unless polymorphic.
     *
     * @var string
     */
    protected $foreignClass;

    /**
     * Class name of instance that owns this list
     *
     * @var string
     */
    protected $parentClass;

    /**
     * Build query manipulator for a given join table. Additional parameters (foreign key, etc)
     * will be inferred at evaluation from query parameters set via the ManyManyThroughList
     *
     * @param class-string<TJoin> $joinClass
     * @param string $foreignClass
     * @param string $parentClass
     */
    public function __construct(string $joinClass, string $localKey, string $foreignKey, string $foreignClass, string $parentClass)
    {
        $this->setJoinClass($joinClass);
        $this->setLocalKey($localKey);
        $this->setForeignKey($foreignKey);
        if ($foreignClass) {
            $this->setForeignClass($foreignClass);
        }
        if ($parentClass) {
            $this->setParentClass($parentClass);
        }
    }

    /**
     * @return class-string<TJoin>
     */
    public function getJoinClass()
    {
        return $this->joinClass;
    }

    /**
     * @param class-string<TJoin> $joinClass
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
     * Gets ID key name for foreign key component
     *
     * @return string
     */
    public function getForeignIDKey()
    {
        $key = $this->getForeignKey();
        if ($this->getForeignClass() === DataObject::class) {
            return $key . 'ID';
        }
        return $key;
    }

    /**
     * Gets Class key name for foreign key component (or null if none)
     *
     * @return string|null
     */
    public function getForeignClassKey()
    {
        if ($this->getForeignClass() === DataObject::class) {
            return $this->getForeignKey() . 'Class';
        }
        return null;
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
     * @return HasManyList<TJoin>
     */
    public function getParentRelationship(DataQuery $query)
    {
        // Create has_many
        if ($this->getForeignClass() === DataObject::class) {
            /** @internal Polymorphic many_many is experimental */
            $list = PolymorphicHasManyList::create(
                $this->getJoinClass(),
                $this->getForeignKey(),
                $this->getParentClass()
            );
        } else {
            $list = HasManyList::create($this->getJoinClass(), $this->getForeignKey());
        }
        $list = $list->setDataQueryParam($this->extractInheritableQueryParameters($query));

        // Limit to given foreign key
        $foreignID = $query->getQueryParam('Foreign.ID');
        if ($foreignID) {
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
        foreach (array_keys($params ?? []) as $key) {
            if (stripos($key ?? '', 'Foreign.') === 0) {
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
    public function beforeGetFinalisedQuery(DataQuery $dataQuery, $queriedColumns, SQLSelect $sqlSelect)
    {
        // Get metadata and SQL from join table
        $hasManyRelation = $this->getParentRelationship($dataQuery);
        $joinTableSQLSelect = $hasManyRelation->dataQuery()->query();
        $joinTableSQL = $joinTableSQLSelect->sql($joinTableParameters);
        $joinTableColumns = array_keys($joinTableSQLSelect->getSelect() ?? []); // Get aliases (keys) only
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
        // By using a string placeholder $$_SUBQUERY_$$ we protect field/table rewrites from interfering twice
        // on the already-finalised inner list
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
    public function afterGetFinalisedQuery(DataQuery $dataQuery, $queriedColumns, SQLSelect $sqlQuery)
    {
        // Inject final replacement after manipulation has been performed on the base dataquery
        $joinTableSQL = $dataQuery->getQueryParam('Foreign.JoinTableSQL');
        if ($joinTableSQL) {
            $sqlQuery->replaceText('SELECT $$_SUBQUERY_$$', $joinTableSQL);
            $dataQuery->setQueryParam('Foreign.JoinTableSQL', null);
        }
    }

    /**
     * @return string
     */
    public function getForeignClass()
    {
        return $this->foreignClass;
    }

    /**
     * @param string $foreignClass
     * @return $this
     */
    public function setForeignClass($foreignClass)
    {
        $this->foreignClass = $foreignClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * @param string $parentClass
     * @return $this
     */
    public function setParentClass($parentClass)
    {
        $this->parentClass = $parentClass;
        return $this;
    }
}
