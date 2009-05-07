<?php
/**
 * Partially based on Zend_Currency.
 *
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd	 New BSD License
 * @version   $Id: Currency.php 6137 2007-08-19 14:55:27Z shreef $
 */

set_include_path(get_include_path() . PATH_SEPARATOR . BASE_PATH . '/sapphire/thirdparty');
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
		"Currency" => "Varchar(3) null",
		"Amount" => Array('type'=>'decimal', 'parts'=>Array('datatype'=>'decimal', 'precision'=>"14,2"), 'default' => '0'),
	);
	
	function __construct($name = null) {
		$this->currencyLib = new Zend_Currency(null, i18n::default_locale());
		
		parent::__construct($name);
	}

	public function composite_db(){
		return self::$composite_db;
	}

	function requireField() {
		$composite_db = $this->composite_db();
		foreach($composite_db as $name => $type){
			DB::requireField($this->tableName, $this->name.$name, $type);
		}
	}

	function writeToManipulation(&$manipulation) {
		$manipulation['fields'][$this->name.'Currency'] = $this->prepValueForDB($this->getCurrency());
		$manipulation['fields'][$this->name.'Amount'] = $this->getAmount();
	}

	function setValue($value,$record=null) {
		if($record && isset($record[$this->name . 'Currency']) && isset($record[$this->name . 'Amount'])) {
			if($record[$this->name . 'Currency'] && $record[$this->name . 'Amount']) {
				$this->setCurrency($record[$this->name . 'Currency']);
				$this->setAmount($record[$this->name . 'Amount']);
			} else {
				$this->value = $this->nullValue();
			}
		} elseif ($value instanceof Money) {
			$this->setCurrency($value->getCurrency());
			$this->setAmount($value->getAmount());
		} else if (is_array($value)) {
			if (array_key_exists('Currency', $value)) {
				$this->setCurrency($value['Currency']);
				$this->isChanged = true;
			}
			if (array_key_exists('Amount', $value)) {
				$this->setAmount($value['Amount']);
				$this->isChanged = true;
			}
		} else {
			user_error('Invalid value in Money->setValue()', E_USER_ERROR);
		}
	}

	/**
	 * @return string
	 */
	function Nice($options = array()) {
		return $this->currencyLib->toCurrency($this->getAmount(), $options);
	}
	
	/**
	 * @return string
	 */
	function NiceWithShortname($options = array()){
		$shortName = $this->getShortName();
		$symbol = $this->getSymbol();
		return $shortName."(".$symbol.")"." ".$this->getAmount();
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
	function setCurrency($currency) {
		$this->currency = $currency;
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
	function setAmount($amount) {
		$this->amount = (float)$amount;
	}
	
	/**
	 * @return boolean
	 */
	function hasValue() {
		return ($this->getCurrency() && is_numeric($this->getAmount()));
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
	 * @todo Implement this
	 */
	function toString() {
		
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
		$fieldAmount = new NumericField($this->name."Amount", 'Amount');

		$allowedCurrencies = $this->getAllowedCurrencies();
		if($allowedCurrencies) {
			$fieldCurrency = new DropdownField(
				$this->name."Currency", 
				'Currency',
				array_combine($allowedCurrencies,$allowedCurrencies)
			);
		} else {
			$fieldCurrency = new TextField($this->name."Currency", 'Currency');
		}
		
		$field = new FieldGroup(
			$fieldAmount,
			$fieldCurrency
		);
		
		$field->setID ($this->name);
		
		return $field;
	}
}
?>