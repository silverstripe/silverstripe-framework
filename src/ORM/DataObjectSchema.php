<?php

namespace SilverStripe\ORM;

use Exception;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\DBSchemaManager;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Provides dataobject and database schema mapping functionality
 */
class DataObjectSchema
{
    use Injectable;
    use Configurable;

    /**
     * Configuration key for has_one relations that can support multiple reciprocal has_many relations.
     */
    public const HAS_ONE_MULTI_RELATIONAL = 'multirelational';

    /**
     * Default separate for table namespaces. Can be set to any string for
     * databases that do not support some characters.
     *
     * @config
     * @var string
     */
    private static $table_namespace_separator = '_';

    /**
     * Cache of database fields
     *
     * @var array
     */
    protected $databaseFields = [];

    /**
     * Cache of database indexes
     *
     * @var array
     */
    protected $databaseIndexes = [];

    /**
     * Fields that should be indexed, by class name
     *
     * @var array
     */
    protected $defaultDatabaseIndexes = [];

    /**
     * Cache of composite database field
     *
     * @var array
     */
    protected $compositeFields = [];

    /**
     * Cache of table names
     *
     * @var array
     */
    protected $tableNames = [];

    /**
     * Clear cached table names
     */
    public function reset()
    {
        $this->tableNames = [];
        $this->databaseFields = [];
        $this->databaseIndexes = [];
        $this->defaultDatabaseIndexes = [];
        $this->compositeFields = [];
    }

    /**
     * Get all table names
     *
     * @return array
     */
    public function getTableNames()
    {
        $this->cacheTableNames();
        return $this->tableNames;
    }

    /**
     * Given a DataObject class and a field on that class, determine the appropriate SQL for
     * selecting / filtering on in a SQL string. Note that $class must be a valid class, not an
     * arbitrary table.
     *
     * The result will be a standard ANSI-sql quoted string in "Table"."Column" format.
     *
     * @param string $class Class name (not a table).
     * @param string $field Name of field that belongs to this class (or a parent class)
     * @param string $tablePrefix Optional prefix for table (alias)
     *
     * @return string The SQL identifier string for the corresponding column for this field
     */
    public function sqlColumnForField($class, $field, $tablePrefix = null)
    {
        $table = $this->tableForField($class, $field);
        if (!$table) {
            throw new InvalidArgumentException("\"{$field}\" is not a field on class \"{$class}\"");
        }
        return "\"{$tablePrefix}{$table}\".\"{$field}\"";
    }

    /**
     * Get table name for the given class.
     *
     * Note that this does not confirm a table actually exists (or should exist), but returns
     * the name that would be used if this table did exist.
     *
     * @param string $class
     *
     * @return string Returns the table name, or null if there is no table
     */
    public function tableName($class)
    {
        $tables = $this->getTableNames();
        $class = ClassInfo::class_name($class);
        if (isset($tables[$class])) {
            return Convert::raw2sql($tables[$class]);
        }
        return null;
    }

    /**
     * Returns the root class (the first to extend from DataObject) for the
     * passed class.
     *
     * @param string|object $class
     *
     * @return class-string<DataObject>
     * @throws InvalidArgumentException
     */
    public function baseDataClass($class)
    {
        $current = $class;
        while ($next = get_parent_class($current ?? '')) {
            if ($next === DataObject::class) {
                // Only use ClassInfo::class_name() to format the class if we've not used get_parent_class()
                return ($current === $class) ? ClassInfo::class_name($current) : $current;
            }
            $current = $next;
        }
        throw new InvalidArgumentException("$class is not a subclass of DataObject");
    }

    /**
     * Get the base table
     *
     * @param string|object $class
     *
     * @return string
     */
    public function baseDataTable($class)
    {
        return $this->tableName($this->baseDataClass($class));
    }

    /**
     * fieldSpec should exclude virtual fields (such as composite fields), and only include fields with a db column.
     */
    const DB_ONLY = 1;

    /**
     * fieldSpec should only return fields that belong to this table, and not any ancestors
     */
    const UNINHERITED = 2;

    /**
     * fieldSpec should prefix all field specifications with the class name in RecordClass.Column(spec) format.
     */
    const INCLUDE_CLASS = 4;

    /**
     * Get all DB field specifications for a class, including ancestors and composite fields.
     *
     * @param string|DataObject $classOrInstance
     * @param int $options Bitmask of options
     *  - UNINHERITED Limit to only this table
     *  - DB_ONLY Exclude virtual fields (such as composite fields), and only include fields with a db column.
     *  - INCLUDE_CLASS Prefix the field specification with the class name in RecordClass.Column(spec) format.
     *
     * @return array List of fields, where the key is the field name and the value is the field specification.
     */
    public function fieldSpecs($classOrInstance, $options = 0)
    {
        $class = ClassInfo::class_name($classOrInstance);

        // Validate options
        if (!is_int($options)) {
            throw new InvalidArgumentException("Invalid options " . var_export($options, true));
        }
        $uninherited = ($options & DataObjectSchema::UNINHERITED) === DataObjectSchema::UNINHERITED;
        $dbOnly = ($options & DataObjectSchema::DB_ONLY) === DataObjectSchema::DB_ONLY;
        $includeClass = ($options & DataObjectSchema::INCLUDE_CLASS) === DataObjectSchema::INCLUDE_CLASS;

        // Walk class hierarchy
        $db = [];
        $classes = $uninherited ? [$class] : ClassInfo::ancestry($class);
        foreach ($classes as $tableClass) {
            // Skip irrelevant parent classes
            if (!is_subclass_of($tableClass, DataObject::class)) {
                continue;
            }

            // Find all fields on this class
            $fields = $this->databaseFields($tableClass, false);
            // Merge with composite fields
            if (!$dbOnly) {
                $compositeFields = $this->compositeFields($tableClass, false);
                $fields = array_merge($fields, $compositeFields);
            }

            // Record specification
            foreach ($fields as $name => $specification) {
                $prefix = $includeClass ? "{$tableClass}." : "";
                $db[$name] = $prefix . $specification;
            }
        }
        return $db;
    }


    /**
     * Get specifications for a single class field
     *
     * @param string|DataObject $classOrInstance Name or instance of class
     * @param string $fieldName Name of field to retrieve
     * @param int $options Bitmask of options
     *  - UNINHERITED Limit to only this table
     *  - DB_ONLY Exclude virtual fields (such as composite fields), and only include fields with a db column.
     *  - INCLUDE_CLASS Prefix the field specification with the class name in RecordClass.Column(spec) format.
     *
     * @return string|null Field will be a string in FieldClass(args) format, or
     * RecordClass.FieldClass(args) format if using INCLUDE_CLASS. Will be null if no field is found.
     */
    public function fieldSpec($classOrInstance, $fieldName, $options = 0)
    {
        $specs = $this->fieldSpecs($classOrInstance, $options);
        return isset($specs[$fieldName]) ? $specs[$fieldName] : null;
    }

    /**
     * Find the class for the given table
     *
     * @param string $table
     *
     * @return class-string<DataObject>|null The FQN of the class, or null if not found
     */
    public function tableClass($table)
    {
        $tables = $this->getTableNames();
        $class = array_search($table, $tables ?? [], true);
        if ($class) {
            return $class;
        }

        // If there is no class for this table, strip table modifiers (e.g. _Live / _Versions)
        // from the end and re-attempt a search.
        if (preg_match('/^(?<class>.+)(_[^_]+)$/i', $table ?? '', $matches)) {
            $table = $matches['class'];
            $class = array_search($table, $tables ?? [], true);
            if ($class) {
                return $class;
            }
        }
        return null;
    }

    /**
     * Cache all table names if necessary
     */
    protected function cacheTableNames()
    {
        if ($this->tableNames) {
            return;
        }
        $this->tableNames = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $class) {
            if ($class === DataObject::class) {
                continue;
            }
            $table = $this->buildTableName($class);

            // Check for conflicts
            $conflict = array_search($table, $this->tableNames ?? [], true);
            if ($conflict) {
                throw new LogicException(
                    "Multiple classes (\"{$class}\", \"{$conflict}\") map to the same table: \"{$table}\""
                );
            }
            $this->tableNames[$class] = $table;
        }
    }

    /**
     * Generate table name for a class.
     *
     * Note: some DB schema have a hard limit on table name length. This is not enforced by this method.
     * See dev/build errors for details in case of table name violation.
     *
     * @param string $class
     *
     * @return string
     */
    protected function buildTableName($class)
    {
        $table = Config::inst()->get($class, 'table_name', Config::UNINHERITED);

        // Generate default table name
        if ($table) {
            return $table;
        }

        if (strpos($class ?? '', '\\') === false) {
            return $class;
        }

        $separator = DataObjectSchema::config()->uninherited('table_namespace_separator');
        $table = str_replace('\\', $separator ?? '', trim($class ?? '', '\\'));

        if (!ClassInfo::classImplements($class, TestOnly::class) && $this->classHasTable($class)) {
            DBSchemaManager::showTableNameWarning($table, $class);
        }

        return $table;
    }

    /**
     * Return the complete map of fields to specification on this object, including fixed_fields.
     * "ID" will be included on every table.
     *
     * @param string $class Class name to query from
     * @param bool $aggregated Include fields in entire hierarchy, rather than just on this table
     *
     * @return array Map of fieldname to specification, similar to {@link DataObject::$db}.
     */
    public function databaseFields($class, $aggregated = true)
    {
        $class = ClassInfo::class_name($class);
        if ($class === DataObject::class) {
            return [];
        }
        $this->cacheDatabaseFields($class);
        $fields = $this->databaseFields[$class];

        if (!$aggregated) {
            return $fields;
        }

        // Recursively merge
        $parentFields = $this->databaseFields(get_parent_class($class ?? ''));
        return array_merge($fields, array_diff_key($parentFields ?? [], $fields));
    }

    /**
     * Gets a single database field.
     *
     * @param string $class Class name to query from
     * @param string $field Field name
     * @param bool $aggregated Include fields in entire hierarchy, rather than just on this table
     *
     * @return string|null Field specification, or null if not a field
     */
    public function databaseField($class, $field, $aggregated = true)
    {
        $fields = $this->databaseFields($class, $aggregated);
        return isset($fields[$field]) ? $fields[$field] : null;
    }

    /**
     * @param string $class
     * @param bool $aggregated
     *
     * @return array
     */
    public function databaseIndexes($class, $aggregated = true)
    {
        $class = ClassInfo::class_name($class);
        if ($class === DataObject::class) {
            return [];
        }
        $this->cacheDatabaseIndexes($class);
        $indexes = $this->databaseIndexes[$class];
        if (!$aggregated) {
            return $indexes;
        }
        return array_merge($indexes, $this->databaseIndexes(get_parent_class($class ?? '')));
    }

    /**
     * Check if the given class has a table
     *
     * @param string $class
     *
     * @return bool
     */
    public function classHasTable($class)
    {
        if (!is_subclass_of($class, DataObject::class)) {
            return false;
        }

        $fields = $this->databaseFields($class, false);
        return !empty($fields);
    }

    /**
     * Returns a list of all the composite if the given db field on the class is a composite field.
     * Will check all applicable ancestor classes and aggregate results.
     *
     * Can be called directly on an object. E.g. Member::composite_fields(), or Member::composite_fields(null, true)
     * to aggregate.
     *
     * Includes composite has_one (Polymorphic) fields
     *
     * @param string $class Name of class to check
     * @param bool $aggregated Include fields in entire hierarchy, rather than just on this table
     *
     * @return array List of composite fields and their class spec
     */
    public function compositeFields($class, $aggregated = true)
    {
        $class = ClassInfo::class_name($class);
        if ($class === DataObject::class) {
            return [];
        }
        $this->cacheDatabaseFields($class);

        // Get fields for this class
        $compositeFields = $this->compositeFields[$class];
        if (!$aggregated) {
            return $compositeFields;
        }

        // Recursively merge
        $parentFields = $this->compositeFields(get_parent_class($class ?? ''));
        return array_merge($compositeFields, array_diff_key($parentFields ?? [], $compositeFields));
    }

    /**
     * Get a composite field for a class
     *
     * @param string $class Class name to query from
     * @param string $field Field name
     * @param bool $aggregated Include fields in entire hierarchy, rather than just on this table
     *
     * @return string|null Field specification, or null if not a field
     */
    public function compositeField($class, $field, $aggregated = true)
    {
        $fields = $this->compositeFields($class, $aggregated);
        return isset($fields[$field]) ? $fields[$field] : null;
    }

    /**
     * Cache all database and composite fields for the given class.
     * Will do nothing if already cached
     *
     * @param string $class Class name to cache
     */
    protected function cacheDatabaseFields($class)
    {
        // Skip if already cached
        if (isset($this->databaseFields[$class]) && isset($this->compositeFields[$class])) {
            return;
        }
        $compositeFields = [];
        $dbFields = [];

        // Ensure fixed fields appear at the start
        $fixedFields = DataObject::config()->uninherited('fixed_fields');
        if (get_parent_class($class ?? '') === DataObject::class) {
            // Merge fixed with ClassName spec and custom db fields
            $dbFields = $fixedFields;
        } else {
            $dbFields['ID'] = $fixedFields['ID'];
        }

        // Check each DB value as either a field or composite field
        $db = Config::inst()->get($class, 'db', Config::UNINHERITED) ?: [];
        foreach ($db as $fieldName => $fieldSpec) {
            $fieldClass = strtok($fieldSpec ?? '', '(');
            if (singleton($fieldClass) instanceof DBComposite) {
                $compositeFields[$fieldName] = $fieldSpec;
            } else {
                $dbFields[$fieldName] = $fieldSpec;
            }
        }

        // Add in all has_ones
        $hasOne = Config::inst()->get($class, 'has_one', Config::UNINHERITED) ?: [];
        foreach ($hasOne as $fieldName => $spec) {
            if (is_array($spec)) {
                if (!isset($spec['class'])) {
                    throw new LogicException("has_one relation {$class}.{$fieldName} must declare a class");
                }
                // Handle has_one which handles multiple reciprocal has_many relations
                $hasOneClass = $spec['class'];
                if (($spec[DataObjectSchema::HAS_ONE_MULTI_RELATIONAL] ?? false) === true) {
                    $compositeFields[$fieldName] = 'PolymorphicRelationAwareForeignKey';
                    continue;
                }
            } else {
                $hasOneClass = $spec;
            }
            if ($hasOneClass === DataObject::class) {
                $compositeFields[$fieldName] = 'PolymorphicForeignKey';
            } else {
                $dbFields["{$fieldName}ID"] = 'ForeignKey';
            }
        }

        // Merge composite fields into DB
        foreach ($compositeFields as $fieldName => $fieldSpec) {
            $fieldObj = Injector::inst()->create($fieldSpec, $fieldName);
            $fieldObj->setTable($class);
            $nestedFields = $fieldObj->compositeDatabaseFields();
            foreach ($nestedFields as $nestedName => $nestedSpec) {
                $dbFields["{$fieldName}{$nestedName}"] = $nestedSpec;
            }
        }

        // Prevent field-less tables with only 'ID'
        if (count($dbFields ?? []) < 2) {
            $dbFields = [];
        }

        // Return cached results
        $this->databaseFields[$class] = $dbFields;
        $this->compositeFields[$class] = $compositeFields;
    }

    /**
     * Cache all indexes for the given class. Will do nothing if already cached.
     *
     * @param $class
     */
    protected function cacheDatabaseIndexes($class)
    {
        if (!array_key_exists($class, $this->databaseIndexes ?? [])) {
            $this->databaseIndexes[$class] = array_merge(
                $this->buildSortDatabaseIndexes($class),
                $this->cacheDefaultDatabaseIndexes($class),
                $this->buildCustomDatabaseIndexes($class)
            );
        }
    }

    /**
     * Get "default" database indexable field types
     *
     * @param  string $class
     *
     * @return array
     */
    protected function cacheDefaultDatabaseIndexes($class)
    {
        if (array_key_exists($class, $this->defaultDatabaseIndexes ?? [])) {
            return $this->defaultDatabaseIndexes[$class];
        }
        $this->defaultDatabaseIndexes[$class] = [];

        $fieldSpecs = $this->fieldSpecs($class, DataObjectSchema::UNINHERITED);
        foreach ($fieldSpecs as $field => $spec) {
            /** @var DBField $fieldObj */
            $fieldObj = Injector::inst()->create($spec, $field);
            if ($indexSpecs = $fieldObj->getIndexSpecs()) {
                $this->defaultDatabaseIndexes[$class][$field] = $indexSpecs;
            }
        }
        return $this->defaultDatabaseIndexes[$class];
    }

    /**
     * Look for custom indexes declared on the class
     *
     * @param  string $class
     *
     * @return array
     * @throws InvalidArgumentException If an index already exists on the class
     * @throws InvalidArgumentException If a custom index format is not valid
     */
    protected function buildCustomDatabaseIndexes($class)
    {
        $indexes = [];
        $classIndexes = Config::inst()->get($class, 'indexes', Config::UNINHERITED) ?: [];
        foreach ($classIndexes as $indexName => $indexSpec) {
            if (array_key_exists($indexName, $indexes ?? [])) {
                throw new InvalidArgumentException(sprintf(
                    'Index named "%s" already exists on class %s',
                    $indexName,
                    $class
                ));
            }
            if (is_array($indexSpec)) {
                if (!ArrayLib::is_associative($indexSpec)) {
                    $indexSpec = [
                        'columns' => $indexSpec,
                    ];
                }
                if (!isset($indexSpec['type'])) {
                    $indexSpec['type'] = 'index';
                }
                if (!isset($indexSpec['columns'])) {
                    $indexSpec['columns'] = [$indexName];
                } elseif (!is_array($indexSpec['columns'])) {
                    throw new InvalidArgumentException(sprintf(
                        'Index %s on %s is not valid. columns should be an array %s given',
                        var_export($indexName, true),
                        var_export($class, true),
                        var_export($indexSpec['columns'], true)
                    ));
                }
            } else {
                $indexSpec = [
                    'type' => 'index',
                    'columns' => [$indexName],
                ];
            }
            $indexes[$indexName] = $indexSpec;
        }
        return $indexes;
    }

    protected function buildSortDatabaseIndexes($class)
    {
        $sort = Config::inst()->get($class, 'default_sort', Config::UNINHERITED);
        $indexes = [];

        if ($sort && is_string($sort)) {
            $sort = preg_split('/,(?![^()]*+\\))/', $sort ?? '');
            foreach ($sort as $value) {
                try {
                    list ($table, $column) = $this->parseSortColumn(trim($value ?? ''));
                    $table = trim($table ?? '', '"');
                    $column = trim($column ?? '', '"');
                    if ($table && strtolower($table ?? '') !== strtolower(DataObjectSchema::tableName($class) ?? '')) {
                        continue;
                    }
                    if ($this->databaseField($class, $column, false)) {
                        $indexes[$column] = [
                            'type' => 'index',
                            'columns' => [$column],
                        ];
                    }
                } catch (InvalidArgumentException $e) {
                }
            }
        }
        return $indexes;
    }

    /**
     * Parses a specified column into a sort field and direction
     *
     * @param string $column String to parse containing the column name
     *
     * @return array Resolved table and column.
     */
    protected function parseSortColumn($column)
    {
        // Parse column specification, considering possible ansi sql quoting
        // Note that table prefix is allowed, but discarded
        if (preg_match('/^("?(?<table>[^"\s]+)"?\\.)?"?(?<column>[^"\s]+)"?(\s+(?<direction>((asc)|(desc))(ending)?))?$/i', $column ?? '', $match)) {
            $table = $match['table'];
            $column = $match['column'];
        } else {
            throw new InvalidArgumentException("Invalid sort() column");
        }
        return [$table, $column];
    }

    /**
     * Returns the table name in the class hierarchy which contains a given
     * field column for a {@link DataObject}. If the field does not exist, this
     * will return null.
     *
     * @param string $candidateClass
     * @param string $fieldName
     *
     * @return string
     */
    public function tableForField($candidateClass, $fieldName)
    {
        $class = $this->classForField($candidateClass, $fieldName);
        if ($class) {
            return $this->tableName($class);
        }
        return null;
    }

    /**
     * Returns the class name in the class hierarchy which contains a given
     * field column for a {@link DataObject}. If the field does not exist, this
     * will return null.
     *
     * @param string $candidateClass
     * @param string $fieldName
     *
     * @return class-string<DataObject>|null
     */
    public function classForField($candidateClass, $fieldName)
    {
        // normalise class name
        $candidateClass = ClassInfo::class_name($candidateClass);
        if ($candidateClass === DataObject::class) {
            return null;
        }

        // Short circuit for fixed fields
        $fixed = DataObject::config()->uninherited('fixed_fields');
        if (isset($fixed[$fieldName])) {
            return $this->baseDataClass($candidateClass);
        }

        // Find regular field
        while ($candidateClass && $candidateClass !== DataObject::class) {
            $fields = $this->databaseFields($candidateClass, false);
            if (isset($fields[$fieldName])) {
                return $candidateClass;
            }
            $candidateClass = get_parent_class($candidateClass ?? '');
        }
        return null;
    }

    /**
     * Return information about a specific many_many component. Returns a numeric array.
     * The first item in the array will be the class name of the relation.
     *
     * Standard many_many return type is:
     *
     * [
     *  <manyManyClass>,        Name of class for relation. E.g. "Categories"
     *  <classname>,            The class that relation is defined in e.g. "Product"
     *  <candidateName>,        The target class of the relation e.g. "Category"
     *  <parentField>,          The field name pointing to <classname>'s table e.g. "ProductID".
     *  <childField>,           The field name pointing to <candidatename>'s table e.g. "CategoryID".
     *  <joinTableOrRelation>   The join table between the two classes e.g. "Product_Categories".
     *                          If the class name is 'ManyManyThroughList' then this is the name of the
     *                          has_many relation.
     * ]
     *
     * @param string $class Name of class to get component for
     * @param string $component The component name
     *
     * @return array|null
     */
    public function manyManyComponent($class, $component)
    {
        $classes = ClassInfo::ancestry($class);
        foreach ($classes as $parentClass) {
            // Check if the component is defined in many_many on this class
            $otherManyMany = Config::inst()->get($parentClass, 'many_many', Config::UNINHERITED);
            if (isset($otherManyMany[$component])) {
                return $this->parseManyManyComponent($parentClass, $component, $otherManyMany[$component]);
            }

            // Check if the component is defined in belongs_many_many on this class
            $belongsManyMany = Config::inst()->get($parentClass, 'belongs_many_many', Config::UNINHERITED);
            if (!isset($belongsManyMany[$component])) {
                continue;
            }

            // Extract class and relation name from dot-notation
            $belongs = $this->parseBelongsManyManyComponent(
                $parentClass,
                $component,
                $belongsManyMany[$component]
            );

            // Build inverse relationship from other many_many, and swap parent/child
            $otherManyMany = $this->manyManyComponent($belongs['childClass'], $belongs['relationName']);
            return [
                'relationClass' => $otherManyMany['relationClass'],
                'parentClass' => $otherManyMany['childClass'],
                'childClass' => $otherManyMany['parentClass'],
                'parentField' => $otherManyMany['childField'],
                'childField' => $otherManyMany['parentField'],
                'join' => $otherManyMany['join'],
            ];
        }
        return null;
    }


    /**
     * Parse a belongs_many_many component to extract class and relationship name
     *
     * @param string $parentClass Name of class
     * @param string $component Name of relation on class
     * @param string $specification specification for this belongs_many_many
     *
     * @return array Array with child class and relation name
     */
    protected function parseBelongsManyManyComponent($parentClass, $component, $specification)
    {
        $childClass = $specification;
        $relationName = null;
        if (strpos($specification ?? '', '.') !== false) {
            list($childClass, $relationName) = explode('.', $specification ?? '', 2);
        }

        // Check child class exists
        if (!class_exists($childClass ?? '')) {
            throw new LogicException(
                "belongs_many_many relation {$parentClass}.{$component} points to "
                . "{$childClass} which does not exist"
            );
        }

        // We need to find the inverse component name, if not explicitly given
        if (!$relationName) {
            $relationName = $this->getManyManyInverseRelationship($childClass, $parentClass);
        }

        // Check valid relation found
        if (!$relationName) {
            throw new LogicException(
                "belongs_many_many relation {$parentClass}.{$component} points to "
                . "{$specification} without matching many_many"
            );
        }

        // Return relatios
        return [
            'childClass' => $childClass,
            'relationName' => $relationName,
        ];
    }

    /**
     * Return the many-to-many extra fields specification for a specific component.
     *
     * @param string $class
     * @param string $component
     *
     * @return array|null
     */
    public function manyManyExtraFieldsForComponent($class, $component)
    {
        // Get directly declared many_many_extraFields
        $extraFields = Config::inst()->get($class, 'many_many_extraFields');
        if (isset($extraFields[$component])) {
            return $extraFields[$component];
        }

        // If not belongs_many_many then there are no components
        while ($class && ($class !== DataObject::class)) {
            $belongsManyMany = Config::inst()->get($class, 'belongs_many_many', Config::UNINHERITED);
            if (isset($belongsManyMany[$component])) {
                // Reverse relationship and find extrafields from child class
                $belongs = $this->parseBelongsManyManyComponent(
                    $class,
                    $component,
                    $belongsManyMany[$component]
                );
                return $this->manyManyExtraFieldsForComponent($belongs['childClass'], $belongs['relationName']);
            }
            $class = get_parent_class($class ?? '');
        }
        return null;
    }

    /**
     * Return data for a specific has_many component.
     *
     * @param string $class Parent class
     * @param string $component
     * @param bool $classOnly If this is TRUE, than any has_many relationships in the form
     * "ClassName.Field" will have the field data stripped off. It defaults to TRUE.
     *
     * @return string|null
     */
    public function hasManyComponent($class, $component, $classOnly = true)
    {
        $hasMany = (array)Config::inst()->get($class, 'has_many');
        if (!isset($hasMany[$component])) {
            return null;
        }

        // Remove has_one specifier if given
        $hasMany = $hasMany[$component];
        $hasManyClass = strtok($hasMany ?? '', '.');

        // Validate
        $this->checkRelationClass($class, $component, $hasManyClass, 'has_many');
        return $classOnly ? $hasManyClass : $hasMany;
    }

    /**
     * Return data for a specific has_one component.
     *
     * @param string $class
     * @param string $component
     *
     * @return string|null
     */
    public function hasOneComponent($class, $component)
    {
        $hasOnes = Config::forClass($class)->get('has_one');
        if (!isset($hasOnes[$component])) {
            return null;
        }

        $spec = $hasOnes[$component];

        // Validate
        if (is_array($spec)) {
            $this->checkHasOneArraySpec($class, $component, $spec);
        }
        $relationClass = is_array($spec) ? $spec['class'] : $spec;
        $this->checkRelationClass($class, $component, $relationClass, 'has_one');

        return $relationClass;
    }

    /**
     * Check if a has_one relation handles multiple reciprocal has_many relations.
     *
     * @return bool True if the relation exists and handles multiple reciprocal has_many relations.
     */
    public function hasOneComponentHandlesMultipleRelations(string $class, string $component): bool
    {
        $hasOnes = Config::forClass($class)->get('has_one');
        if (!isset($hasOnes[$component])) {
            return false;
        }

        $spec = $hasOnes[$component];
        return ($spec[DataObjectSchema::HAS_ONE_MULTI_RELATIONAL] ?? false) === true;
    }

    /**
     * Return data for a specific belongs_to component.
     *
     * @param string $class
     * @param string $component
     * @param bool $classOnly If this is TRUE, than any has_many relationships in the
     * form "ClassName.Field" will have the field data stripped off. It defaults to TRUE.
     *
     * @return string|null
     */
    public function belongsToComponent($class, $component, $classOnly = true)
    {
        $belongsTo = (array)Config::forClass($class)->get('belongs_to');
        if (!isset($belongsTo[$component])) {
            return null;
        }

        // Remove has_one specifier if given
        $belongsTo = $belongsTo[$component];
        $belongsToClass = strtok($belongsTo ?? '', '.');

        // Validate
        $this->checkRelationClass($class, $component, $belongsToClass, 'belongs_to');
        return $classOnly ? $belongsToClass : $belongsTo;
    }

    /**
     * Check class for any unary component
     *
     * Alias for hasOneComponent() ?: belongsToComponent()
     *
     * @param string $class
     * @param string $component
     *
     * @return string|null
     */
    public function unaryComponent($class, $component)
    {
        return $this->hasOneComponent($class, $component) ?: $this->belongsToComponent($class, $component);
    }

    /**
     *
     * @param string $parentClass Parent class name
     * @param string $component ManyMany name
     * @param string|array $specification Declaration of many_many relation type
     *
     * @return array
     */
    protected function parseManyManyComponent($parentClass, $component, $specification)
    {
        // Check if this is many_many_through
        if (is_array($specification)) {
            // Validate join, parent and child classes
            $joinClass = $this->checkManyManyJoinClass($parentClass, $component, $specification);
            $parentClass = $this->checkManyManyFieldClass($parentClass, $component, $joinClass, $specification, 'from');
            $joinChildClass = $this->checkManyManyFieldClass($parentClass, $component, $joinClass, $specification, 'to');
            return [
                'relationClass' => ManyManyThroughList::class,
                'parentClass' => $parentClass,
                'childClass' => $joinChildClass,
                /** @internal Polymorphic many_many is experimental */
                'parentField' => $specification['from'] . ($parentClass === DataObject::class ? '' : 'ID'),
                'childField' => $specification['to'] . 'ID',
                'join' => $joinClass,
            ];
        }

        // Validate $specification class is valid
        $this->checkRelationClass($parentClass, $component, $specification, 'many_many');

        // automatic scaffolded many_many table
        $classTable = $this->tableName($parentClass);
        $parentField = "{$classTable}ID";
        if ($parentClass === $specification) {
            $childField = "ChildID";
        } else {
            $candidateTable = $this->tableName($specification);
            $childField = "{$candidateTable}ID";
        }
        $joinTable = "{$classTable}_{$component}";
        return [
            'relationClass' => ManyManyList::class,
            'parentClass' => $parentClass,
            'childClass' => $specification,
            'parentField' => $parentField,
            'childField' => $childField,
            'join' => $joinTable,
        ];
    }

    /**
     * Find a many_many on the child class that points back to this many_many
     *
     * @param string $childClass
     * @param string $parentClass
     *
     * @return string|null
     */
    protected function getManyManyInverseRelationship($childClass, $parentClass)
    {
        $otherManyMany = Config::inst()->get($childClass, 'many_many', Config::UNINHERITED);
        if (!$otherManyMany) {
            return null;
        }
        foreach ($otherManyMany as $inverseComponentName => $manyManySpec) {
            // Normal many-many
            if ($manyManySpec === $parentClass) {
                return $inverseComponentName;
            }
            // many-many through, inspect 'to' for the many_many
            if (is_array($manyManySpec)) {
                $toClass = $this->hasOneComponent($manyManySpec['through'], $manyManySpec['to']);
                if ($toClass === $parentClass) {
                    return $inverseComponentName;
                }
            }
        }
        return null;
    }

    /**
     * Tries to find the database key on another object that is used to store a
     * relationship to this class. If no join field can be found it defaults to 'ParentID'.
     *
     * If the remote field is polymorphic then $polymorphic is set to true, and the return value
     * is in the form 'Relation' instead of 'RelationID', referencing the composite DBField.
     *
     * @param string $class
     * @param string $component Name of the relation on the current object pointing to the
     * remote object.
     * @param string $type the join type - either 'has_many' or 'belongs_to'
     * @param boolean $polymorphic Flag set to true if the remote join field is polymorphic.
     *
     * @return string
     * @throws Exception
     */
    public function getRemoteJoinField($class, $component, $type = 'has_many', &$polymorphic = false)
    {
        return $this->getBelongsToAndHasManyDetails($class, $component, $type, $polymorphic)['joinField'];
    }

    public function getHasManyComponentDetails(string $class, string $component): array
    {
        return $this->getBelongsToAndHasManyDetails($class, $component);
    }

    private function getBelongsToAndHasManyDetails(
        string $class,
        string $component,
        string $type = 'has_many',
        &$polymorphic = false
    ): array {
        // Extract relation from current object
        if ($type === 'has_many') {
            $remoteClass = $this->hasManyComponent($class, $component, false);
        } else {
            $remoteClass = $this->belongsToComponent($class, $component, false);
        }

        if (empty($remoteClass)) {
            throw new Exception("Unknown $type component '$component' on class '$class'");
        }
        if (!ClassInfo::exists(strtok($remoteClass ?? '', '.'))) {
            throw new Exception(
                "Class '$remoteClass' not found, but used in $type component '$component' on class '$class'"
            );
        }

        // If presented with an explicit field name (using dot notation) then extract field name
        $remoteField = null;
        if (strpos($remoteClass ?? '', '.') !== false) {
            list($remoteClass, $remoteField) = explode('.', $remoteClass ?? '');
        }

        // Reference remote has_one to check against
        $remoteRelations = Config::inst()->get($remoteClass, 'has_one');
        foreach ($remoteRelations as $key => $value) {
            if (is_array($value)) {
                $remoteRelations[$key] = $this->hasOneComponent($remoteClass, $key);
            }
        }

        // Without an explicit field name, attempt to match the first remote field
        // with the same type as the current class
        if (empty($remoteField)) {
            // look for remote has_one joins on this class or any parent classes
            $remoteRelationsMap = array_flip($remoteRelations ?? []);
            foreach (array_reverse(ClassInfo::ancestry($class) ?? []) as $ancestryClass) {
                if (array_key_exists($ancestryClass, $remoteRelationsMap ?? [])) {
                    $remoteField = $remoteRelationsMap[$ancestryClass];
                    break;
                }
            }
        }

        // In case of an indeterminate remote field show an error
        if (empty($remoteField)) {
            $polymorphic = false;
            $message = "No has_one found on class '$remoteClass'";
            if ($type == 'has_many') {
                // include a hint for has_many that is missing a has_one
                $message .= ", the has_many relation from '$class' to '$remoteClass'";
                $message .= " requires a has_one on '$remoteClass'";
            }
            throw new Exception($message);
        }

        // If given an explicit field name ensure the related class specifies this
        if (empty($remoteRelations[$remoteField])) {
            throw new Exception("Missing expected has_one named '$remoteField'
				on class '$remoteClass' referenced by $type named '$component'
				on class {$class}");
        }

        $polymorphic = $this->hasOneComponent($remoteClass, $remoteField) === DataObject::class;
        $remoteClassField = $polymorphic ? $remoteField . 'Class' : null;
        $needsRelation = $type === 'has_many' && $polymorphic && $this->hasOneComponentHandlesMultipleRelations($remoteClass, $remoteField);
        $remoteRelationField = $needsRelation ? $remoteField . 'Relation' : null;

        // This must be after the above assignments, as they rely on the original value.
        if (!$polymorphic) {
            $remoteField .= 'ID';
        }

        return [
            'joinField' => $remoteField,
            'relationField' => $remoteRelationField,
            'classField' => $remoteClassField,
            'polymorphic' => $polymorphic,
            'needsRelation' => $needsRelation,
        ];
    }

    /**
     * Validate the to or from field on a has_many mapping class
     *
     * @param string $parentClass Name of parent class
     * @param string $component Name of many_many component
     * @param string $joinClass Class for the joined table
     * @param array $specification Complete many_many specification
     * @param string $key Name of key to check ('from' or 'to')
     *
     * @return string Class that matches the given relation
     * @throws InvalidArgumentException
     */
    protected function checkManyManyFieldClass($parentClass, $component, $joinClass, $specification, $key)
    {
        // Ensure value for this key exists
        if (empty($specification[$key])) {
            throw new InvalidArgumentException(
                "many_many relation {$parentClass}.{$component} has missing {$key} which "
                . "should be a has_one on class {$joinClass}"
            );
        }

        // Check that the field exists on the given object
        $relation = $specification[$key];
        $relationClass = $this->hasOneComponent($joinClass, $relation);
        if (empty($relationClass)) {
            throw new InvalidArgumentException(
                "many_many through relation {$parentClass}.{$component} {$key} references a field name "
                . "{$joinClass}::{$relation} which is not a has_one"
            );
        }

        // Check for polymorphic
        /** @internal Polymorphic many_many is experimental */
        if ($relationClass === DataObject::class) {
            // Currently polymorphic 'from' is supported.
            if ($key === 'from') {
                return $relationClass;
            }

            throw new InvalidArgumentException(
                "many_many through relation {$parentClass}.{$component} {$key} references a polymorphic field "
                . "{$joinClass}::{$relation} which is not supported"
            );
        }

        // Validate the join class isn't also the name of a field or relation on either side
        // of the relation
        $field = $this->fieldSpec($relationClass, $joinClass);
        if ($field) {
            throw new InvalidArgumentException(
                "many_many through relation {$parentClass}.{$component} {$key} class {$relationClass} "
                . " cannot have a db field of the same name of the join class {$joinClass}"
            );
        }

        // Validate bad types on parent relation
        if ($key === 'from' && $relationClass !== $parentClass && !is_subclass_of($parentClass, $relationClass)) {
            throw new InvalidArgumentException(
                "many_many through relation {$parentClass}.{$component} {$key} references a field name "
                . "{$joinClass}::{$relation} of type {$relationClass}; {$parentClass} expected"
            );
        }
        return $relationClass;
    }

    /**
     * @param string $parentClass Name of parent class
     * @param string $component Name of many_many component
     * @param array $specification Complete many_many specification
     *
     * @return string Name of join class
     */
    protected function checkManyManyJoinClass($parentClass, $component, $specification)
    {
        if (empty($specification['through'])) {
            throw new InvalidArgumentException(
                "many_many relation {$parentClass}.{$component} has missing through which should be "
                . "a DataObject class name to be used as a join table"
            );
        }
        $joinClass = $specification['through'];
        if (!class_exists($joinClass ?? '')) {
            throw new InvalidArgumentException(
                "many_many relation {$parentClass}.{$component} has through class \"{$joinClass}\" which does not exist"
            );
        }
        return $joinClass;
    }

    private function checkHasOneArraySpec(string $class, string $component, array $spec): void
    {
        if (!array_key_exists('class', $spec)) {
            throw new InvalidArgumentException(
                "has_one relation {$class}.{$component} doesn't define a class for the relation"
            );
        }

        if (($spec[DataObjectSchema::HAS_ONE_MULTI_RELATIONAL] ?? false) === true
            && $spec['class'] !== DataObject::class
        ) {
            throw new InvalidArgumentException(
                "has_one relation {$class}.{$component} must be polymorphic, or not support multiple"
                . 'reciprocal has_many relations'
            );
        }
    }

    /**
     * Validate a given class is valid for a relation
     *
     * @param string $class Parent class
     * @param string $component Component name
     * @param string $relationClass Candidate class to check
     * @param string $type Relation type (e.g. has_one)
     */
    protected function checkRelationClass($class, $component, $relationClass, $type)
    {
        if (!is_string($component) || is_numeric($component)) {
            throw new InvalidArgumentException(
                "{$class} has invalid {$type} relation name"
            );
        }
        if (!is_string($relationClass)) {
            throw new InvalidArgumentException(
                "{$type} relation {$class}.{$component} is not a class name"
            );
        }
        if (!class_exists($relationClass ?? '')) {
            throw new InvalidArgumentException(
                "{$type} relation {$class}.{$component} references class {$relationClass} which doesn't exist"
            );
        }
        // Support polymorphic has_one
        if ($type === 'has_one') {
            $valid = is_a($relationClass, DataObject::class, true);
        } else {
            $valid = is_subclass_of($relationClass, DataObject::class, true);
        }
        if (!$valid) {
            throw new InvalidArgumentException(
                "{$type} relation {$class}.{$component} references class {$relationClass} "
                . " which is not a subclass of " . DataObject::class
            );
        }
    }
}
