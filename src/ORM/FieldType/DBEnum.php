<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\SelectField;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DB;

/**
 * Class Enum represents an enumeration of a set of strings.
 *
 * See {@link DropdownField} for a {@link FormField} to select enum values.
 */
class DBEnum extends DBString
{
    /**
     * List of enum values
     */
    protected array $enum = [];

    /**
     * Default value
     */
    protected ?string $default = null;

    private static string $default_search_filter_class = 'ExactMatchFilter';

    /**
     * Internal cache for obsolete enum values. The top level keys are the table, each of which contains
     * nested arrays with keys mapped to field names. The values of the lowest level array are the enum values
     */
    protected static array $enum_cache = [];

    /**
     * Clear all cached enum values.
     */
    public static function flushCache(): void
    {
        DBEnum::$enum_cache = [];
    }

    /**
     * Create a new Enum field, which is a value within a defined set, with an optional default.
     *
     * Example field specification strings:
     *
     * <code>
     *  "MyField" => "Enum('Val1, Val2, Val3')" // First item 'Val1' is default implicitly
     *  "MyField" => "Enum('Val1, Val2, Val3', 'Val2')" // 'Val2' is default explicitly
     *  "MyField" => "Enum('Val1, Val2, Val3', null)" // Force empty (no) default
     *  "MyField" => "Enum(['Val1', 'Val2', 'Val3'], 'Val1')" // Supports array notation as well
     * </code>
     *
     * @param string|array $enum A string containing a comma separated list of options or an array of Vals.
     * @param string|int|null $default The default option, which is either NULL or one of the items in the enumeration.
     * If passing in an integer (non-string) it will default to the index of that item in the list.
     * Set to null or empty string to allow empty values
     * @param array $options Optional parameters for this DB field
     */
    public function __construct(
        ?string $name = null,
        string|array|null $enum = null,
        string|int|null $default = 0,
        array $options = []
    ) {
        if ($enum) {
            $this->setEnum($enum);
            $enum = $this->getEnum();

            // If there's a default, then use this
            if ($default && !is_int($default)) {
                if (in_array($default, $enum ?? [])) {
                    $this->setDefault($default);
                } else {
                    throw new \InvalidArgumentException(
                        "Enum::__construct() The default value '$default' does not match any item in the enumeration"
                    );
                }
            } elseif (is_int($default) && $default < count($enum ?? [])) {
                // Set to specified index if given
                $this->setDefault($enum[$default]);
            } else {
                // Set to null if specified
                $this->setDefault(null);
            }
        }

        parent::__construct($name, $options);
    }

    public function requireField(): void
    {
        $charset = Config::inst()->get(MySQLDatabase::class, 'charset');
        $collation = Config::inst()->get(MySQLDatabase::class, 'collation');

        $parts = [
            'datatype' => 'enum',
            'enums' => $this->getEnumObsolete(),
            'character set' => $charset,
            'collate' => $collation,
            'default' => $this->getDefault(),
            'table' => $this->getTable(),
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'enum',
            'parts' => $parts
        ];

        DB::require_field($this->getTable(), $this->getName(), $values);
    }

    /**
     * Return a form field suitable for editing this field.
     */
    public function formField(
        ?string $title = null,
        ?string $name = null,
        bool $hasEmpty = false,
        ?string $value = '',
        ?string $emptyString = null
    ): SelectField {
        if (!$title) {
            $title = $this->getName();
        }
        if (!$name) {
            $name = $this->getName();
        }

        $field = DropdownField::create($name, $title, $this->enumValues(false), $value);
        if ($hasEmpty) {
            $field->setEmptyString($emptyString);
        }

        return $field;
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return $this->formField($title);
    }

    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        $anyText = _t(__CLASS__ . '.ANY', 'Any');
        return $this->formField($title, null, true, '', "($anyText)");
    }

    /**
     * Returns the values of this enum as an array, suitable for insertion into
     * a {@link DropdownField}
     */
    public function enumValues(bool $hasEmpty = false): array
    {
        return ($hasEmpty)
            ? array_merge(['' => ''], ArrayLib::valuekey($this->getEnum()))
            : ArrayLib::valuekey($this->getEnum());
    }

    /**
     * Get list of enum values
     */
    public function getEnum(): array
    {
        return $this->enum;
    }

    /**
     * Get the list of enum values, including obsolete values still present in the database
     *
     * If table or name are not set, or if it is not a valid field on the given table,
     * then only known enum values are returned.
     *
     * Values cached in this method can be cleared via `DBEnum::flushCache();`
     */
    public function getEnumObsolete(): array
    {
        // Without a table or field specified, we can only retrieve known enum values
        $table = $this->getTable();
        $name = $this->getName();
        if (empty($table) || empty($name)) {
            return $this->getEnum();
        }

        // Ensure the table level cache exists
        if (empty(DBEnum::$enum_cache[$table])) {
            DBEnum::$enum_cache[$table] = [];
        }

        // Check existing cache
        if (!empty(DBEnum::$enum_cache[$table][$name])) {
            return DBEnum::$enum_cache[$table][$name];
        }

        // Get all enum values
        $enumValues = $this->getEnum();
        if (DB::get_schema()->hasField($table, $name)) {
            $existing = DB::query("SELECT DISTINCT \"{$name}\" FROM \"{$table}\"")->column();
            $enumValues = array_unique(array_merge($enumValues, $existing));
        }

        // Cache and return
        DBEnum::$enum_cache[$table][$name] = $enumValues;
        return $enumValues;
    }

    /**
     * Set enum options
     */
    public function setEnum(string|array $enum): static
    {
        if (!is_array($enum)) {
            $enum = preg_split(
                '/\s*,\s*/',
                // trim commas only if they are on the right with a newline following it
                ltrim(preg_replace('/,\s*\n\s*$/', '', $enum ?? '') ?? '')
            );
        }
        $this->enum = array_values($enum ?? []);
        return $this;
    }

    /**
     * Get default value
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * Set default value
     */
    public function setDefault(?string $default): static
    {
        $this->default = $default;
        $this->setDefaultValue($default);
        return $this;
    }
}
