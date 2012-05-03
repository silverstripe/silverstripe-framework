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
	 
	function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . "/prototype/prototype.js");
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour/behaviour.js");
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		Requirements::javascript(FRAMEWORK_DIR . "/javascript/UniqueFields.js");
		
		$url = Convert::raw2att( $this->validateURL );
		
		if($this->restrictedRegex)
			$restrict = "<input type=\"hidden\" class=\"hidden\" name=\"{$this->name}Restricted\" id=\"" . $this->id() . "RestrictedRegex\" value=\"{$this->restrictedRegex}\" />";
		
		$attributes = array(
			'type' => 'text',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->getName(),
			'value' => $this->Value(),
			'tabindex' => $this->getAttribute('tabindex'),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null
		);
		
		return $this->createTag('input', $attributes);
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
