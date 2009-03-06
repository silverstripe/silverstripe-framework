<?php
/**
 * Used by ModelAdmin scaffolding, to manage many-many relationships. 
 *
 * @package forms
 * @subpackage fields-relational
 */
class ScaffoldingComplexTableField_Popup extends Form {
	protected $sourceClass;
	protected $dataObject;
	
	public static $allowed_actions = array(
		'filter', 'record', 'httpSubmission', 'handleAction', 'handleField'
	);

	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		$this->dataObject = $dataObject;

		/**
		 * WARNING: DO NOT CHANGE THE ORDER OF THESE JS FILES
		 * Some have special requirements.
		 */
		//Requirements::css(CMS_DIR . 'css/layout.css');
		Requirements::css(SAPPHIRE_DIR . '/css/Form.css');
		Requirements::css(SAPPHIRE_DIR . '/css/ComplexTableField_popup.css');
		Requirements::css(CMS_DIR . '/css/typography.css');
		Requirements::css(CMS_DIR . '/css/cms_right.css');
		Requirements::css(THIRDPARTY_DIR . '/jquery/plugins/autocomplete/jquery.ui.autocomplete.css');
		Requirements::javascript(THIRDPARTY_DIR . "/prototype.js");
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/prototype_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/scriptaculous.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/controls.js");
		Requirements::javascript(THIRDPARTY_DIR . "/layout_helpers.js");
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(CMS_DIR . "/javascript/LeftAndMain.js");
		Requirements::javascript(CMS_DIR . "/javascript/LeftAndMain_right.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/TableField.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ComplexTableField.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ComplexTableField_popup.js");
		// jQuery requirements (how many of these are actually needed?)
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/plugins/livequery/jquery.livequery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/ui/ui.core.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/ui/ui.tabs.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/plugins/form/jquery.form.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/plugins/dimensions/jquery.dimensions.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/plugins/autocomplete/jquery.ui.autocomplete.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/ScaffoldComplexTableField.js');
		Requirements::javascript(CMS_DIR . '/javascript/ModelAdmin.js');
		
 		if($this->dataObject->hasMethod('getRequirementsForPopup')) {
			$this->dataObject->getRequirementsForPopup();
		}
		
		$actions = new FieldSet();	
		if(!$readonly) {
			$actions->push(
				$saveAction = new FormAction("saveComplexTableField", "Save")
			);	
			$saveAction->addExtraClass('save');
		}
		
		$fields->push(new HiddenField("ComplexTableField_Path", Director::absoluteBaseURL()));
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	function FieldHolder() {
		return $this->renderWith('ComplexTableField_Form');
	}
	
	
	/**
	 * Handle a generic action passed in by the URL mapping.
	 *
	 * @param HTTPRequest $request
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
	 * @param HTTPRequest $request
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
			$record = DataObject::get_one($this->dataObject->class, "$type = '$value'");
			header("Content-Type: text/plain");
			echo json_encode(array("record"=>$record->toMap()));
		}
	}

}
?>