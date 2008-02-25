<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a decimal field containing a currency amount.
 * Currency the currency class only supports single currencies.
 * @package sapphire
 * @subpackage model
 */
class Currency extends Decimal {
	
	function Nice() {
		// return "<span title=\"$this->value\">$" . number_format($this->value, 2) . '</span>';
		return '$' . number_format($this->value, 2);
	}
	
	function Whole() {
		return '$' . number_format($this->value, 0);
	}
	
	function setValue($value) {
		$this->value = ereg_replace('[^0-9.]+','', $value);
	}
}

?>