<?php
/**
 * A form field that can save into a {@link Money} database field.
 * See {@link CurrencyField} for a similiar implementation 
 * that can save into a single float database field without indicating the currency.
 * 
 * @author Ingo Schommer, SilverStripe Ltd. (<firstname>@silverstripe.com)
 * 
 * @package framework
 * @subpackage fields-formattedinput
 */
class MoneyField extends FormField {
	
	/**
	 * @var string $_locale
	 */
	protected $_locale;
	
	/**
	 * Limit the currencies
	 * @var array $allowedCurrencies
	 */
	protected $allowedCurrencies;
	
	/**
	 * @var FormField
	 */
	protected $fieldAmount = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldCurrency = null;
	
	public function __construct($name, $title = null, $value = "") {
		// naming with underscores to prevent values from actually being saved somewhere
		$this->fieldAmount = new NumericField("{$name}[Amount]", _t('MoneyField.FIELDLABELAMOUNT', 'Amount'));
		$this->fieldCurrency = $this->FieldCurrency($name);
		
		parent::__construct($name, $title, $value);
	}
	
	/**
	 * @return string
	 */
	public function Field($properties = array()) {
		return "<div class=\"fieldgroup\">" .
			"<div class=\"fieldgroupField\">" . $this->fieldCurrency->SmallFieldHolder() . "</div>" . 
			"<div class=\"fieldgroupField\">" . $this->fieldAmount->SmallFieldHolder() . "</div>" . 
		"</div>";
	}
	
	/**
	 * @param string $name - Name of field
	 * @return FormField
	 */
	protected function FieldCurrency($name) {
		$allowedCurrencies = $this->getAllowedCurrencies();
		if($allowedCurrencies) {
			$field = new DropdownField(
				"{$name}[Currency]", 
				_t('MoneyField.FIELDLABELCURRENCY', 'Currency'),
				ArrayLib::is_associative($allowedCurrencies)
					? $allowedCurrencies
					: array_combine($allowedCurrencies,$allowedCurrencies)
			);
		} else {
			$field = new TextField(
				"{$name}[Currency]", 
				_t('MoneyField.FIELDLABELCURRENCY', 'Currency')
			);
		}
		
		return $field;
	}
	
	public function setValue($val) {
		$this->value = $val;

		if(is_array($val)) {
			$this->fieldCurrency->setValue($val['Currency']);
			$this->fieldAmount->setValue($val['Amount']);
		} elseif($val instanceof Money) {
			$this->fieldCurrency->setValue($val->getCurrency());
			$this->fieldAmount->setValue($val->getAmount());
		}
		
		// @todo Format numbers according to current locale, incl.
		//  decimal and thousands signs, while respecting the stored
		//  precision in the database without truncating it during display
		//  and subsequent save operations

		return $this;
	}
	
	/**
	 * 30/06/2009 - Enhancement: 
	 * SaveInto checks if set-methods are available and use them 
	 * instead of setting the values in the money class directly. saveInto
	 * initiates a new Money class object to pass through the values to the setter
	 * method.
	 *
	 * (see @link MoneyFieldTest_CustomSetter_Object for more information)
	 */
	public function saveInto(DataObjectInterface $dataObject) {
		$fieldName = $this->name;
		if($dataObject->hasMethod("set$fieldName")) {
			$dataObject->$fieldName = DBField::create_field('Money', array(
				"Currency" => $this->fieldCurrency->Value(),
				"Amount" => $this->fieldAmount->Value()
			));
		} else {
			$dataObject->$fieldName->setCurrency($this->fieldCurrency->Value()); 
			$dataObject->$fieldName->setAmount($this->fieldAmount->Value());
		}
	}

	/**
	 * Returns a readonly version of this field.
	 */
	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
	
	/**
	 * @todo Implement removal of readonly state with $bool=false
	 * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
	 */
	public function setReadonly($bool) {
		parent::setReadonly($bool);
		
		$this->fieldAmount->setReadonly($bool);
		$this->fieldCurrency->setReadonly($bool);

		return $this;
	}

	public function setDisabled($bool) {
		parent::setDisabled($bool);
		
		$this->fieldAmount->setDisabled($bool);
		$this->fieldCurrency->setDisabled($bool);

		return $this;
	}
	
	/**
	 * @param array $arr
	 */
	public function setAllowedCurrencies($arr) {
		$this->allowedCurrencies = $arr;
		
		// @todo Has to be done twice in case allowed currencies changed since construction
		$oldVal = $this->fieldCurrency->Value();
		$this->fieldCurrency = $this->FieldCurrency($this->name);
		$this->fieldCurrency->setValue($oldVal);

		return $this;
	}
	
	/**
	 * @return array
	 */
	public function getAllowedCurrencies() {
		return $this->allowedCurrencies;
	}
	
	public function setLocale($locale) {
		$this->_locale = $locale;
		return $this;
	}
	
	public function getLocale() {
		return $this->_locale;
	}
}
