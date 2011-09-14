<?php
/**
 * Partially based on Zend_Currency.
 *
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd	 New BSD License
 * @version   $Id: Currency.php 6137 2007-08-19 14:55:27Z shreef $
 */

require_once 'Zend/Currency.php';

/**
 * Implements the "Money" pattern.
 * 
 * @see http://www.martinfowler.com/eaaCatalog/money.html
 *
 * @todo Support different ways of rounding
 * @todo Equality operators
 * @todo Addition, substraction and allocation of values
 * @todo Model validation for $allowedCurrencies
 * 
 * @package sapphire
 * @subpackage model
 */
class Money extends DBField implements CompositeDBField {

	/**
	 * @var string $getCurrency()
	 */
	protected $currency;

	/**
	 * @var float $currencyAmount
	 */
	protected $amount;

	/**
	 * @var boolean $isChanged
	 */
	protected $isChanged = false;
	
	/**
	 * @var string $locale
	 */
	protected $locale = null;
	
	/**
	 * @var Zend_Currency
	 */
	protected $currencyLib;
	
	/**
	 * Limit the currencies
	 * @var array $allowedCurrencies
	 */
	protected $allowedCurrencies;
	
	/**
	 * @param array
	 */
	static $composite_db = array(
		"Currency" => "Varchar(3)",
		"Amount" => 'Decimal(19,4)'
	);
	
	function __construct($name = null) {
		$this->currencyLib = new Zend_Currency(null, i18n::default_locale());
		
		parent::__construct($name);
	}
	
	function compositeDatabaseFields() {
		return self::$composite_db;
	}

	function requireField() {
		$fields = $this->compositeDatabaseFields();
		if($fields) foreach($fields as $name => $type){
			DB::requireField($this->tableName, $this->name.$name, $type);
		}
	}

	function writeToManipulation(&$manipulation) {
		if($this->getCurrency()) {
			$manipulation['fields'][$this->name.'Currency'] = $this->prepValueForDB($this->getCurrency());
		} else {
			$manipulation['fields'][$this->name.'Currency'] = DBField::create('Varchar', $this->getCurrency())->nullValue();
		}
		
		if($this->getAmount()) {
			$manipulation['fields'][$this->name.'Amount'] = $this->getAmount();
		} else {
			$manipulation['fields'][$this->name.'Amount'] = DBField::create('Decimal', $this->getAmount())->nullValue();
		}
	}
	
	function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->select[] = sprintf('"%sAmount"', $this->name);
		$query->select[] = sprintf('"%sCurrency"', $this->name);
	}

	function setValue($value, $record = null, $markChanged = true) {
		// @todo Allow resetting value to NULL through Money $value field
		if ($value instanceof Money && $value->hasValue()) {
			$this->setCurrency($value->getCurrency(), $markChanged);
			$this->setAmount($value->getAmount(), $markChanged);
			if($markChanged) $this->isChanged = true;
		} else if($record && isset($record[$this->name . 'Currency']) && isset($record[$this->name . 'Amount'])) {
			if($record[$this->name . 'Currency'] && $record[$this->name . 'Amount']) {
				$this->setCurrency($record[$this->name . 'Currency'], $markChanged);
				$this->setAmount($record[$this->name . 'Amount'], $markChanged);
			} else {
				$this->value = $this->nullValue();
			}
			if($markChanged) $this->isChanged = true;
		} else if (is_array($value)) {
			if (array_key_exists('Currency', $value)) {
				$this->setCurrency($value['Currency'], $markChanged);
			}
			if (array_key_exists('Amount', $value)) {
				$this->setAmount($value['Amount'], $markChanged);
			}
			if($markChanged) $this->isChanged = true;
		} else {
			// @todo Allow to reset a money value by passing in NULL
			//user_error('Invalid value in Money->setValue()', E_USER_ERROR);
		}
	}

	/**
	 * @return string
	 */
	function Nice($options = array()) {
		$amount = $this->getAmount();
		if(!isset($options['display'])) $options['display'] = Zend_Currency::USE_SYMBOL;
		if(!isset($options['currency'])) $options['currency'] = $this->getCurrency();
		if(!isset($options['symbol'])) $options['symbol'] = $this->currencyLib->getSymbol($this->getCurrency(), $this->getLocale());
		return (is_numeric($amount)) ? $this->currencyLib->toCurrency($amount, $options) : '';
	}
	
	/**
	 * @return string
	 */
	function NiceWithShortname($options = array()){
		$options['display'] = Zend_Currency::USE_SHORTNAME;
		return $this->Nice($options);
	}
	
	/**
	 * @return string
	 */
	function NiceWithName($options = array()){
		$options['display'] = Zend_Currency::USE_NAME;
		return $this->Nice($options);
	}

	/**
	 * @return string
	 */
	function getCurrency() {
		return $this->currency;
	}
	
	/**
	 * @param string
	 */
	function setCurrency($currency, $markChanged = true) {
		$this->currency = $currency;
		if($markChanged) $this->isChanged = true;
	}
	
	/**
	 * @todo Return casted Float DBField?
	 * 
	 * @return float
	 */
	function getAmount() {
		return $this->amount;
	}
	
	/**
	 * @param float $amount
	 */
	function setAmount($amount, $markChanged = true) {
		$this->amount = (float)$amount;
		if($markChanged) $this->isChanged = true;
	}
	
	/**
	 * @return boolean
	 */
	function hasValue() {
		return ($this->getCurrency() && is_numeric($this->getAmount()));
	}
	
	/**
	 * @return boolean
	 */
	function hasAmount() {
		return (int)$this->getAmount() != '0';
	}
	
	function isChanged() {
		return $this->isChanged;
	}
		
	/**
	 * @param string $locale
	 */
	function setLocale($locale) {
		$this->locale = $locale;
		$this->currencyLib->setLocale($locale);
	}
	
	/**
	 * @return string
	 */
	function getLocale() {
		return ($this->locale) ? $this->locale : i18n::get_locale();
	}
	
	/**
	 * @return string
	 */
	function getSymbol($currency = null, $locale = null) {
		
		if($locale === null) $locale = $this->getLocale();
		if($currency === null) $currency = $this->getCurrency();
		
		return $this->currencyLib->getSymbol($currency, $locale);
	}
	
	/**
	 * @return string
	 */
	function getShortName($currency = null, $locale = null) {
		if($locale === null) $locale = $this->getLocale();
		if($currency === null) $currency = $this->getCurrency();
		
		return $this->currencyLib->getShortName($currency, $locale);
	}
	
	/**
	 * @return string
	 */
	function getName($currency = null, $locale = null) {
		if($locale === null) $locale = $this->getLocale();
		if($currency === null) $currency = $this->getCurrency();
		
		return $this->currencyLib->getName($currency, $locale);
	}
	
	/**
	 * @param array $arr
	 */
	function setAllowedCurrencies($arr) {
		$this->allowedCurrencies = $arr;
	}
	
	/**
	 * @return array
	 */
	function getAllowedCurrencies() {
		return $this->allowedCurrencies;
	}
	
	/**
	 * Returns a CompositeField instance used as a default
	 * for form scaffolding.
	 *
	 * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
	 * 
	 * @param string $title Optional. Localized title of the generated instance
	 * @return FormField
	 */
	public function scaffoldFormField($title = null) {
		$field = new MoneyField($this->name);
		$field->setAllowedCurrencies($this->getAllowedCurrencies());
		$field->setLocale($this->getLocale());
		
		return $field;
	}
	
	/**
	 * For backwards compatibility reasons
	 * (mainly with ecommerce module),
	 * this returns the amount value of the field,
	 * rather than a {@link Nice()} formatting.
	 */
	function __toString() {
		return (string)$this->getAmount();
	}
}
?>