<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\View\ArrayData;

/**
 * Represents a signed 32 bit integer field.
 */
class DBInt extends DBField
{

    public function __construct($name = null, $defaultVal = 0)
    {
        $this->defaultVal = is_int($defaultVal) ? $defaultVal : 0;

        parent::__construct($name);
    }

    /**
     * Ensure int values are always returned.
     * This is for mis-configured databases that return strings.
     */
    public function getValue()
    {
        return (int) $this->value;
    }

    /**
     * Returns the number, with commas added as appropriate, eg “1,000”.
     */
    public function Formatted()
    {
        return number_format($this->value ?? 0.0);
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'int',
            'precision' => 11,
            'null' => 'not null',
            'default' => $this->defaultVal,
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'int', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function Times()
    {
        $output = new ArrayList();
        for ($i = 0; $i < $this->value; $i++) {
            $output->push(ArrayData::create(['Number' => $i + 1]));
        }

        return $output;
    }

    public function Nice()
    {
        return sprintf('%d', $this->value);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return NumericField::create($this->name, $title);
    }

    public function nullValue()
    {
        return 0;
    }

    public function prepValueForDB($value)
    {
        if ($value === true) {
            return 1;
        }

        if (empty($value) || !is_numeric($value)) {
            return 0;
        }

        return (int)$value;
    }
}
