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
		$val = '$' . number_format(abs($this->value), 2);
		if($this->value < 0) return "($val)";
		else return $val;
	}
	
	function Whole() {
		$val = '$' . number_format(abs($this->value), 0);
		if($this->value < 0) return "($val)";
		else return $val;
	}
	
	function setValue($value) {
		$matches = null;
		if(is_numeric($value)) {
			$this->value = $value;
			
		} else if(preg_match('/-?\$?[0-9,]+(.[0-9]+)?([Ee][0-9]+)?/', $value, $matches)) {
			$this->value = str_replace(array('$',','),'',$matches[0]);
			
		} else {
			$this->value = 0;
		}
	}
}

?>