<?php
/**
 * Represents a decimal field containing a currency amount.
 * The currency class only supports single currencies.  For multi-currency support, use {@link Money}
 * 
 * 
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 * 	"Price" => "Currency",
 * 	"Tax" => "Currency(5)",
 * );
 * </code>
 *
 * @package framework
 * @subpackage model
 */
class Currency extends Decimal {
	protected static $currencySymbol = '$';
	
	function __construct($name = null, $wholeSize = 9, $decimalSize = 2, $defaultValue = 0) {
		parent::__construct($name, $wholeSize, $decimalSize, $defaultValue);
	}
	
	/**
	 * Returns the number as a currency, eg “$1,000.00”.
	 */
	function Nice() {
		// return "<span title=\"$this->value\">$" . number_format($this->value, 2) . '</span>';
		$val = self::$currencySymbol . number_format(abs($this->value), 2);
		if($this->value < 0) return "($val)";
		else return $val;
	}
	
	/**
	 * Returns the number as a whole-number currency, eg “$1,000”.
	 */
	function Whole() {
		$val = self::$currencySymbol . number_format(abs($this->value), 0);
		if($this->value < 0) return "($val)";
		else return $val;
	}
	
	function setValue($value, $record = null) {
		$matches = null;
		if(is_numeric($value)) {
			$this->value = $value;
			
		} else if(preg_match('/-?\$?[0-9,]+(.[0-9]+)?([Ee][0-9]+)?/', $value, $matches)) {
			$this->value = str_replace(array('$',',',self::$currencySymbol),'',$matches[0]);
			
		} else {
			$this->value = 0;
		}
	}
	
	static function setCurrencySymbol($value) {
		self::$currencySymbol = $value;
	}
}

