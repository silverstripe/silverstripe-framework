<?php


namespace SilverStripe\ORM;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
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
     * @param string $joinClass Class name of the joined dataobject record
     * @param string $localKey The key in the join table that maps to the dataClass' PK.
     * @param string $foreignKey The key in the join table that maps to joined class' PK.
     * @param string $foreignClass the 'from' class name
     * @param string $parentClass Name of parent class. Subclass of $foreignClass
     */
    public function __construct(string $joinClass, string $localKey, string $foreignKey, string $foreignClass = null, string $parentClass = null): void
    {
        $this->setJoinClass($joinClass);
        $this->setLocalKey($localKey);
        $this->setForeignKey($foreignKey);
        if ($foreignClass) {
            $this->setForeignClass($foreignClass);
        } else {
            Deprecation::notice('5.0', 'Arg $foreignClass will be mandatory in 5.x');
        }
        if ($parentClass) {
            $this->setParentClass($parentClass);
        } else {
            Deprecation::notice('5.0', 'Arg $parentClass will be mandatory in 5.x');
        }
    }

    /**
     * @return string
     */
    public function getJoinClass(): string
    {
        return $this->joinClass;
    }

    /**
     * @param mixed $joinClass
     * @return $this
     */
    public function setJoinClass(string $joinClass): SilverStripe\ORM\ManyManyThroughQueryManipulator
    {
        $this->joinClass = $joinClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * @param string $localKey
     * @return $this
     */
    public function setLocalKey(string $localKey): SilverStripe\ORM\ManyManyThroughQueryManipulator
    {
        $this->localKey = $localKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Gets ID key name for foreign key component
     *
     * @return string
     */
    public function getForeignIDKey(): string
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
    public function setForeignKey(string $foreignKey): SilverStripe\ORM\ManyManyThroughQueryManipulator
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
    public function getParentRelationship(DataQuery $query): SilverStripe\ORM\PolymorphicHasManyList
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
    public function extractInheritableQueryParameters(DataQuery $query): array
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
    public function getJoinAlias(): string
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
    public function beforeGetFinalisedQuery(DataQuery $dataQuery, array $queriedColumns, SQLSelect $sqlSelect): void
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
    public function afterGetFinalisedQuery(DataQuery $dataQuery, array $queriedColumns, SQLSelect $sqlQuery): void
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
    public function getForeignClass(): string
    {
        return $this->foreignClass;
    }

    /**
     * @param string $foreignClass
     * @return $this
     */
    public function setForeignClass(string $foreignClass): SilverStripe\ORM\ManyManyThroughQueryManipulator
    {
        $this->foreignClass = $foreignClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentClass(): string
    {
        return $this->parentClass;
    }

    /**
     * @param string $parentClass
     * @return ManyManyThroughQueryManipulator
     */
    public function setParentClass(string $parentClass): SilverStripe\ORM\ManyManyThroughQueryManipulator
    {
        $this->parentClass = $parentClass;
        return $this;
    }
}
