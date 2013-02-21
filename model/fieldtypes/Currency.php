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
	protected static $currencySymbolBack = '';
	
	public function __construct($name = null, $wholeSize = 9, $decimalSize = 2, $defaultValue = 0) {
		parent::__construct($name, $wholeSize, $decimalSize, $defaultValue);
	}
	
	/**
	 * Returns the number as a currency, eg “$1,000.00”.
	 */
	public function Nice() {
		// return "<span title=\"$this->value\">$" . number_format($this->value, 2) . '</span>';
		$val = self::$currencySymbol . number_format(abs($this->value), 2) . self::$currencySymbolBack;
		if($this->value < 0) return "($val)";
		else return $val;
	}
	
	/**
	 * Returns the number as a whole-number currency, eg “$1,000”.
	 */
	public function Whole() {
		$val = self::$currencySymbol . number_format(abs($this->value), 0) . self::$currencySymbolBack;
		if($this->value < 0) return "($val)";
		else return $val;
	}
	
	public function setValue($value, $record = null) {
		$matches = null;
		if(is_numeric($value)) {
			$this->value = (string) $value;
			$this->value = str_replace(',','.',$this->value);
		} else if(preg_match('/-?\$?[0-9,]+(.[0-9]+)?([Ee][0-9]+)?/', $value, $matches)) {
			$this->value = str_replace(array('$',self::$currencySymbol,self::$currencySymbolBack),'',$matches[0]);
			$this->value = str_replace(',','.',$matches[0]);
		} else {
			$this->value = 0;
		}
	}
	
	public static function getCurrencySymbol() {
		return self::$currencySymbol;
	}
	
	public static function setCurrencySymbol($value) {
		self::$currencySymbol = $value;
	}
	
	public static function getCurrencySymbolBack() {
		return self::$currencySymbolBack;
	}
	
	public static function setCurrencySymbolBack($value) {
		self::$currencySymbolBack = $value;
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new CurrencyField($this->name, $title);
	}
}

