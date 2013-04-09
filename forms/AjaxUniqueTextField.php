<?php
/**
 * Text field that automatically checks that the value entered is unique for the given
 * set of fields in a given set of tables
 * @package forms
 * @subpackage fields-formattedinput
 */
class AjaxUniqueTextField extends TextField {
	
	protected $restrictedField;
	protected $restrictedRegex;
	protected $restrictedTable;
	protected $validationURL;
	
	
	public function __construct($name, $title, $restrictedField, $restrictedTable, $value = '', $maxLength = null,
			$validationURL = null, $restrictedRegex = null ){

		$this->setRestrictedField($restrictedField);
		$this->setRestrictedTable($restrictedTable);
		$this->setValidationURL($validationURL);
		$this->setRestrictedRegex($restrictedRegex);
		
		parent::__construct($name, $title, $value, $maxLength);	
	}
	
	public function Field($properties = array()) {
		$url = Convert::raw2att( $this->getValidationURL() );
		
		if($this->getRestrictedRegex())
			$restrict = "<input type=\"hidden\" class=\"hidden\" name=\"{$this->name}Restricted\" id=\"" . $this->id()
				. "RestrictedRegex\" value=\"{$this->getRestrictedRegex()}\" />";
		
		$attributes = array(
			'type' => 'text',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->getName(),
			'value' => $this->Value(),
			'tabindex' => $this->getAttribute('tabindex'),
			'maxlength' => ($this->getMaxLength()) ? $this->getMaxLength() : null
		);
		
		return FormField::create_tag('input', $attributes);
	}

	public function getRestrictedField() {
		return $this->restrictedField;
	}

	public function getRestrictedTable() {
		return $this->restrictedTable;
	}

	public function getRestrictedRegex() {
		return $this->restrictedRegex;
	}

	public function getValidationURL() {
		return $this->validationURL;
	}

	public function setRestrictedField($restrictedField) {
		/**
		 *  The name of the database field you want force uniqueness on. e.g "Title"
		 * @var string
		 */
		$this->restrictedField = $restrictedField;
	}

	public function setRestrictedRegex($restrictedRegex) {
		/**
		 * Optional regex that must pass for validation to pass.
		 * @var string
		 */
		$this->restrictedRegex = $restrictedRegex;
	}

	public function setRestrictedTable($restrictedTable) {
		/**
		 *  The name of the database table that restrictedField exists in, e.g "SiteTree" 
		 * @var string
		 */
		$this->restrictedTable = $restrictedTable;
	}

	public function setValidationURL($validationURL) {
		/**
		 *  Optional, callback url, e.g "home/validate/foo" which returns a bool. Must be true for validation to pass. 
		 * @var string
		 */
		$this->validationURL = $validationURL;
	}

	public function validate( $validator ) {
		$result = DB::query(sprintf(
			"SELECT COUNT(*) FROM \"%s\" WHERE \"%s\" = '%s'",
			$this->getRestrictedTable(),
			$this->getRestrictedField(),
			Convert::raw2sql($this->value)
		))->value();

		if( $result && ( $result > 0 ) ) {
			$validator->validationError($this->name,
				_t('Form.VALIDATIONNOTUNIQUE', "The value entered is not unique"));
			return false;
		}

		return true; 
	}
}
