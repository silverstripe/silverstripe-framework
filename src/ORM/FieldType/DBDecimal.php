<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;

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

        $this->defaultValue = number_format((float) $defaultValue, $decimalSize ?? 0);

        parent::__construct($name);
    }

    /**
     * @return float
     */
    public function Nice()
    {
        return number_format($this->value ?? 0.0, $this->decimalSize ?? 0);
    }

    /**
     * @return int
     */
    public function Int()
    {
        return floor($this->value ?? 0.0);
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'decimal',
            'precision' => "$this->wholeSize,$this->decimalSize",
            'default' => $this->defaultValue,
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'decimal',
            'parts' => $parts
        ];

        DB::require_field($this->tableName, $this->name, $values);
    }

    public function saveInto($dataObject)
    {
        $fieldName = $this->name;

        if ($fieldName) {
            if ($this->value instanceof DBField) {
                $this->value->saveInto($dataObject);
            } else {
                $value = (float) preg_replace('/[^0-9.\-\+]/', '', $this->value ?? '');
                $dataObject->__set($fieldName, $value);
            }
        } else {
            throw new \UnexpectedValueException(
                "DBField::saveInto() Called on a nameless '" . static::class . "' object"
            );
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

        if (abs((float) $value - (int) $value) < PHP_FLOAT_EPSILON) {
            return (int)$value;
        }

        return (float)$value;
    }
}
