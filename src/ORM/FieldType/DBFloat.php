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
        $parts = array(
            'datatype'=>'float',
            'null'=>'not null',
            'default'=>$this->defaultVal,
            'arrayValue'=>$this->arrayValue
        );
        $values = array('type'=>'float', 'parts'=>$parts);
        DB::require_field($this->tableName, $this->name, $values);
    }

	/**
	 * Decimal and thousands separator are configurable with yaml
	 *
	 * SilverStripe\ORM\FieldType\DBFloat.decimal_separator & SilverStripe\ORM\FieldType\DBFloat.thousands_separator
	 *
	 * @return array
	 */
	public static function get_number_format_separators()
	{
		$decimalSep = self::config()->get('decimal_separator');
		$thousandsSep = self::config()->get('thousands_separator');

		return [
			'decimal' => (!$decimalSep ? '.' : $decimalSep),
			'thousand' => (!$thousandsSep ? ',' : $thousandsSep),
		];
	}
	/**
	 * Returns the number, with commas and decimal places as appropriate, eg “1,000.00”.
	 *
	 * @param int $decimals
	 *
	 * @uses number_format()
	 *
	 * @return string
	 */
	public function Nice($decimals = 2)
	{
		$separators = self::get_number_format_separators();
		return number_format($this->value, $decimals, $separators['decimal'], $separators['thousand']);
	}

    public function Round($precision = 3)
    {
        return round($this->value, $precision);
    }

	public function NiceRound($precision = 3)
	{
		$separators = self::get_number_format_separators();
		return number_format(round($this->value, $precision), $precision, $separators['decimal'], $separators['thousand']);
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
        } elseif (empty($value) || !is_numeric($value)) {
            return 0;
        }

        return $value;
    }
}
