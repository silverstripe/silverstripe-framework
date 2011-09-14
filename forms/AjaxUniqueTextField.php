<?php
/**
 * Text field that automatically checks that the value entered is unique for the given
 * set of fields in a given set of tables
 * @package forms
 * @subpackage fields-formattedinput
 */
class AjaxUniqueTextField extends TextField {
	
	protected $restrictedField;
	protected $restrictedTable;
	// protected $restrictedMessage;
	protected $validateURL;
	
	protected $restrictedRegex;
	
	function __construct($name, $title, $restrictedField, $restrictedTable, $value = "", $maxLength = null, $validationURL = null, $restrictedRegex = null ){
		$this->maxLength = $maxLength;
		
		$this->restrictedField = $restrictedField;
		
		$this->restrictedTable = $restrictedTable;
		
		$this->validateURL = $validationURL;
		
		$this->restrictedRegex = $restrictedRegex;
		
		parent::__construct($name, $title, $value);	
	}
	 
	function Field() {
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/UniqueFields.js");
		
		$this->jsValidation();
		
		$url = Convert::raw2att( $this->validateURL );
		
		if($this->restrictedRegex)
			$restrict = "<input type=\"hidden\" class=\"hidden\" name=\"{$this->name}Restricted\" id=\"" . $this->id() . "RestrictedRegex\" value=\"{$this->restrictedRegex}\" />";
		
		$attributes = array(
			'type' => 'text',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->Value(),
			'tabindex' => $this->getTabIndex(),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null
		);
		
		return $this->createTag('input', $attributes);
	}

	function jsValidation() {
		$formID = $this->form->FormName();
		$id = $this->id();
		$url = Director::absoluteBaseURL() . $this->validateURL;

		if($this->restrictedRegex) {
			$jsCheckFunc = <<<JS
Element.removeClassName(this, 'invalid');
var match = this.value.match(/{$this->restrictedRegex}/);
if(match) {
	Element.addClassName(this, 'invalid');
	return false;
}

return true;	
JS;
		} else {
			$jsCheckFunc = "return true;";
		}

		$jsFunc = <<<JS
Behaviour.register({
	'#$id' : {
		onkeyup: function() {
			var self = this;
			if(this.checkValid()) {
				jQuery.ajax({
					'url': '{$url}?ajax=1&{$this->name}=' + encodeURIComponent(this.value),
					method: 'get',
					success: function(response) {
						if(response.responseText == 'ok')
							Element.removeClassName(self, 'inuse');
						else {
							Element.addClassName(self, 'inuse');	
						}
					}
					error: function(response) {
					
					}	
				});
			}
		},
		
		checkValid: function() {
			$jsCheckFunc
		}
	} 
});
JS;
		Requirements::customScript($jsFunc, 'func_validateAjaxUniqueTextField');

		//return "\$('$formID').validateCurrency('$this->name');";

	}

	function validate( $validator ) {
		
		$result = DB::query(sprintf(
			"SELECT COUNT(*) FROM \"%s\" WHERE \"%s\" = '%s'",
			$this->restrictedTable,
			$this->restrictedField,
			Convert::raw2sql($this->value)
		))->value();

		if( $result && ( $result > 0 ) ) {
			$validator->validationError( $this->name, _t('Form.VALIDATIONNOTUNIQUE', "The value entered is not unique") );
			return false;
		}

		return true; 
	}
}
?>