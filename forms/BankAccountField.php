<?php
/**
 * Field for displaying bank account numbers. It separates the bank, branch, account-number and account-suffix.
 * @package forms
 * @subpackage fields-formattedinput
 */
class BankAccountField extends FormField {
	
	protected $bankCode;
	protected $branchCode;
	
	/**
	 * @var array $valueArr Stores value in associative array-format, with:
	 * BankCode, BranchCode, AccountNumber, AccountSuffix
	 */
	protected $valueArr = array();
	
	/**
	 * HACK Proper requiring of compositefields would involve serious restructuring.
	 */
	public $isRequired = false;
	public $requiredFields = array(
		"BankCode",
		"BranchCode",
		"AccountNumber",
	);
	
	public function __construct($name, $title, $value = null, $bankCode = null, $branchCode = null, $form = null) {
		
		$this->bankCode = $bankCode;
		$this->branchCode = $branchCode;
		
		// needs to be passed through setValue() to populate $valueArr
		if($value) $this->setValue($value);
		
		parent::__construct($name, $title, $value, $form);
	}
	
	public function Field() {
		$field = new FieldGroup($this->name);
		$field->setID("{$this->name}_Holder");
		
		$valueArr = $this->valueArr;
		
		$valueArr = self::convert_format_nz($valueArr);
		
		$field->push($n1 = new NumericField($this->name.'[BankCode]', '', $valueArr['BankCode'], 2));
		$field->push($n2 = new NumericField($this->name.'[BranchCode]', '', $valueArr['BranchCode'], 4));
		$field->push($n3 = new NumericField($this->name.'[AccountNumber]', '', $valueArr['AccountNumber'], 8));
		$field->push($n4 = new NumericField($this->name.'[AccountSuffix]', '', $valueArr['AccountSuffix'], 3));
		if($this->tabIndex) {
			$n1->setTabIndex($this->getTabIndex());
			$n2->setTabIndex($this->getTabIndex()+1);
			$n3->setTabIndex($this->getTabIndex()+2);
			$n4->setTabIndex($this->getTabIndex()+3);
		}
		
		return $field;
	}
	
	public function setValue($value) {
		$this->value = self::join_bank_number($value);
		if($this->value) {
			$this->valueArr = array();
			list(
				$this->valueArr['BankCode'], 
				$this->valueArr['BranchCode'], 
				$this->valueArr['AccountNumber'], 
				$this->valueArr['AccountSuffix']
			) = explode(" ",$this->value);
		}
	}
	
	/**
	 * Checks for a valid array structure.
	 *
	 * @param array $arr
	 * @return boolean
	 */
	protected static function is_valid_array_structure($arr) {
		return (
			is_array($arr)
			&& array_key_exists('BankCode', $arr)
			&& array_key_exists('BranchCode', $arr)
			&& array_key_exists('AccountNumber', $arr)
			&& array_key_exists('AccountSuffix', $arr)
		);
	}
	
	/**
	 * @return string
	 */
	function getBankCode() {
		return $this->valueArr['BankCode'];
	}
	
	/**
	 * @return string
	 */
	function getBranchCode() {
		return $this->valueArr['BranchCode'];
	}
	
	/**
	 * @return string
	 */
	function getAccountNumber() {
		return $this->valueArr['AccountNumber'];
	}
	
	/**
	 * @return string
	 */
	function getAccountSuffix() {
		return $this->valueArr['AccountSuffix'];
	}
	
	/**
	 * Makes sure the number is a string with spaces instead of hyphens,
	 * and adjusts to new format (2-4-8-3) by using conver_format_nz().
	 * 
	 * @param mixed $value String- or Array-representation of full bank-account-number.
	 * @return string
	 */
	public static function join_bank_number($value) {
		if(is_array($value)) {
			$value = self::convert_format_nz($value);
			$completeNumber = "";
			if($value['BankCode']) {
				$completeNumber .= $value['BankCode'] . " ";
			}
			if($value['BranchCode']) {
				$completeNumber .= $value['BranchCode'] . " ";
			}
			if($value['AccountNumber']) { 
				$completeNumber .= $value['AccountNumber'] . " ";
			}
			if($value['AccountSuffix']) {
				$completeNumber .= $value['AccountSuffix'];
			}
			return $completeNumber;
		} else
			return $value;
	}
	
	/**
	 * @todo Very basic validation at the moment
	 */
	function jsValidation() {
		$formID = $this->form->FormName();
		
		$jsRequired = "";
		if($this->isRequired && $this->requiredFields) {
			foreach($this->requiredFields as $requiredFieldName) {
				$name = $this->Name() . "-{$requiredFieldName}";
				$jsRequired .= "require('$name')\n";
			}
		}
		$error = _t('BankAccountField.VALIDATIONJS', 'Please enter a valid bank number');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateBankNumber: function(fieldName) {
			if(!$(fieldName + "_Holder")) return true;

			// Phonenumbers are split into multiple values, so get the inputs from the form.
			var parts = $(fieldName + "_Holder").getElementsByTagName('input');
			var isNull = true;
			
			// we're not validating empty fields (done by requiredfields)
			for(i=0; i < parts.length ; i++ ) {
				isNull = (parts[i].value == null || parts[i].value == "") ? isNull && true : false;
			}
			
			if(!isNull) {
				// Concatenate the string values from the parts of the input.
				var joinedNumber = ""; 
				for(i=0; i < parts.length; i++) joinedNumber += parts[i].value;
				if(!joinedNumber.match(/^[\d]{2}[\s]*[\d]{4}[\s]*[\d]{7,8}[\s]*[\d]{2,3}\$/)) {
					// TODO Find a way to mark multiple error fields
					validationError(
						fieldName+"-AccountNumber",
						"$error",
						"validation",
						false
					);
				}
			}
			$jsRequired
			return true;			
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validateBankNumber');
		
		return "\$('$formID').validateBankNumber('$this->name');";
	}
	
	/**
	 * @todo Very basic validation at the moment
	 */
	function validate($validator){
		$valid = preg_match(
			'/^[\d]{2}[\s]*[\d]{4}[\s]*[\d]{7,8}[\s]*[\d]{2,3}$/',
			self::join_bank_number($this->value)
		);
		
		if(!$valid){
			$validator->validationError(
				$this->name, 
				_t('Form.VALIDATIONBANKACC', "Please enter a valid bank number"),
				"validation", 
				false
			);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Convert from old format (2-4-7-2) to new format (2-4-8-3).
	 * 
	 * @param $value array BankCode, BranchCode, AccountNumber, AccountSuffix
	 * @return array
	 */
	static function convert_format_nz($value) {
		if(is_string($value)) {
			list(
				$valueArr['BankCode'], 
				$valueArr['BranchCode'], 
				$valueArr['AccountNumber'], 
				$valueArr['AccountSuffix']
			) = explode(" ",$value);
		} elseif(self::is_valid_array_structure($value)) {
			$valueArr = $value;
		} else {
			return false;
		}
		
		if(strlen(trim($valueArr['AccountNumber'])) == 7) {
			$valueArr['AccountNumber'] = str_pad($valueArr['AccountNumber'],8,"0",STR_PAD_LEFT);
		}
		if(strlen(trim($valueArr['AccountSuffix'])) == 2) {
			$valueArr['AccountSuffix'] = str_pad($valueArr['AccountSuffix'],3,"0",STR_PAD_LEFT);
		}
		return $valueArr;
	}
	
}
?>