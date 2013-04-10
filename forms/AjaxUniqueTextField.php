<?php
/**
 * Text field that automatically checks that the value entered is unique for the given
 * set of fields in a given set of tables
 * @package forms
 * @subpackage fields-formattedinput
 */
class AjaxUniqueTextField extends TextField {
	
	/**
	 * Callback to the data list provider method, used to provide the submitted data.
	 * See call_user_func() for syntax.
	 * @var callable
	 */	
	protected $listProvider;

	/**
	 * Data list containing data to be validated, populated using setList(<DataList>)
	 * @var DataList
	 */	
	protected $list;
		
	protected $restrictedField;
	
	protected $validateURL;
	
	protected $restrictedRegex;
	
	public function __construct($name, $title, $restrictedField = null, $restrictedTable = null, $value = '', $maxLength = null,
			$validationURL = null, $restrictedRegex = null ){

		$this->restrictedField = $restrictedField;

		if (!is_null($restrictedTable)) {
			Deprecation::notice('3.2', 'Use the "setListProvider(<callable>)" method instead of restictedTable');
			$list = new DataList($restrictedTable);
			$this->setList($list);
		}

		$this->validateURL = $validationURL;
		
		$this->restrictedRegex = $restrictedRegex;
		
		parent::__construct($name, $title, $value, $maxLength);	
	}
	
	public function Field($properties = array()) {
		$url = Convert::raw2att( $this->validateURL );
		
		if($this->restrictedRegex)
			$restrict = "<input type=\"hidden\" class=\"hidden\" name=\"{$this->name}Restricted\" id=\"" . $this->id()
				. "RestrictedRegex\" value=\"{$this->restrictedRegex}\" />";
		
		$attributes = array(
			'type' => 'text',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->getName(),
			'value' => $this->Value(),
			'tabindex' => $this->getAttribute('tabindex'),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null
		);
		
		return FormField::create_tag('input', $attributes);
	}

	/**
	 * Sets the dataList to be validated
	 * Example (from DataObject):
	 * public function DataListProvider($formField) {
	 * 		$list = new DataList('SiteTree');
	 * 		$list = $list->filter('Title', $formField->Value());
	 * 		$list = $list->exclude('ID', $this->ID);
	 * 		$formField->setList($list);
	 * 	}
	 * 
	 * @param DataList $list
	 */
	public function setList(DataList $list) {
		$this->list = $list;
	}

	/**
	 * Sets the callback that will provide the DataList
	 * @param callable $listProvider
	 * Example (from DataObject):
	 * $ajaxutf = new AjaxUniqueTextField('UniqueTitle','Unique Title');
	 * $ajaxutf->setListProvider(array($this,'DataListProvider'));
	 * $fields->insertBefore($ajaxutf, 'Content');
	 * 
	 */
	public function setListProvider($listProvider) {
		$this->listProvider = $listProvider;
	}

	/**
	 * @return callable
	 */
	public function getListProvider() {
		return $this->listProvider;
	}
 
	/**
	 * @return DataList
	 */
	public function getList() {
		return $this->list;
	}

	function validate( $validator ) {
		if (!is_null($this->getListProvider())) {
			call_user_func($this->getListProvider(), $this);
		}

		$list = $this->getList();

		if (is_null($this->getListProvider())) {
			$list = $list->filter($this->restrictedField, $this->Value());
		}
		if (is_null($list)) {
			user_error("Missing required DataList. Please supply a datalist using the setList() method.", E_USER_ERROR);
			return false;
		}

		if( $list->count() > 0 ) {
			$validator->validationError($this->name,	_t('Form.VALIDATIONNOTUNIQUE', "The value entered is not unique"));
			return false;
		}

		return true; 
	}
}
