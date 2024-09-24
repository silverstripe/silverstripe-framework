<?php

namespace SilverStripe\ORM\FieldType;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\Model\ModelFields\ModelField;
use SilverStripe\Model\ModelFields\ModelFieldTrait;
use SilverStripe\Model\ModelFields\StringModelField;

/**
 * Single field in the database.
 *
 * Every field from the database is represented as a sub-class of DBField.
 *
 * <b>Multi-value DBField objects</b>
 *
 * Sometimes you will want to make DBField classes that don't have a 1-1 match
 * to database fields.  To do this, there are a number of fields for you to
 * overload:
 *
 *  - Overload {@link writeToManipulation} to add the appropriate references to
 *      the INSERT or UPDATE command
 *  - Overload {@link addToQuery} to add the appropriate items to a SELECT
 *      query's field list
 *  - Add appropriate accessor methods
 *
 * <b>Subclass Example</b>
 *
 * The class is easy to overload with custom types, e.g. the MySQL "BLOB" type
 * (https://dev.mysql.com/doc/refman/8.4/en/blob.html).
 *
 * <code>
 * class Blob extends DBField {
 *  function requireField(): void {
 *      DB::require_field($this->tableName, $this->name, "blob");
 *  }
 * }
 * </code>
 */
trait DBFieldTrait
{
    /**
     * Raw value of this field
     */
    protected mixed $value = null;

    /**
     * Name of this field
     */
    protected ?string $name = null;

    /**
     * Table this field belongs to
     */
    protected ?string $tableName = null;

    /**
     * Used for generating DB schema. {@see DBSchemaManager}
     * Despite its name, this seems to be a string
     */
    protected $arrayValue;

    /**
     * Optional parameters for this field
     */
    protected array $options = [];

    /**
     * The type of index to use for this field. Can either be a string (one of the DBIndexable type options) or a
     * boolean. When a boolean is given, false will not index the field, and true will use the default index type.
     */
    private static string|bool $index = false;

    /**
     * Subclass of {@link SearchFilter} for usage in {@link defaultSearchFilter()}.
     */
    private static string $default_search_filter_class = 'PartialMatchFilter';

    /**
     * Default value in the database.
     * Might be overridden on DataObject-level, but still useful for setting defaults on
     * already existing records after a db-build.
     */
    protected mixed $defaultVal = null;

    /**
     * Provide the DBField name and an array of options, e.g. ['index' => true], or ['nullifyEmpty' => false]
     *
     * @throws InvalidArgumentException If $options was passed by not an array
     */
    public function __construct(?string $name = null, array $options = [])
    {
        if (!is_a(self::class, ModelField::class, true)) {
            throw new InvalidArgumentException(
                'DBFieldTrait can only be used on classes that extend ' . ModelField::class
            );
        }
        if (!is_a(self::class, DBField::class, true)) {
            throw new InvalidArgumentException(
                'DBFieldTrait can only be used on classes that implement ' . DBField::class
            );
        }
        if ($options) {
            if (!is_array($options)) {
                throw new InvalidArgumentException("Invalid options $options");
            }
            $this->setOptions($options);
        }
        parent::__construct($name);
    }

    /**
     * Set the name of this field.
     *
     * The name should never be altered, but it if was never given a name in
     * the first place you can set a name.
     *
     * If you try an alter the name a warning will be thrown.
     */
    public function setName(?string $name): static
    {
        if ($this->name && $this->name !== $name) {
            user_error("ModelField::setName() shouldn't be called once a ModelField already has a name."
                . "It's partially immutable - it shouldn't be altered after it's given a value.", E_USER_WARNING);
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Set the value of this field in various formats.
     * Used by {@link DataObject->getField()}, {@link DataObject->setCastedField()}
     * {@link DataObject->dbObject()} and {@link DataObject->write()}.
     *
     * As this method is used both for initializing the field after construction,
     * and actually changing its values, it needs a {@link $markChanged}
     * parameter.
     *
     * @param null|ModelData|array $record An array or object that this field is part of
     * @param bool $markChanged Indicate whether this field should be marked changed.
     *  Set to FALSE if you are initializing this field after construction, rather
     *  than setting a new value.
     */
    public function setValue(mixed $value, null|array|ModelData $record = null, bool $markChanged = true): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Add the field to the underlying database.
     */
    abstract public function requireField(): void;

    /**
     * Get default value assigned at the DB level
     */
    public function getDefaultValue(): mixed
    {
        return $this->defaultVal;
    }

    /**
     * Set default value to use at the DB level
     */
    public function setDefaultValue(mixed $defaultValue): static
    {
        $this->defaultVal = $defaultValue;
        return $this;
    }

    /**
     * Update the optional parameters for this field
     */
    public function setOptions(array $options = []): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get optional parameters for this field
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setIndexType($type): string|bool
    {
        if (!is_bool($type)
            && !in_array($type, [DBIndexable::TYPE_INDEX, DBIndexable::TYPE_UNIQUE, DBIndexable::TYPE_FULLTEXT])
        ) {
            throw new \InvalidArgumentException(
                "{$type} is not a valid index type or boolean. Please see DBIndexable."
            );
        }

        $this->options['index'] = $type;
        return $this;
    }

    public function getIndexType()
    {
        if (array_key_exists('index', $this->options ?? [])) {
            $type = $this->options['index'];
        } else {
            $type = static::config()->get('index');
        }

        if (is_bool($type)) {
            if (!$type) {
                return false;
            }
            $type = DBIndexable::TYPE_DEFAULT;
        }

        return $type;
    }

    /**
     * Determines if the field has a value which is not considered to be 'null'
     * in a database context.
     */
    public function exists(): bool
    {
        return (bool)$this->value;
    }

    /**
     * Return the transformed value ready to be sent to the database. This value
     * will be escaped automatically by the prepared query processor, so it
     * should not be escaped or quoted at all.
     *
     * @param mixed $value The value to check
     * @return mixed The raw value, or escaped parameterised details
     */
    public function prepValueForDB(mixed $value): mixed
    {
        if ($value === null ||
            $value === "" ||
            $value === false ||
            ($this->scalarValueOnly() && !is_scalar($value))
        ) {
            return null;
        } else {
            return $value;
        }
    }

    /**
     * Prepare the current field for usage in a
     * database-manipulation (works on a manipulation reference).
     *
     * Make value safe for insertion into
     * a SQL SET statement by applying addslashes() -
     * can also be used to apply special SQL-commands
     * to the raw value (e.g. for GIS functionality).
     * {@see prepValueForDB}
     */
    public function writeToManipulation(array &$manipulation): void
    {
        $manipulation['fields'][$this->name] = $this->exists()
            ? $this->prepValueForDB($this->value) : $this->nullValue();
    }

    /**
     * Add custom query parameters for this field,
     * mostly SELECT statements for multi-value fields.
     *
     * By default, the ORM layer does a
     * SELECT <tablename>.* which
     * gets you the default representations
     * of all columns.
     */
    public function addToQuery(SQLSelect &$query)
    {
    }

    /**
     * Assign this DBField to a table
     */
    public function setTable(string $tableName): static
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Get the table this field belongs to, if assigned
     */
    public function getTable(): ?string
    {
        return $this->tableName;
    }

    /**
     * Returns the value to be set in the database to blank this field.
     * Usually it's a choice between null, 0, and ''
     */
    public function nullValue(): mixed
    {
        return null;
    }

    /**
     * @param string $name Override name of this field
     */
    public function defaultSearchFilter(?string $name = null): SearchFilter
    {
        $name = ($name) ? $name : $this->name;
        $filterClass = static::config()->get('default_search_filter_class');
        return Injector::inst()->create($filterClass, $name);
    }

    public function getArrayValue()
    {
        return $this->arrayValue;
    }

    public function setArrayValue($value): static
    {
        $this->arrayValue = $value;
        return $this;
    }

    public function getIndexSpecs(): ?array
    {
        $type = $this->getIndexType();
        if ($type) {
            return [
                'type' => $type,
                'columns' => [$this->getName()],
            ];
        }
        return null;
    }
}
