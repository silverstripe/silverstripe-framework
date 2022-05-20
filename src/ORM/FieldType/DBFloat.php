<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;

/**
 * Represents a floating point field.
 */
class DBFloat extends DBField
{

    public function __construct($name = null, $defaultVal = 0)
    {
        $this->defaultVal = is_float($defaultVal) ? $defaultVal : (float) 0;

        parent::__construct($name);
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'float',
            'null' => 'not null',
            'default' => $this->defaultVal,
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'float', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    /**
     * Returns the number, with commas and decimal places as appropriate, eg “1,000.00”.
     *
     * @uses number_format()
     */
    public function Nice()
    {
        return number_format($this->value ?? 0.0, 2);
    }

    public function Round($precision = 3)
    {
        return round($this->value ?? 0.0, $precision ?? 0);
    }

    public function NiceRound($precision = 3)
    {
        return number_format(round($this->value ?? 0.0, $precision ?? 0), $precision ?? 0);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        $field = NumericField::create($this->name, $title);
        $field->setScale(null); // remove no-decimal restriction
        return $field;
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

        return $value;
    }
}
