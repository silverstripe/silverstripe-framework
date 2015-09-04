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
 * @package framework
 * @subpackage model
 */
class Money extends CompositeDBField {

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
	private static $composite_db = array(
		"Currency" => "Varchar(3)",
		"Amount" => 'Decimal(19,4)'
	);

	public function __construct($name = null) {
		$this->currencyLib = new Zend_Currency(null, i18n::get_locale());

		parent::__construct($name);
	}

	/**
	 * @return string
	 */
	public function Nice($options = array()) {
		$amount = $this->getAmount();
		if(!isset($options['display'])) $options['display'] = Zend_Currency::USE_SYMBOL;
		if(!isset($options['currency'])) $options['currency'] = $this->getCurrency();
		if(!isset($options['symbol'])) {
			$options['symbol'] = $this->currencyLib->getSymbol($this->getCurrency(), $this->getLocale());
		}
		return (is_numeric($amount)) ? $this->currencyLib->toCurrency($amount, $options) : '';
	}

	/**
	 * @return string
	 */
	public function NiceWithShortname($options = array()){
		$options['display'] = Zend_Currency::USE_SHORTNAME;
		return $this->Nice($options);
	}

	/**
	 * @return string
	 */
	public function NiceWithName($options = array()){
		$options['display'] = Zend_Currency::USE_NAME;
		return $this->Nice($options);
	}

	/**
	 * @return string
	 */
	public function getCurrency() {
		return $this->getField('Currency');
	}

	/**
	 * @param string
	 */
	public function setCurrency($currency, $markChanged = true) {
		$this->setField('Currency', $currency, $markChanged);
	}

	/**
	 * @return float
	 */
	public function getAmount() {
		return $this->getField('Amount');
	}

	/**
	 * @param float $amount
	 */
	public function setAmount($amount, $markChanged = true) {
		$this->setField('Amount', (float)$amount, $markChanged);
	}

	/**
	 * @return boolean
	 */
	public function exists() {
		return ($this->getCurrency() && is_numeric($this->getAmount()));
	}

	/**
	 * @return boolean
	 */
	public function hasAmount() {
		$a = $this->getAmount();
		return (!empty($a) && is_numeric($a));
	}

	/**
	 * @param string $locale
	 */
	public function setLocale($locale) {
		$this->locale = $locale;
		$this->currencyLib->setLocale($locale);
	}

	/**
	 * @return string
	 */
	public function getLocale() {
		return ($this->locale) ? $this->locale : i18n::get_locale();
	}

	/**
	 * @return string
	 */
	public function getSymbol($currency = null, $locale = null) {

		if($locale === null) $locale = $this->getLocale();
		if($currency === null) $currency = $this->getCurrency();

		return $this->currencyLib->getSymbol($currency, $locale);
	}

	/**
	 * @return string
	 */
	public function getShortName($currency = null, $locale = null) {
		if($locale === null) $locale = $this->getLocale();
		if($currency === null) $currency = $this->getCurrency();

		return $this->currencyLib->getShortName($currency, $locale);
	}

	/**
	 * @return string
	 */
	public function getCurrencyName($currency = null, $locale = null) {
		if($locale === null) $locale = $this->getLocale();
		if($currency === null) $currency = $this->getCurrency();

		return $this->currencyLib->getName($currency, $locale);
	}

	/**
	 * @param array $arr
	 */
	public function setAllowedCurrencies($arr) {
		$this->allowedCurrencies = $arr;
	}

	/**
	 * @return array
	 */
	public function getAllowedCurrencies() {
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
		$field = new MoneyField($this->getName());
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
	public function __toString() {
		return (string)$this->getAmount();
	}
}
