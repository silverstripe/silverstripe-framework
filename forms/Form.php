<?php

/**
 * @package forms
 * @subpackage core
 */

/**
 * Base class for all forms.
 * The form class is an extensible base for all forms on a sapphire application.  It can be used
 * either by extending it, and creating processor methods on the subclass, or by creating instances
 * of form whose actions are handled by the parent controller.
 *
 * In either case, if you want to get a form to do anything, it must be inextricably tied to a
 * controller.  The constructor is passed a controller and a method on that controller.  This method
 * should return the form object, and it shouldn't require any arguments.  Parameters, if necessary,
 * can be passed using the URL or get variables.  These restrictions are in place so that we can
 * recreate the form object upon form submission, without the use of a session, which would be too
 * resource-intensive.
 *
 * @package forms
 * @subpackage core
 */
class Form extends ViewableData {
	
	protected $fields;
	
	protected $actions;
	
	protected $controller;
	
	protected $name;

	protected $validator;
	
	protected $formMethod = "post";
	
	public static $backup_post_data = false;
	
	protected static $current_action;

	/**
	 * Keeps track of whether this form has a default action or not.
	 * Set to false by $this->disableDefaultAction();
	 */
	protected $hasDefaultAction = true;

	/**
	 * Variable set to true once the SecurityID hidden field has been added.
	 */
	protected $securityTokenAdded = false;

	/**
	 * Accessed by Form.ss; modified by formHtmlContent.
	 * A performance enhancement over the generate-the-form-tag-and-then-remove-it code that was there previously
	 */
	public $IncludeFormTag = true;

	/**
	 * Create a new form, with the given fields an action buttons.
	 * @param controller The parent controller, necessary to create the appropriate form action tag.
	 * @param name The method on the controller that will return this form object.
	 * @param fields All of the fields in the form - a {@link FieldSet} of {@link FormField} objects.
	 * @param actions All of the action buttons in the form - a {@link FieldSet} of {@link FormAction} objects
	 */
	function __construct($controller, $name, FieldSet $fields, FieldSet $actions, $validator = null) {
		parent::__construct();

		foreach($fields as $field) $field->setForm($this);
		foreach($actions as $action) $actions->setForm($this);

		$this->fields = $fields;
		$this->actions = $actions;
		$this->controller = $controller;
		$this->name = $name;

		// Form validation
		if($validator) {
			$this->validator = $validator;
			$this->validator->setForm($this);
		}

		// Form error controls
		$errorInfo = Session::get("FormInfo.{$this->FormName()}");

		if(isset($errorInfo['errors']) && is_array($errorInfo['errors'])){
			foreach($errorInfo['errors'] as $error){
				$field = $this->fields->dataFieldByName($error['fieldName']);

				if(!$field){
         	$errorInfo['message'] = $error['message'];
					$errorInfo['type'] = $error['messageType'];
				} else {
					$field->setError($error['message'],$error['messageType']);
				}
			}

			// load data in from previous submission upon error
			if(isset($errorInfo['data']))
				$this->loadDataFrom($errorInfo['data']);

		}

		if(isset($errorInfo['message']) && isset($errorInfo['type'])) {
			$this->setMessage($errorInfo['message'],$errorInfo['type']);
		}
	}

	/**
	 * Convert this form into a readonly form
	 */
	function makeReadonly() {
		$this->transform(new ReadonlyTransformation());
	}

	/**
	 * Add an error message to a field on this form.  It will be saved into the session
	 * and used the next time this form is displayed.
	 */
	function addErrorMessage($fieldName, $message, $messageType) {
		Session::addToArray("FormInfo.{$this->FormName()}.errors",  array(
			'fieldName' => $fieldName,
			'message' => $message,
			'messageType' => $messageType,
		));
	}

	function transform(FormTransformation $trans) {
		$newFields = new FieldSet();
		foreach($this->fields as $field) {
			$newFields->push($field->transform($trans));
		}
		$this->fields = $newFields;

		$newActions = new FieldSet();
		foreach($this->actions as $action) {
			$newActions->push($action->transform($trans));
		}
		$this->actions = $newActions;


		// We have to remove validation, if the fields are not editable ;-)
		if($this->validator)
			$this->validator->removeValidation();
	}
	
	/**
	 * Get the {@link Validator} attached to this form.
	 * @return Validator
	 */
	function getValidator() {
		return $this->validator;
	}

	/**
	 * Set the {@link Validator} on this form.
	 */
	function setValidator( Validator $validator ) {
		if($validator) {
			$this->validator = $validator;
			$this->validator->setForm($this);
		}
	}

	/**
	 * Remove the {@link Validator} from this from.
	 */
	function unsetValidator(){
		$this->validator = null;
	}

	/**
	 * Convert this form to another format.
	 */
	function transformTo(FormTransformation $format) {
		$newFields = new FieldSet();
		foreach($this->fields as $field) {
			$newFields->push($field->transformTo($format));
		}
		$this->fields = $newFields;

		// We have to remove validation, if the fields are not editable ;-)
		if($this->validator)
			$this->validator->removeValidation();
	}

	/**
	 * Return the form's fields - used by the templates
	 * @return FieldSet The form fields
	 */
	function Fields() {
		if(!$this->securityTokenAdded && $this->securityTokenEnabled()) {
			if(Session::get('SecurityID')) {
				$securityID = Session::get('SecurityID');
			} else {
				$securityID = rand();
				Session::set('SecurityID', $securityID);
			}
			
			$this->fields->push(new HiddenField('SecurityID', '', $securityID));
			$this->securityTokenAdded = true;
		}
		
		return $this->fields;
	}
	
	/**
	 * Setter for the form fields.
	 *
	 * @param FieldSet $fields
	 */
	function setFields($fields) {
		$this->fields = $fields;
	}
	
	/**
	 * Get a named field from this form's fields.
	 * It will traverse into composite fields for you, to find the field you want.
	 * It will only return a data field.
	 * @return FormField
	 */
	function dataFieldByName($name) {
		return $this->fields->dataFieldByName($name);
	}


	/**
	 * Return the form's action buttons - used by the templates
	 * @return FieldSet The action list
	 */
	function Actions() {
		return $this->actions;
	}

	/**
	 * Setter for the form actions.
	 *
	 * @param FieldSet $actions
	 */
	function setActions($actions) {
		$this->actions = $actions;
	}
	
	/**
	 * Unset all form actions
	 */
	function unsetAllActions(){
		$this->actions = new FieldSet();
	}

	/**
	 * Unset the form's action button by its name
	 */
	function unsetActionByName($name) {
		$action = $this->actions->fieldByName($name);

		$action->unsetthis();
	}

	/**
	 * Unset the form's dataField by its name
	 */
	function unsetDataFieldByName($fieldName){
		//Debug::show($this->Fields()->dataFields());
		foreach($this->Fields()->dataFields() as $child) {
			//Debug::show($child->Name());
			if(is_object($child) && ($child->Name() == $fieldName || $child->Title() == $fieldName)) {
				$child=null;
				/*array_splice($this->Fields()->dataFields(), $i, 1);
				break;*/
			}
		}

	}
	
	/**
	 * Remove a field from the given tab.
	 */
	public function unsetFieldFromTab($tabName, $fieldName) {
		// Find the tab
		$tab = $this->Fields()->findOrMakeTab($tabName);
		$tab->removeByName($fieldName);
	}

	/**
	 * Return the attributes of the form tag - used by the templates
	 * @return string The attribute string
	 */
	function FormAttributes() {
		// Forms shouldn't be cached, cos their error messages won't be shown
		HTTP::set_cache_age(0);

		if($this->validator) $this->validator->includeJavascriptValidation();
		if($this->target) $target = " target=\"".$this->target."\"";
    else $target = "";

    return "id=\"" . $this->FormName() . "\" action=\"" . $this->FormAction()
				. "\" method=\"" . $this->FormMethod() . "\" enctype=\"" . $this->FormEncType() . "\"$target";
	}

  protected $target;
  /**
  * Set the target of this form to any value - useful for opening the form contents in a new window or refreshing another frame
  * @param target The value of the target
  */
  function setTarget($target) {
    $this->target = $target;
  }

	/**
	 * Returns the encoding type of the form.
	 * This will be either multipart/form-data - if there are field fields - or application/x-www-form-urlencoded
	 * @return string The encoding mime type
	 */
	function FormEncType() {
		if(is_array($this->fields->dataFields())){
			foreach($this->fields->dataFields() as $field) {
				if(is_a($field, "FileField")) return "multipart/form-data";
			}
		}
		return "application/x-www-form-urlencoded";
	}
	
	/**
	 * Returns the form method.
	 * @return string 'get' or 'post'
	 */
	function FormMethod() {
		return $this->formMethod;
	}
	
	/**
	 * Set the form method - get or post
	 */
	function setFormMethod($method) {
		$this->formMethod = strtolower($method);
		if($this->formMethod == 'get') $this->fields->push(new HiddenField('executeForm', '', $this->name));
	}
	
	protected $formAction = null;
	
	function setFormAction($link) {
		$this->formAction = $link;
	}
	
	/**
	 * Return the form's action attribute.
	 * This is build by adding an executeForm get variable to the parent controller's Link() value
	 * @return string The
	 */
	function FormAction() {
		// Custom override
		if($this->formAction) return $this->formAction;
		
		// "get" form needs ?executeForm added as a hidden field
		if($this->formMethod == 'post') {
			if($this->controller->hasMethod("FormObjectLink")) {
				return $this->controller->FormObjectLink($this->name);
			} else {
				return $this->controller->Link() . "?executeForm=" . $this->name;
			}
		} else {
			return $this->controller->Link();
		}
	}

	/**
	 * Returns the name of the form
	 */
	function FormName() {
		return $this->class . '_' . str_replace('.','',$this->name);
	}
	
	/**
	 * @return string
	 */
	function Name(){
		return $this->name;
	}
	
	/**
	 * Returns the field referenced by $_GET[fieldName].
	 * Used for embedding entire extra helper forms inside complex field types (such as ComplexTableField)
	 * @return FormField The field referenced by $_GET[fieldName]
	 */
	function ReferencedField() {
		$field = $this->dataFieldByName($_GET['fieldName']);
		if(!$field) user_error("Field '" . $_GET['fieldName'] . "' not found in this form", E_USER_WARNING);
		return $field;
	}

	/**
	 * The next functions store and modify the forms
	 * message attributes. messages are stored in session under
	 * $_SESSION[formname][message];
	 */
	protected $message, $messageType;
	function Message() {
		$this->getMessageFromSession();
		$message = $this->message;
		$this->clearMessage();
		return $message;
	}
	function MessageType() {
		$this->getMessageFromSession();
		return $this->messageType;
	}

	protected function getMessageFromSession() {
		if($this->message || $this->messageType) {
			return $this->message;
		}else{
			$this->message = Session::get("FormInfo.{$this->FormName()}.formError.message");
			$this->messageType = Session::get("FormInfo.{$this->FormName()}.formError.type");

			Session::clear("FormInfo.{$this->FormName()}");
		}
	}

	/**
	 * Set a status message for the form.
	 * @param message the text of the message
	 * @param type Should be set to good, bad, or warning.
	 */
	function setMessage($message, $type) {
		$this->message = $message;
		$this->messageType = $type;
	}

	/**
	 * Set a message to the session, for display next time this form is shown.
	 * @param message the text of the message
	 * @param type Should be set to good, bad, or warning.
	 */
	function sessionMessage($message, $type) {
		Session::set("FormInfo.{$this->FormName()}.formError.message", $message);
		Session::set("FormInfo.{$this->FormName()}.formError.type", $type);
	}

	static function messageForForm( $formName, $message, $type ) {
		Session::set("FormInfo.{$formName}.formError.message", $message);
		Session::set("FormInfo.{$formName}.formError.type", $type);
	}

	function clearMessage() {
		$this->message  = null;
		Session::clear("FormInfo.{$this->FormName()}.errors");
		Session::clear("FormInfo.{$this->FormName()}.formError");
	}
	function resetValidation() {
		Session::clear("FormInfo.{$this->FormName()}.errors");
	}

	protected $record;
	
	/**
	 * Returns the DataObject that has given this form its data.
	 * @return DataObject
	 */
	function getRecord() {
		return $this->record;
	}

	/**
	 * Processing that occurs before a form is executed.
	 * This includes form validation, if it fails, we redirect back
	 * to the form with appropriate error messages
	 */
	 function beforeProcessing(){
		if($this->validator){
			$errors = $this->validator->validate();

			if($errors){
				if(Director::is_ajax()) {
					// Send validation errors back as JSON with a flag at the start
					//echo "VALIDATIONERROR:" . Convert::array2json($errors);
					FormResponse::status_message(_t('Form.VALIDATIONFAILED', 'Validation failed'), 'bad');
					foreach($errors as $error) {
						FormResponse::add(sprintf(
							"validationError('%s', '%s', '%s');\n",
							Convert::raw2js($error['fieldName']),
							Convert::raw2js($error['message']),
							Convert::raw2js($error['messageType'])
						));
					}
					echo FormResponse::respond();
					return false;
				} else {
					$data = $this->getData();

					// People will get worried if you leave credit card information in session..
					if(isset($data['CreditCardNumber']))	unset($data['CreditCardNumber']);
					if(isset($data['DateExpiry'])) unset($data['Expiry']);

					// Load errors into session and post back
					Session::set("FormInfo.{$this->FormName()}", array(
						'errors' => $errors,
						'data' => $data,
					));

					Director::redirectBack();
				}
				return false;
			}
		}
		return true;
	}

	/**
	 * Load data from the given object.
	 * It will call $object->MyField to get the value of MyField.
	 * If you passed an array, it will call $object[MyField]
	 * @param object Either an object or an array to get the data from.
	 * @param forceChanges Load blank values into the form.
	 */

	function loadDataFrom($object, $loadBlanks = false) {
		if(is_object($object)) {
			$o = true;
			$this->record = $object;
		} else if(is_array($object)) {
			$o = false;
		} else {
			user_error("Form::loadDataFrom() not passed an array or an object", E_USER_WARNING);
			return;
		}

		$dataFields = $this->fields->dataFields();
		if($dataFields) foreach($dataFields as $field) {

			if($name = $field->Name()) {
				if($o) {
					// this was failing with the field named 'Name'
					$val = $object->__get($name);
				} else {
					$val = isset($object[$name]) ? $object[$name] : null;
				}

				// First check looks for (fieldname)_unchanged, an indicator that we shouldn't overwrite the field value
				if($o || !isset($object[$name . '_unchanged'])) {
					// Second check was the original check: save the value if we have one
					if(isset($val) || $loadBlanks) {
						$field->setValue($val);
					}
				}
			}
		}
	}

	/**
	 * Load data from the given object.
	 * It will call $object->MyField to get the value of MyField.
	 * If you passed an array, it will call $object[MyField]
	 */
	function loadNonBlankDataFrom($object) {
		$this->record = $object;
		if(is_object($object)) $o = true;
		else if(is_array($object)) $o = false;
		else {
			user_error("Form::loadDataFrom() not passed an array or an object", E_USER_WARNING);
			return;
		}
		$dataFields = $this->fields->dataFields();
		if($dataFields) foreach($dataFields as $field) {
			$name = $field->Name();
			$val = $o ? $object->$name : (isset($object[$name]) ? $object[$name] : null);
			if($name && $val) $field->setValue($val);
		}
	}
	/**
	 * Save the contents of this form into the given data object.
	 * It will make use of setCastedField() to do this.
	 */
	function saveInto(DataObjectInterface $dataObject) {
		$dataFields = $this->fields->dataFields();
		$lastField = null;

		if($dataFields) foreach($dataFields as $field) {
			$saveMethod = "save{$field->Name()}";

			if($field->Name() == "ClassName"){
				$lastField = $field;
			}else if( $dataObject->hasMethod( $saveMethod ) ){
				$dataObject->$saveMethod( $field->dataValue());
			} else if($field->Name() != "ID"){
				$field->saveInto($dataObject);
			}
		}
		if($lastField) $lastField->saveInto($dataObject);
	}
	/**
	 * Get the data from this form
	 */
	function getData() {
		$dataFields = $this->fields->dataFields();
		if($dataFields){
			foreach($dataFields as $field) {
				if($field->Name()) {
					$data[$field->Name()] = $field->dataValue();
				}
			}
		}
		return $data;
	}

	function resetData($fieldName, $fieldValue){
		$dataFields = $this->fields->dataFields();
		if($dataFields){
			foreach($dataFields as $field) {
				if($field->Name()==$fieldName) {
					$field = $field->setValue($fieldValue);
				}
			}
		}		
	}
	
	/**
	 * Call the given method on the given field.
	 * This is used by Ajax-savvy form fields.  By putting '&action=callfieldmethod' to the end
	 * of the form action, they can access server-side data.
	 * @param fieldName The name of the field.  Can be overridden by $_REQUEST[fieldName]
	 * @param methodName The name of the field.  Can be overridden by $_REQUEST[methodName]
	 */

	function callfieldmethod($data) {
		$fieldName = $data['fieldName'];
		$methodName = $data['methodName'];
		$fields = $this->fields->dataFields();

		// special treatment needed for TableField-class and TreeDropdownField
		if(strpos($fieldName, '[')) {
			preg_match_all('/([^\[]*)/',$fieldName, $fieldNameMatches);
			preg_match_all('/\[([^\]]*)\]/',$fieldName, $subFieldMatches);
			$tableFieldName = $fieldNameMatches[1][0];
			$subFieldName = $subFieldMatches[1][1];
		}

		if(isset($tableFieldName) && isset($subFieldName) && is_a($fields[$tableFieldName], 'TableField')) {
			$field = $fields[$tableFieldName]->getField($subFieldName, $fieldName);
			return $field->$methodName();
		} else if(isset($fields[$fieldName])) {
			return $fields[$fieldName]->$methodName();
		} else {
			user_error("Form::callfieldmethod() Field '$fieldName' not found", E_USER_ERROR);
		}

	}


	/**
	 * Return a rendered version of this form.
	 * This is returned when you access a form as $FormObject rather than <% control FormObject %>
	 */
	function forTemplate() {
		$form = $this->renderWith("Form");
		return $form;
	}

	/**
	 * Returns an HTML rendition of this form, without the <form> tag itself.
	 * Attaches 3 extra hidden files, _form_action, _form_name, _form_method, and _form_enctype.  These are
	 * the attributes of the form.  These fields can be used to send the form to Ajax.
	 */
	function formHtmlContent() {
		$this->IncludeFormTag = false;
		$content = $this->forTemplate();
		$this->IncludeFormTag = true;

		$content .= "<input type=\"hidden\" name=\"_form_action\" id=\"" . $this->FormName . "_form_action\" value=\"" . $this->FormAction() . "\" />\n";
		$content .= "<input type=\"hidden\" name=\"_form_name\" value=\"" . $this->FormName() . "\" />\n";
		$content .= "<input type=\"hidden\" name=\"_form_method\" value=\"" . $this->FormMethod() . "\" />\n";
		$content .= "<input type=\"hidden\" name=\"_form_enctype\" value=\"" . $this->FormEncType() . "\" />\n";

		return $content;
	}

	function debug() {
		$result = "<h3>$this->class</h3><ul>";
		foreach($this->fields as $field) {
			$result .= "<li>$field" . $field->debug() . "</li>";
		}
		$result .= "</ul>";

		if( $this->validator )
		        $result .= '<h3>'._t('Form.VALIDATOR', 'Validator').'</h3>' . $this->validator->debug();

		return $result;
	}

	/**
	 * Render this form using the given template, and return the result as a string
	 * You can pass either an SSViewer or a template name
	 */
	function renderWithoutActionButton($template) {
		$custom = $this->customise(array(
			"Actions" => "",
		));

		if(is_string($template)) $template = new SSViewer($template);
		return $template->process($custom);
	}


	protected $buttonClickedFunc;
	/**
	 * Sets the button that was clicked.  This should only be called by the Controller.
	 * @param funcName The name of the action method that will be called.
	 */
	function setButtonClicked($funcName) {
		$this->buttonClickedFunc = $funcName;
	}

	function buttonClicked() {
		foreach($this->actions as $action) {
			if($this->buttonClickedFunc == $action->actionName()) return $action;
		}
	}

	/**
	 * Return the default button that should be clicked when another one isn't available
	 */
	function defaultAction() {
		if($this->hasDefaultAction && $this->actions)
			return $this->actions->First();
	}

	/**
	 * Disable the default button.
	 * Ordinarily, when a form is processed and no action_XXX button is available, then the first button in the actions list
	 * will be pressed.  However, if this is "delete", for example, this isn't such a good idea.
	 */
	function disableDefaultAction() {
		$this->hasDefaultAction = false;
	}
	
	private $security = true;
	
	/**
	 * Disable the requirement of a SecurityID in the Form. This security protects
	 * against CSRF attacks, but you should disable this if you don't want to tie 
	 * a form to a session - eg a search form.
	 */
	function disableSecurityToken() {
		$this->security = false;
	}
	
	/**
	 * Returns true if security is enabled - that is if the SecurityID
	 * should be included and checked on this form.
	 *
	 * @return bool
	 */
	function securityTokenEnabled() {
		return $this->security;
	}

	/**
	 * Returns the name of a field, if that's the only field that the current controller is interested in.
	 * It checks for a call to the callfieldmethod action.
	 * This is useful for optimising your forms
	 */
	static function single_field_required() {
		if(self::current_action() == 'callfieldmethod') return $_REQUEST['fieldName'];
	}

	/**
	 * Return the current form action being called, if available.
	 * This is useful for optimising your forms
	 */
	static function current_action() {
		return self::$current_action;
	}

	/**
	 * Set the current form action.  Should only be called by Controller.
	 */
	static function set_current_action($action) {
		self::$current_action = $action;
	}
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// TESTING HELPERS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Test a submission of this form.
	 * @return HTTPResponse the response object that the handling controller produces.  You can interrogate this in your unit test.
	 */
	function testSubmission($action, $data) {
		$data['action_' . $action] = true;
		$data['executeForm'] = $this->name;
        
        return Director::test($this->FormAction(), $data, Controller::curr()->getSession());
		
		//$response = $this->controller->run($data);
		//return $response;
	}
	
	/**
	 * Test an ajax submission of this form.
	 * @return HTTPResponse the response object that the handling controller produces.  You can interrogate this in your unit test.
	 */
	function testAjaxSubmission($action, $data) {
		$data['ajax'] = 1;
		return $this->testSubmission($action, $data);
	}
}
