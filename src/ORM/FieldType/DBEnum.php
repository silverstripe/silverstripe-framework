<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayLib;
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
     *
     * @var array
     */
    protected $enum = array();

    /**
     * Default value
     *
     * @var string|null
     */
    protected $default = null;

    private static $default_search_filter_class = 'ExactMatchFilter';

    /**
     * Create a new Enum field, which is a value within a defined set, with an optional default.
     *
     * Example field specification strings:
     *
     * <code>
     *  "MyField" => "Enum('Val1, Val2, Val3')" // First item 'Val1' is default implicitly
     *  "MyField" => "Enum('Val1, Val2, Val3', 'Val2')" // 'Val2' is default explicitly
     *  "MyField" => "Enum('Val1, Val2, Val3', null)" // Force empty (no) default
     *  "MyField" => "Enum(array('Val1', 'Val2', 'Val3'), 'Val1')" // Supports array notation as well
     * </code>
     *
     * @param string $name
     * @param string|array $enum A string containing a comma separated list of options or an array of Vals.
     * @param string|int|null $default The default option, which is either NULL or one of the items in the enumeration.
     * If passing in an integer (non-string) it will default to the index of that item in the list.
     * Set to null or empty string to allow empty values
     * @param array  $options Optional parameters for this DB field
     */
    public function __construct($name = null, $enum = null, $default = 0, $options = [])
    {
        if ($enum) {
            $this->setEnum($enum);
            $enum = $this->getEnum();

            // If there's a default, then use this
            if ($default && !is_int($default)) {
                if (in_array($default, $enum)) {
                    $this->setDefault($default);
                } else {
                    user_error(
                        "Enum::__construct() The default value '$default' does not match any item in the enumeration",
                        E_USER_ERROR
                    );
                }
            } elseif (is_int($default) && $default < count($enum)) {
                // Set to specified index if given
                $this->setDefault($enum[$default]);
            } else {
                // Set to null if specified
                $this->setDefault(null);
            }
        }

        parent::__construct($name, $options);
    }

    /**
     * @return void
     */
    public function requireField()
    {
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');

        $parts = array(
            'datatype' => 'enum',
            'enums' => $this->getEnum(),
            'character set' => $charset,
            'collate' => $collation,
            'default' => $this->getDefault(),
            'table' => $this->getTable(),
            'arrayValue' => $this->arrayValue
        );

        $values = array(
            'type' => 'enum',
            'parts' => $parts
        );

        DB::require_field($this->getTable(), $this->getName(), $values);
    }

    /**
     * Return a dropdown field suitable for editing this field.
     *
     * @param string $title
     * @param string $name
     * @param bool $hasEmpty
     * @param string $value
     * @param string $emptyString
     * @return DropdownField
     */
    public function formField($title = null, $name = null, $hasEmpty = false, $value = "", $emptyString = null)
    {

        if (!$title) {
            $title = $this->getName();
        }
        if (!$name) {
            $name = $this->getName();
        }

        $field = new DropdownField($name, $title, $this->enumValues(false), $value);
        if ($hasEmpty) {
            $field->setEmptyString($emptyString);
        }

        return $field;
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return $this->formField($title);
    }

    /**
     * @param string
     *
     * @return DropdownField
     */
    public function scaffoldSearchField($title = null)
    {
        $anyText = _t('SilverStripe\\ORM\\FieldType\\DBEnum.ANY', 'Any');
        return $this->formField($title, null, true, $anyText, "($anyText)");
    }

    /**
     * Returns the values of this enum as an array, suitable for insertion into
     * a {@link DropdownField}
     *
     * @param boolean
     *
     * @return array
     */
    public function enumValues($hasEmpty = false)
    {
        return ($hasEmpty)
            ? array_merge(array('' => ''), ArrayLib::valuekey($this->getEnum()))
            : ArrayLib::valuekey($this->getEnum());
    }

    /**
     * Get list of enum values
     *
     * @return array
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * Set enum options
     *
     * @param string|array $enum
     * @return $this
     */
    public function setEnum($enum)
    {
        if (!is_array($enum)) {
            $enum = preg_split(
                '/\s*,\s*/',
                // trim commas only if they are on the right with a newline following it
                ltrim(preg_replace('/,\s*\n\s*$/', '', $enum))
            );
        }
        $this->enum = array_values($enum);
        return $this;
    }

    /**
     * Get default vwalue
     *
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set default value
     *
     * @param string $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;
        $this->setDefaultValue($default);
        return $this;
    }
}
