<?php
/**
 * Date field that provides 3 dropdowns for entering a date
 * @package forms
 * @subpackage fields-datetime
 */
class CompositeDateField extends DateField {

	function __construct($name, $title, $value = null, $yearRange = null){
		$exploded = explode('-', $value); 
		$year = isset($exploded[0]) ? $exploded[0] : null; 
		$month = isset($exploded[1]) ? $exploded[1] : null; 
		$date = isset($exploded[2]) ? $exploded[2] : null; 
		
		$this->dateDropdown = new DropdownField($name."[date]", "",
			array('NotSet' => '('._t('CompositeDateField.DAY', 'Day').')',
						'01'=>'01', '02'=>'02', '03'=>'03', '04'=>'04', '05'=>'05',
						'06'=>'06', '07'=>'07', '08'=>'08', '09'=>'09', '10'=>'10',
						'11'=>'11', '12'=>'12', '13'=>'13', '14'=>'14', '15'=>'15',
						'16'=>'16', '17'=>'17', '18'=>'18', '19'=>'19', '20'=>'20',
						'21'=>'21', '22'=>'22', '23'=>'23', '24'=>'24', '25'=>'25',
						'26'=>'26', '27'=>'27', '28'=>'28', '29'=>'29', '30'=>'30',
						'31'=>'31'
			),
			$date
		);

		$this->monthDropdown = new DropdownField($name."[month]", "",
			array( 'NotSet' => '('._t('CompositeDateField.MONTH', 'Month').')',
						'01'=>'01', '02'=>'02', '03'=>'03', '04'=>'04', '05'=>'05',
						'06'=>'06', '07'=>'07', '08'=>'08', '09'=>'09', '10'=>'10',
						'11'=>'11', '12'=>'12'
			),
			$month
		);
		
		if($yearRange == null){
			$this->customiseYearDropDown($name, "1995-2012", $year);
		}else{
			$this->customiseYearDropDown($name, $yearRange, $year);
		}	
		parent::__construct($name, $title);
	}
	
	function Field() {
		$this->dateDropdown->setTabIndex($this->getTabIndex());
		$this->monthDropdown->setTabIndex($this->getTabIndex()+1);
		$this->yearDropdown->setTabIndex($this->getTabIndex()+2);
		return $this->dateDropdown->Field() . $this->monthDropdown->Field() . $this->yearDropdown->Field();
	}

	
	function performReadonlyTransformation() {
		$field = new CompositeDateField_Disabled($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		return $field;
	}

	function customiseYearDropDown($name, $yearRange, $year){
		list($from,$to) = explode('-', $yearRange);
		$source['NotSet'] = '(Year)';
		for($i = $to; $i >= $from; $i--){
			$source[$i]=$i;
		}
		$this->yearDropdown = new DropdownField($name."[year]", "", $source, $year);
		$this->yearDropdown->setValue($year);
	}
	
	function setForm($form) {
		$this->dateDropdown->setForm($form);
		$this->monthDropdown->setForm($form);
		$this->yearDropdown->setForm($form);
		parent::setForm($form);
	}
	
	function setValue($val) {
		if($val) {
			if(is_array($val)){
				if($val['date'] == 'NotSet' || $val['month'] == 'NotSet' || $val['year'] == 'NotSet'){
					$this->value = null; return;
				}
				$val = $val['year'] . '-' . $val['month'] . '-' . $val['date'];
			}
			$this->value = date('d/m/Y', strtotime($val));
			list($year, $month, $date) = explode("-", $val);
			$this->yearDropdown->setValue($year);
			$this->monthDropdown->setValue($month);
			$this->dateDropdown->setValue($date);
				
		} else {
			$this->value = null;
		}
	}
	
	function jsValidation() {
		$formID = $this->form->FormName();
		$error1 = _t('CompositeDateField.VALIDATIONJS1', 'Please ensure you have set the');
		$error2 = _t('CompositeDateField.VALIDATIONJS2', 'correctly.');
		$day = _t('CompositeDateField.DAYJS', 'day');
		$month = _t('CompositeDateField.MONTHJS', 'month');
		$year = _t('CompositeDateField.YEARJS', 'year');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateCompositeDateField: function(fieldName) {
			var el = _CURRENT_FORM.elements[fieldName];
			if(!el || !el.value) return true;
		
			// Creditcards are split into multiple values, so get the inputs from the form.
			dateParts = $(fieldName).getElementsByTagName('select');
			
			// Concatenate the string values from the parts of the input.
			for(i=0; i < dateParts.length ; i++ ){
				// The default selected value is 'NotSet'
				if(dateParts[i].value == 'NotSet'){
					switch(i){
						case 0: err = "$day"; break;
						case 1: err = "$month"; break;
						case 2: err = "$year"; break;
					}
					validationError(dateParts[i],"$error1 '" + err + "' $error2","validation");
					return false;
				}
			}
			return true;			
		}
	}
});
JS;

		Requirements::customScript($jsFunc, 'func_validateCompositeDateField');

		return "\$('$formID').validateCompositeDateField('$this->name');";
	}
	
	function validate($validator) {
		// TODO Implement server-side validation
		if($this->value == null) {
			$validator->validationError($this->name,_t('Form.VALIDATIONALLDATEVALUES',"Please ensure you have set all date values"),"validation");
			return false;
		} else {
			return true;	
		}
	}
}

/**
 * Allows dates to be represented in a form, by
 * showing in a user friendly format, eg, dd/mm/yyyy.
 * @package forms
 * @subpackage fields-datetime
 */
class CompositeDateField_Disabled extends DateField {
	
	protected $disabled = true;
	
	function setValue($val) {
		if($val && $val != "0000-00-00") $this->value = date('d/m/Y', strtotime($val));
		else $this->value = _t('Form.DATENOTSET', "(No date set)");
	}
	
	function Field() {
		if($this->value) {
			$df = new Date($this->name);
			$df->setValue($this->dataValue());
			$val = Convert::raw2xml($this->value);
		} else {
			$val = '<i>' . _t('Form.NOTSET', '(not set)') . '</i>';
		}
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}
	
	function Type() {
		return "date_disabled readonly";
	}
}
?>