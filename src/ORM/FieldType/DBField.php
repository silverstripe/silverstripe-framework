<?php

namespace SilverStripe\ORM\FieldType;

use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Model\ModelData;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Validation\FieldValidator;

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
abstract class DBField extends ModelData implements DBIndexable
{
    /**
     * Raw value of this field
     */
    protected mixed $value = null;

    /**
     * Table this field belongs to
     */
    protected ?string $tableName = null;

    /**
     * Name of this field
     */
    protected ?string $name = null;

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
     * The escape type for this field when inserted into a template - either "xml" or "raw".
     */
    private static string $escape_type = 'raw';

    /**
     * Subclass of {@link SearchFilter} for usage in {@link defaultSearchFilter()}.
     */
    private static string $default_search_filter_class = 'PartialMatchFilter';

    /**
     * The type of index to use for this field. Can either be a string (one of the DBIndexable type options) or a
     * boolean. When a boolean is given, false will not index the field, and true will use the default index type.
     */
    private static string|bool $index = false;

    private static array $casting = [
        'ATT' => 'HTMLFragment',
        'CDATA' => 'HTMLFragment',
        'HTML' => 'HTMLFragment',
        'HTMLATT' => 'HTMLFragment',
        'JS' => 'HTMLFragment',
        'RAW' => 'HTMLFragment',
        'RAWURLATT' => 'HTMLFragment',
        'URLATT' => 'HTMLFragment',
        'XML' => 'HTMLFragment',
        'ProcessedRAW' => 'HTMLFragment',
    ];

    private static array $field_validators = [];

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
        $this->name = $name;

        if ($options) {
            if (!is_array($options)) {
                throw new InvalidArgumentException("Invalid options $options");
            }
            $this->setOptions($options);
        }

        parent::__construct();
    }

    /**
     * Create a DBField object that's not bound to any particular field.
     *
     * Useful for accessing the classes behaviour for other parts of your code.
     *
     * @param string $spec Class specification to construct. May include both service name and additional
     * constructor arguments in the same format as DataObject.db config.
     * @param mixed $value value of field
     * @param null|string $name Name of field
     * @param mixed $args Additional arguments to pass to constructor if not using args in service $spec
     * Note: Will raise a warning if using both
     */
    public static function create_field(string $spec, mixed $value, ?string $name = null, mixed ...$args): static
    {
        // Raise warning if inconsistent with DataObject::dbObject() behaviour
        // This will cause spec args to be shifted down by the number of provided $args
        if ($args && strpos($spec ?? '', '(') !== false) {
            trigger_error('Additional args provided in both $spec and $args', E_USER_WARNING);
        }
        // Ensure name is always first argument
        array_unshift($args, $name);

        /** @var DBField $dbField */
        $dbField = Injector::inst()->createWithArgs($spec, $args);
        $dbField->setValue($value, null, false);
        return $dbField;
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
            user_error("DBField::setName() shouldn't be called once a DBField already has a name."
                . "It's partially immutable - it shouldn't be altered after it's given a value.", E_USER_WARNING);
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Returns the name of this field.
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Returns the value of this field.
     */
    public function getValue(): mixed
    {
        return $this->value;
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
     * Determine 'default' casting for this field.
     */
    public function forTemplate(): string
    {
        // Default to XML encoding
        return $this->XML();
    }

    /**
     * Gets the value appropriate for a HTML attribute string
     */
    public function HTMLATT(): string
    {
        return Convert::raw2htmlatt($this->RAW());
    }

    /**
     * urlencode this string
     */
    public function URLATT(): string
    {
        return urlencode($this->RAW() ?? '');
    }

    /**
     * rawurlencode this string
     */
    public function RAWURLATT(): string
    {
        return rawurlencode($this->RAW() ?? '');
    }

    /**
     * Gets the value appropriate for a HTML attribute string
     */
    public function ATT(): string
    {
        return Convert::raw2att($this->RAW());
    }

    /**
     * Gets the raw value for this field.
     * Note: Skips processors implemented via forTemplate()
     */
    public function RAW(): mixed
    {
        return $this->getValue();
    }

    /**
     * Gets javascript string literal value
     */
    public function JS(): string
    {
        return Convert::raw2js($this->RAW());
    }

    /**
     * Return JSON encoded value
     */
    public function JSON(): string
    {
        return json_encode($this->RAW());
    }

    /**
     * Alias for {@see XML()}
     */
    public function HTML(): string
    {
        return $this->XML();
    }

    /**
     * XML encode this value
     */
    public function XML(): string
    {
        return Convert::raw2xml($this->RAW());
    }

    /**
     * Safely escape for XML string
     */
    public function CDATA(): string
    {
        return $this->XML();
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
     * Saves this field to the given data object.
     */
    public function saveInto(ModelData $model): void
    {
        $fieldName = $this->name;
        if (empty($fieldName)) {
            throw new \BadMethodCallException(
                "DBField::saveInto() Called on a nameless '" . static::class . "' object"
            );
        }
        if ($this->value instanceof DBField) {
            $this->value->saveInto($model);
        } else {
            $model->__set($fieldName, $this->value);
        }
    }

    /**
     * Validate this field. Called during DataObject::validate().
     */
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        $fieldValidators = FieldValidator::createFieldValidatorsForField($this, $this->getName(), $this->getValue());
        foreach ($fieldValidators as $fieldValidator) {
            $validationResult = $fieldValidator->validate();
            if (!$validationResult->isValid()) {
                $result->combineAnd($validationResult);
            }
        }
        return $result;
    }

    /**
     * Returns a FormField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     */
    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return TextField::create($this->name, $title);
    }

    /**
     * Returns a FormField instance used as a default
     * for searchform scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}.
     *
     * @param string $title Optional. Localized title of the generated instance
     */
    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        return $this->scaffoldFormField($title);
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

    /**
     * Add the field to the underlying database.
     */
    abstract public function requireField(): void;

    public function debug(): string
    {
        return <<<DBG
<ul>
	<li><b>Name:</b>{$this->name}</li>
	<li><b>Table:</b>{$this->tableName}</li>
	<li><b>Value:</b>{$this->value}</li>
</ul>
DBG;
    }

    public function __toString(): string
    {
        return (string)$this->forTemplate();
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

    /**
     * Get formfield schema value for use in formschema response
     */
    public function getSchemaValue(): mixed
    {
        return $this->RAW();
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

    /**
     * Whether or not this DBField only accepts scalar values.
     *
     * Composite DBFields can override this method and return `false` so they can accept arrays of values.
     */
    public function scalarValueOnly(): bool
    {
        return true;
    }
}
