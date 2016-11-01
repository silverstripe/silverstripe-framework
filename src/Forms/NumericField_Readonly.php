<?php

namespace SilverStripe\Forms;

/**
 * Readonly version of a numeric field.
 */
class NumericField_Readonly extends ReadonlyField
{
	/**
	 * @return static
	 */
	public function performReadonlyTransformation()
	{
		return clone $this;
	}

	/**
	 * @return string
	 */
	public function Value()
	{
		return $this->value ?: '0';
	}

	public function getValueCast()
	{
		return 'Decimal';
	}
}
