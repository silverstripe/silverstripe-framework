<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DB;

/**
 * Represents a boolean field.
 */
class DBBoolean extends DBField
{
    public function __construct($name = null, $defaultVal = 0)
    {
        $this->defaultVal = ($defaultVal) ? 1 : 0;

        parent::__construct($name);
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'tinyint',
            'precision' => 1,
            'sign' => 'unsigned',
            'null' => 'not null',
            'default' => $this->defaultVal,
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'boolean', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function Nice()
    {
        return ($this->value) ? _t(__CLASS__ . '.YESANSWER', 'Yes') : _t(__CLASS__ . '.NOANSWER', 'No');
    }

    public function NiceAsBoolean()
    {
        return ($this->value) ? 'true' : 'false';
    }

    public function saveInto($dataObject)
    {
        $fieldName = $this->name;
        if ($fieldName) {
            if ($this->value instanceof DBField) {
                $this->value->saveInto($dataObject);
            } else {
                $dataObject->__set($fieldName, $this->value ? 1 : 0);
            }
        } else {
            $class = static::class;
            throw new \RuntimeException("DBField::saveInto() Called on a nameless '$class' object");
        }
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return CheckboxField::create($this->name, $title);
    }

    public function scaffoldSearchField($title = null)
    {
        $anyText = _t(__CLASS__ . '.ANY', 'Any');
        $source = [
            '' => $anyText,
            1 => _t(__CLASS__ . '.YESANSWER', 'Yes'),
            0 => _t(__CLASS__ . '.NOANSWER', 'No')
        ];

        return (new DropdownField($this->name, $title, $source))
            ->setEmptyString($anyText);
    }

    public function nullValue()
    {
        return 0;
    }

    public function prepValueForDB($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (empty($value)) {
            return 0;
        }
        if (is_string($value)) {
            switch (strtolower($value ?? '')) {
                case 'false':
                case 'f':
                    return 0;
                case 'true':
                case 't':
                    return 1;
            }
        }
        return $value ? 1 : 0;
    }
}
