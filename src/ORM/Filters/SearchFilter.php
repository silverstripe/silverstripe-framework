<?php

namespace SilverStripe\ORM\Filters;

use BadMethodCallException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Base class for filtering implementations,
 * which work together with {@link SearchContext}
 * to create or amend a query for {@link DataObject} instances.
 * See {@link SearchContext} for more information.
 *
 * Each search filter must be registered in config as an "Injector" service with
 * the "DataListFilter." prefix. E.g.
 *
 * <code>
 * Injector:
 *   DataListFilter.EndsWith:
 *     class: EndsWithFilter
 * </code>
 */
abstract class SearchFilter
{
    use Injectable, Configurable;

    /**
     * Whether the database uses case sensitive collation or not.
     * @internal
     */
    private static ?bool $caseSensitiveByCollation = null;

    /**
     * Whether search filters should be case sensitive or not by default.
     * If null, the database collation setting is used.
     */
    private static ?bool $default_case_sensitive = null;

    /**
     * Classname of the inspected {@link DataObject}.
     * If pointing to a relation, this will be the classname of the leaf
     * class in the relation
     *
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $fullName;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var array
     */
    protected $modifiers;

    /**
     * @var array Parts of a has-one, has-many or many-many relation (not the classname).
     * Set in the constructor as part of the name in dot-notation, and used in
     * {@link applyRelation()}.
     *
     * Also used to build table prefix (see getRelationTablePrefix)
     */
    protected $relation = [];

    /**
     * An array of data about an aggregate column being used
     * ex:
     * [
     *  'function' => 'COUNT',
     *  'column' => 'ID'
     * ]
     * @var array
     */
    protected $aggregate;

    /**
     * @param string $fullName Determines the name of the field, as well as the searched database
     *  column. Can contain a relation name in dot notation, which will automatically join
     *  the necessary tables (e.g. "Comments.Name" to join the "Comments" has-many relationship and
     *  search the "Name" column when applying this filter to a SiteTree class).
     * @param mixed $value
     * @param array $modifiers
     */
    public function __construct($fullName = null, $value = false, array $modifiers = [])
    {
        $this->fullName = $fullName;

        // sets $this->name and $this->relation
        $this->addRelation($fullName);
        $this->addAggregate($fullName);
        $this->value = $value;
        $this->setModifiers($modifiers);
    }

    /**
     * Called by constructor to convert a string pathname into
     * a well defined relationship sequence.
     *
     * @param string $name
     */
    protected function addRelation($name)
    {
        if (strstr($name ?? '', '.')) {
            $parts = explode('.', $name ?? '');
            $this->name = array_pop($parts);
            $this->relation = $parts;
        } else {
            $this->name = $name;
        }
    }

    /**
     * Parses the name for any aggregate functions and stores them in the $aggregate array
     *
     * @param string $name
     */
    protected function addAggregate($name)
    {
        if (!$this->relation) {
            return;
        }

        if (!preg_match('/([A-Za-z]+)\(\s*(?:([A-Za-z_*][A-Za-z0-9_]*))?\s*\)$/', $name ?? '', $matches)) {
            if (stristr($name ?? '', '(') !== false) {
                throw new InvalidArgumentException(sprintf(
                    'Malformed aggregate filter %s',
                    $name
                ));
            }
            return;
        }

        $this->aggregate = [
            'function' => strtoupper($matches[1] ?? ''),
            'column' => isset($matches[2]) ? $matches[2] : null
        ];
    }

    /**
     * Set the root model class to be selected by this
     * search query.
     *
     * @param string|DataObject $className
     */
    public function setModel($className)
    {
        $this->model = ClassInfo::class_name($className);
    }

    /**
     * Set the current value(s) to be filtered on.
     *
     * @param string|array $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Accessor for the current value to be filtered on.
     *
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the current modifiers to apply to the filter
     *
     * @param array $modifiers
     */
    public function setModifiers(array $modifiers)
    {
        $modifiers = array_map('strtolower', $modifiers ?? []);

        // Validate modifiers are supported
        $allowed = $this->getSupportedModifiers();
        $unsupported = array_diff($modifiers ?? [], $allowed);
        if ($unsupported) {
            throw new InvalidArgumentException(
                static::class . ' does not accept ' . implode(', ', $unsupported) . ' as modifiers'
            );
        }

        $this->modifiers = $modifiers;
    }

    /**
     * Gets supported modifiers for this filter
     *
     * @return array
     */
    public function getSupportedModifiers()
    {
        // By default support 'not' as a modifier for all filters
        return ['not'];
    }

    /**
     * Accessor for the current modifiers to apply to the filter.
     *
     * @return array
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * The original name of the field.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * The full name passed to the constructor,
     * including any (optional) relations in dot notation.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param string $name
     */
    public function setFullName($name)
    {
        $this->fullName = $name;
    }

    /**
     * Normalizes the field name to table mapping.
     *
     * @return string
     */
    public function getDbName()
    {
        // Special handler for "NULL" relations
        if ($this->name === "NULL") {
            return $this->name;
        }

        // Ensure that we're dealing with a DataObject.
        if (!is_subclass_of($this->model, DataObject::class)) {
            throw new InvalidArgumentException(
                "Model supplied to " . static::class . " should be an instance of DataObject."
            );
        }
        $tablePrefix = DataQuery::applyRelationPrefix($this->relation);
        $schema = DataObject::getSchema();

        if ($this->aggregate) {
            $column = $this->aggregate['column'];
            $function = $this->aggregate['function'];

            $table = $column ?
                $schema->tableForField($this->model, $column) :
                $schema->baseDataTable($this->model);

            if (!$table) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid column %s for aggregate function %s on %s',
                    $column,
                    $function,
                    $this->model
                ));
            }
            return sprintf(
                '%s("%s%s".%s)',
                $function,
                $tablePrefix,
                $table,
                $column ? "\"$column\"" : '"ID"'
            );
        }


        // Check if this column is a table on the current model
        $table = $schema->tableForField($this->model, $this->name);
        if ($table) {
            return $schema->sqlColumnForField($this->model, $this->name, $tablePrefix);
        }

        // fallback to the provided name in the event of a joined column
        // name (as the candidate class doesn't check joined records)
        $parts = explode('.', $this->fullName ?? '');
        return '"' . implode('"."', $parts) . '"';
    }

    /**
     * Return the value of the field as processed by the DBField class
     *
     * @return string
     */
    public function getDbFormattedValue()
    {
        // SRM: This code finds the table where the field named $this->name lives

        if ($this->aggregate) {
            return intval($this->value);
        }

        /** @var DBField $dbField */
        $dbField = singleton($this->model)->dbObject($this->name);
        $dbField->setValue($this->value);
        return $dbField->RAW();
    }

    /**
     * Given an escaped HAVING clause, add it along with the appropriate GROUP BY clause
     * @param  DataQuery $query
     * @param  string    $having
     * @return DataQuery
     */
    public function applyAggregate(DataQuery $query, $having)
    {
        $schema = DataObject::getSchema();
        $baseTable = $schema->baseDataTable($query->dataClass());

        return $query
            ->having($having)
            ->groupby("\"{$baseTable}\".\"ID\"");
    }

    /**
     * Check whether this filter matches against a value.
     */
    public function matches(mixed $objectValue): bool
    {
        // We can't add an abstract method because that will mean custom subclasses would need to
        // implement this new method which makes it a breaking change - but we want to enforce the
        // method signature for any subclasses which do implement this - therefore, throw an
        // exception by default.
        $actualClass = get_class($this);
        throw new BadMethodCallException("matches is not implemented on $actualClass");
    }

    /**
     * Apply filter criteria to a SQL query.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        if (($key = array_search('not', $this->modifiers ?? [])) !== false) {
            unset($this->modifiers[$key]);
            return $this->exclude($query);
        }
        if (is_array($this->value)) {
            return $this->applyMany($query);
        } else {
            return $this->applyOne($query);
        }
    }

    /**
     * Apply filter criteria to a SQL query with a single value.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    abstract protected function applyOne(DataQuery $query);

    /**
     * Apply filter criteria to a SQL query with an array of values.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function applyMany(DataQuery $query)
    {
        throw new InvalidArgumentException(static::class . " can't be used to filter by a list of items.");
    }

    /**
     * Exclude filter criteria from a SQL query.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    public function exclude(DataQuery $query)
    {
        if (($key = array_search('not', $this->modifiers ?? [])) !== false) {
            unset($this->modifiers[$key]);
            return $this->apply($query);
        }
        if (is_array($this->value)) {
            return $this->excludeMany($query);
        } else {
            return $this->excludeOne($query);
        }
    }

    /**
     * Exclude filter criteria from a SQL query with a single value.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    abstract protected function excludeOne(DataQuery $query);

    /**
     * Exclude filter criteria from a SQL query with an array of values.
     *
     * @param DataQuery $query
     * @return DataQuery
     */
    protected function excludeMany(DataQuery $query)
    {
        throw new InvalidArgumentException(static::class . " can't be used to filter by a list of items.");
    }

    /**
     * Determines if a field has a value,
     * and that the filter should be applied.
     * Relies on the field being populated with
     * {@link setValue()}
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return false;
    }

    /**
     * Determines case sensitivity based on {@link getModifiers()}.
     *
     * @return ?bool TRUE or FALSE to enforce sensitivity, NULL to use field collation.
     */
    protected function getCaseSensitive()
    {
        $modifiers = $this->getModifiers();
        if (in_array('case', $modifiers ?? [])) {
            return true;
        } elseif (in_array('nocase', $modifiers ?? [])) {
            return false;
        } else {
            $sensitive = SearchFilter::config()->get('default_case_sensitive');
            if ($sensitive !== null) {
                return $sensitive;
            }
        }
        return null;
    }

    /**
     * Find out whether the database is set to use case sensitive comparisons or not by default.
     * Used for static comparisons in the matches() method.
     */
    protected function getCaseSensitiveByCollation(): ?bool
    {
        if (!SearchFilter::$caseSensitiveByCollation) {
            if (!DB::is_active()) {
                return null;
            }
            SearchFilter::$caseSensitiveByCollation = DB::query("SELECT 'CASE' = 'case'")->record() === 0;
        }

        return SearchFilter::$caseSensitiveByCollation;
    }
}
