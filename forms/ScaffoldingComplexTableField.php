<?php
/**
 * Used by ModelAdmin scaffolding, to manage many-many relationships. 
 *
 * @package forms
 * @subpackage fields-relational
 */
class ScaffoldingComplexTableField_Popup extends ComplexTableField_Popup {
	
	public static $allowed_actions = array(
		'filter', 'record', 'httpSubmission', 'handleAction', 'handleField'
	);

	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		$this->dataObject = $dataObject;
		
		Requirements::clear();

		$actions = new FieldSet();	
		if(!$readonly) {
			$actions->push(
				$saveAction = new FormAction("saveComplexTableField", "Save")
			);	
			$saveAction->addExtraClass('save');
		}
		
		$fields->push(new HiddenField("ComplexTableField_Path", Director::absoluteBaseURL()));
		
		parent::__construct($controller, $name, $fields, $validator, $readonly, $dataObject);
	}
	
	/**
	 * Handle a generic action passed in by the URL mapping.
	 *
	 * @param SS_HTTPRequest $request
	 */
	public function handleAction($request) {
		$action = str_replace("-","_",$request->param('Action'));
		if(!$this->action) $this->action = 'index';
		
		if($this->checkAccessAction($action)) {
			if($this->hasMethod($action)) {
				$result = $this->$action($request);
			
				// Method returns an array, that is used to customise the object before rendering with a template
				if(is_array($result)) {
					return $this->getViewer($action)->process($this->customise($result));
				
				// Method returns a string / object, in which case we just return that
				} else {
					return $result;
				}
			
			// There is no method, in which case we just render this object using a (possibly alternate) template
			} else {
				return $this->getViewer($action)->process($this);
			}
		} else {
			return $this->httpError(403, "Action '$action' isn't allowed on class $this->class");
		}		
	}
	
	/**
	 * Action to render results for an autocomplete filter.
	 *
	 * @param SS_HTTPRequest $request
	 * @return void
	 */	
	function filter($request) {
		//$model = singleton($this->modelClass);
		$context = $this->dataObject->getDefaultSearchContext();
		$value = $request->getVar('q');
		$results = $context->getResults(array("Name"=>$value));
		header("Content-Type: text/plain");
		foreach($results as $result) {
			echo $result->Name . "\n";
		}		
	}
	
	/**
	 * Action to populate edit box with a single data object via Ajax query
	 */
	function record($request) {
		$type = $request->getVar('type');
		$value = $request->getVar('value');
		if ($type && $value) {
			$record = DataObject::get_one($this->dataObject->class, "\"$type\" = '$value'");
			header("Content-Type: text/plain");
			echo json_encode(array("record"=>$record->toMap()));
		}
	}

}
?>