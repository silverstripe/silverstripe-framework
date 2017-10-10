<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\NumericField;

/**
 * Represents a Decimal field.
 */
class DBDecimal extends DBField
{

    /**
     * Whole number size
     *
     * @var int
     */
    protected $wholeSize = 9;

    /**
     * Decimal scale
     *
     * @var int
     */
    protected $decimalSize = 2;

    /**
     * Default value
     *
     * @var string
     */
    protected $defaultValue = 0;

    /**
     * Create a new Decimal field.
     *
     * @param string $name
     * @param int $wholeSize
     * @param int $decimalSize
     * @param float|int $defaultValue
     */
    public function __construct($name = null, $wholeSize = 9, $decimalSize = 2, $defaultValue = 0)
    {
        $this->wholeSize = is_int($wholeSize) ? $wholeSize : 9;
        $this->decimalSize = is_int($decimalSize) ? $decimalSize : 2;

        $this->defaultValue = number_format((float) $defaultValue, $decimalSize);

        parent::__construct($name);
    }

    /**
     * @return float
     */
    public function Nice()
    {
        return number_format($this->value, $this->decimalSize);
    }

    /**
     * @return int
     */
    public function Int()
    {
        return floor($this->value);
    }

    public function requireField()
    {
        $parts = array(
            'datatype' => 'decimal',
            'precision' => "$this->wholeSize,$this->decimalSize",
            'default' => $this->defaultValue,
            'arrayValue' => $this->arrayValue
        );

        $values = array(
            'type' => 'decimal',
            'parts' => $parts
        );

        DB::require_field($this->tableName, $this->name, $values);
    }

    public function saveInto($dataObject)
    {
        $fieldName = $this->name;

        if ($fieldName) {
            $dataObject->$fieldName = (float)preg_replace('/[^0-9.\-\+]/', '', $this->value);
        } else {
            user_error("DBField::saveInto() Called on a nameless '" . static::class . "' object", E_USER_ERROR);
        }
    }

    /**
     * @param string $title
     * @param array $params
     *
     * @return NumericField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return NumericField::create($this->name, $title)
            ->setScale($this->decimalSize);
    }

    /**
     * @return float
     */
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

        if (ctype_digit($value)) {
            return (int)$value;
        }

        return (float)$value;
    }
}
