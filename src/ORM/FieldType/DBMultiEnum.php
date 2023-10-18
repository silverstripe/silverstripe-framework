<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DB;

/**
 * Represents an multi-select enumeration field.
 */
class DBMultiEnum extends DBEnum
{
    public function __construct($name = null, $enum = null, $default = null)
    {
        // MultiEnum needs to take care of its own defaults
        parent::__construct($name, $enum, null);

        // Validate and assign the default
        $this->default = null;
        if ($default) {
            $defaults = preg_split('/ *, */', trim($default ?? ''));
            foreach ($defaults as $thisDefault) {
                if (!in_array($thisDefault, $this->enum ?? [])) {
                    throw new \InvalidArgumentException(
                        "Enum::__construct() The default value '$thisDefault' does not match "
                        . 'any item in the enumeration'
                    );
                }
            }
            $this->default = implode(',', $defaults);
        }
    }

    public function requireField()
    {
        $charset = Config::inst()->get(MySQLDatabase::class, 'charset');
        $collation = Config::inst()->get(MySQLDatabase::class, 'collation');
        $values = [
            'type' => 'set',
            'parts' => [
                'enums' => $this->enum,
                'character set' => $charset,
                'collate' => $collation,
                'default' => $this->default,
                'table' => $this->tableName,
                'arrayValue' => $this->arrayValue,
            ],
        ];

        DB::require_field($this->tableName, $this->name, $values);
    }


    /**
     * Return a {@link CheckboxSetField} suitable for editing this field
     *
     * @param string $title
     * @param string $name
     * @param bool $hasEmpty
     * @param string $value
     * @param string $emptyString
     * @return CheckboxSetField
     */
    public function formField($title = null, $name = null, $hasEmpty = false, $value = '', $emptyString = null)
    {

        if (!$title) {
            $title = $this->name;
        }
        if (!$name) {
            $name = $this->name;
        }

        return new CheckboxSetField($name, $title, $this->enumValues($hasEmpty), $value);
    }
}
