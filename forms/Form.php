<?php
/**
 * Base class for all forms.
 * The form class is an extensible base for all forms on a SilverStripe application.  It can be used
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
 * You will need to create at least one method for processing the submission (through {@link FormAction}).
 * This method will be passed two parameters: the raw request data, and the form object.
 * Usually you want to save data into a {@link DataObject} by using {@link saveInto()}.
 * If you want to process the submitted data in any way, please use {@link getData()} rather than
 * the raw request data.
 *
 * <h2>Validation</h2>
 * Each form needs some form of {@link Validator} to trigger the {@link FormField->validate()} methods for each field.
 * You can't disable validator for security reasons, because crucial behaviour like extension checks for file uploads
 * depend on it.
 * The default validator is an instance of {@link RequiredFields}.
 * If you want to enforce serverside-validation to be ignored for a specific {@link FormField},
 * you need to subclass it.
 *
 * <h2>URL Handling</h2>
 * The form class extends {@link RequestHandler}, which means it can
 * be accessed directly through a URL. This can be handy for refreshing
 * a form by ajax, or even just displaying a single form field.
 * You can find out the base URL for your form by looking at the
 * <form action="..."> value. For example, the edit form in the CMS would be located at
 * "admin/EditForm". This URL will render the form without its surrounding
 * template when called through GET instead of POST.
 *
 * By appending to this URL, you can render individual form elements
 * through the {@link FormField->FieldHolder()} method.
 * For example, the "URLSegment" field in a standard CMS form would be
 * accessible through "admin/EditForm/field/URLSegment/FieldHolder".
 *
 * @package forms
 * @subpackage core
 */
class Form extends RequestHandler {

	const ENC_TYPE_URLENCODED = 'application/x-www-form-urlencoded';
	const ENC_TYPE_MULTIPART  = 'multipart/form-data';

	/**
	 * @var boolean $includeFormTag Accessed by Form.ss; modified by {@link formHtmlContent()}.
	 * A performance enhancement over the generate-the-form-tag-and-then-remove-it code that was there previously
	 */
	public $IncludeFormTag = true;

	/**
	 * @var FieldList|null
	 */
	protected $fields;

	/**
	 * @var FieldList|null
	 */
	protected $actions;

	/**
	 * @var Controller|null
	 */
	protected $controller;

	/**
	 * @var string|null
	 */
	protected $name;

	/**
	 * @var Validator|null
	 */
	protected $validator;

	/**
	 * @var string
	 */
	protected $formMethod = "POST";

	/**
	 * @var boolean
	 */
	protected $strictFormMethodCheck = false;

	/**
	 * @var string|null
	 */
	protected static $current_action;

	/**
	 * @var DataObject|null $record Populated by {@link loadDataFrom()}.
	 */
	protected $record;

	/**
	 * Keeps track of whether this form has a default action or not.
	 * Set to false by $this->disableDefaultAction();
	 *
	 * @var boolean
	 */
	protected $hasDefaultAction = true;

	/**
	 * Target attribute of form-tag.
	 * Useful to open a new window upon
	 * form submission.
	 *
	 * @var string|null
	 */
	protected $target;

	/**
	 * Legend value, to be inserted into the
	 * <legend> element before the <fieldset>
	 * in Form.ss template.
	 *
	 * @var string|null
	 */
	protected $legend;

	/**
	 * The SS template to render this form HTML into.
	 * Default is "Form", but this can be changed to
	 * another template for customisation.
	 *
	 * @see Form->setTemplate()
	 * @var string|null
	 */
	protected $template;

	/**
	 * @var callable|null
	 */
	protected $buttonClickedFunc;

	/**
	 * @var string|null
	 */
	protected $message;

	/**
	 * @var string|null
	 */
	protected $messageType;

	/**
	 * Should we redirect the user back down to the
	 * the form on validation errors rather then just the page
	 *
	 * @var bool
	 */
	protected $redirectToFormOnValidationError = false;

	/**
	 * @var bool
	 */
	protected $security = true;

	/**
	 * @var SecurityToken|null
	 */
	protected $securityToken = null;

	/**
	 * @var array $extraClasses List of additional CSS classes for the form tag.
	 */
	protected $extraClasses = array();

	/**
	 * @config
	 * @var array $default_classes The default classes to apply to the Form
	 */
	private static $default_classes = array();

	/**
	 * @var string|null
	 */
	protected $encType;

	/**
	 * @var array Any custom form attributes set through {@link setAttributes()}.
	 * Some attributes are calculated on the fly, so please use {@link getAttributes()} to access them.
	 */
	protected $attributes = array();

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'handleField',
		'httpSubmission',
		'forTemplate',
	);

	/**
	 * @var FormTemplateHelper
	 */
	private $templateHelper = null;

	/**
	 * @ignore
	 */
	private $htmlID = null;

	/**
	 * @ignore
	 */
	private $formActionPath = false;

	/**
	 * @var bool
	 */
	protected $securityTokenAdded = false;

	/**
	 * Create a new form, with the given fields an action buttons.
	 *
	 * @param Controller $controller The parent controller, necessary to create the appropriate form action tag.
	 * @param string $name The method on the controller that will return this form object.
	 * @param FieldList $fields All of the fields in the form - a {@link FieldList} of {@link FormField} objects.
	 * @param FieldList $actions All of the action buttons in the form - a {@link FieldLis} of
	 *                           {@link FormAction} objects
	 * @param Validator $validator Override the default validator instance (Default: {@link RequiredFields})
	 */
	public function __construct($controller, $name, FieldList $fields, FieldList $actions, $validator = null) {
		parent::__construct();

		if(!$fields instanceof FieldList) {
			throw new InvalidArgumentException('$fields must be a valid FieldList instance');
		}
		if(!$actions instanceof FieldList) {
			throw new InvalidArgumentException('$actions must be a valid FieldList instance');
		}
		if($validator && !$validator instanceof Validator) {
			throw new InvalidArgumentException('$validator must be a Validator instance');
		}

		$fields->setForm($this);
		$actions->setForm($this);

		$this->fields = $fields;
		$this->actions = $actions;
		$this->controller = $controller;
		$this->name = $name;

		if(!$this->controller) user_error("$this->class form created without a controller", E_USER_ERROR);

		// Form validation
		$this->validator = ($validator) ? $validator : new RequiredFields();
		$this->validator->setForm($this);

		// Form error controls
		$this->setupFormErrors();

		// Check if CSRF protection is enabled, either on the parent controller or from the default setting. Note that
		// method_exists() is used as some controllers (e.g. GroupTest) do not always extend from Object.
		if(method_exists($controller, 'securityTokenEnabled') || (method_exists($controller, 'hasMethod')
				&& $controller->hasMethod('securityTokenEnabled'))) {

			$securityEnabled = $controller->securityTokenEnabled();
		} else {
			$securityEnabled = SecurityToken::is_enabled();
		}

		$this->securityToken = ($securityEnabled) ? new SecurityToken() : new NullSecurityToken();

		$this->setupDefaultClasses();
	}

	/**
	 * @var array
	 */
	private static $url_handlers = array(
		'field/$FieldName!' => 'handleField',
		'POST ' => 'httpSubmission',
		'GET ' => 'httpSubmission',
		'HEAD ' => 'httpSubmission',
	);

	/**
	 * Set up current form errors in session to
	 * the current form if appropriate.
	 *
	 * @return $this
	 */
	public function setupFormErrors() {
		$errorInfo = Session::get("FormInfo.{$this->FormName()}");

		if(isset($errorInfo['errors']) && is_array($errorInfo['errors'])) {
			foreach($errorInfo['errors'] as $error) {
				$field = $this->fields->dataFieldByName($error['fieldName']);

				if(!$field) {
					$errorInfo['message'] = $error['message'];
					$errorInfo['type'] = $error['messageType'];
				} else {
					$field->setError($error['message'], $error['messageType']);
				}
			}

			// load data in from previous submission upon error
			if(isset($errorInfo['data'])) $this->loadDataFrom($errorInfo['data']);
		}

		if(isset($errorInfo['message']) && isset($errorInfo['type'])) {
			$this->setMessage($errorInfo['message'], $errorInfo['type']);
		}

		return $this;
	}

	/**
	 * set up the default classes for the form. This is done on construct so that the default classes can be removed
	 * after instantiation
	 */
	protected function setupDefaultClasses() {
		$defaultClasses = self::config()->get('default_classes');
		if ($defaultClasses) {
			foreach ($defaultClasses as $class) {
				$this->addExtraClass($class);
			}
		}
	}

	/**
	 * Handle a form submission.  GET and POST requests behave identically.
	 * Populates the form with {@link loadDataFrom()}, calls {@link validate()},
	 * and only triggers the requested form action/method
	 * if the form is valid.
	 *
	 * @param SS_HTTPRequest $request
	 * @throws SS_HTTPResponse_Exception
	 */
	public function httpSubmission($request) {
		// Strict method check
		if($this->strictFormMethodCheck) {

			// Throws an error if the method is bad...
			if($this->formMethod != $request->httpMethod()) {
				$response = Controller::curr()->getResponse();
				$response->addHeader('Allow', $this->formMethod);
				$this->httpError(405, _t("Form.BAD_METHOD", "This form requires a ".$this->formMethod." submission"));
			}

			// ...and only uses the variables corresponding to that method type
			$vars = $this->formMethod == 'GET' ? $request->getVars() : $request->postVars();
		} else {
			$vars = $request->requestVars();
		}

		// Populate the form
		$this->loadDataFrom($vars, true);

		// Protection against CSRF attacks
		$token = $this->getSecurityToken();
		if( ! $token->checkRequest($request)) {
			$securityID = $token->getName();
			if (empty($vars[$securityID])) {
				$this->httpError(400, _t("Form.CSRF_FAILED_MESSAGE",
					"There seems to have been a technical problem. Please click the back button, ".
					"refresh your browser, and try again."
				));
			} else {
				// Clear invalid token on refresh
				$data = $this->getData();
				unset($data[$securityID]);
				Session::set("FormInfo.{$this->FormName()}.data", $data);
				Session::set("FormInfo.{$this->FormName()}.errors", array());
				$this->sessionMessage(
					_t("Form.CSRF_EXPIRED_MESSAGE", "Your session has expired. Please re-submit the form."),
					"warning"
				);
				return $this->controller->redirectBack();
			}
		}

		// Determine the action button clicked
		$funcName = null;
		foreach($vars as $paramName => $paramVal) {
			if(substr($paramName,0,7) == 'action_') {
				// Break off querystring arguments included in the action
				if(strpos($paramName,'?') !== false) {
					list($paramName, $paramVars) = explode('?', $paramName, 2);
					$newRequestParams = array();
					parse_str($paramVars, $newRequestParams);
					$vars = array_merge((array)$vars, (array)$newRequestParams);
				}

				// Cleanup action_, _x and _y from image fields
				$funcName = preg_replace(array('/^action_/','/_x$|_y$/'),'',$paramName);
				break;
			}
		}

		// If the action wasn't set, choose the default on the form.
		if(!isset($funcName) && $defaultAction = $this->defaultAction()){
			$funcName = $defaultAction->actionName();
		}

		if(isset($funcName)) {
			Form::set_current_action($funcName);
			$this->setButtonClicked($funcName);
		}

		// Permission checks (first on controller, then falling back to form)
		if(
			// Ensure that the action is actually a button or method on the form,
			// and not just a method on the controller.
			$this->controller->hasMethod($funcName)
			&& !$this->controller->checkAccessAction($funcName)
			// If a button exists, allow it on the controller
			&& !$this->actions->dataFieldByName('action_' . $funcName)
		) {
			return $this->httpError(
				403,
				sprintf('Action "%s" not allowed on controller (Class: %s)', $funcName, get_class($this->controller))
			);
		} elseif(
			$this->hasMethod($funcName)
			&& !$this->checkAccessAction($funcName)
			// No checks for button existence or $allowed_actions is performed -
			// all form methods are callable (e.g. the legacy "callfieldmethod()")
		) {
			return $this->httpError(
				403,
				sprintf('Action "%s" not allowed on form (Name: "%s")', $funcName, $this->name)
			);
		}
		// TODO : Once we switch to a stricter policy regarding allowed_actions (meaning actions must be set
		// explicitly in allowed_actions in order to run)
		// Uncomment the following for checking security against running actions on form fields
		/* else {
			// Try to find a field that has the action, and allows it
			$fieldsHaveMethod = false;
			foreach ($this->Fields() as $field){
				if ($field->hasMethod($funcName) && $field->checkAccessAction($funcName)) {
					$fieldsHaveMethod = true;
				}
			}
			if (!$fieldsHaveMethod) {
				return $this->httpError(
					403,
					sprintf('Action "%s" not allowed on any fields of form (Name: "%s")', $funcName, $this->Name())
				);
			}
		}*/

		// Validate the form
		if(!$this->validate()) {
			return $this->getValidationErrorResponse();
		}

		// First, try a handler method on the controller (has been checked for allowed_actions above already)
		if($this->controller->hasMethod($funcName)) {
			return $this->controller->$funcName($vars, $this, $request);
		// Otherwise, try a handler method on the form object.
		} elseif($this->hasMethod($funcName)) {
			return $this->$funcName($vars, $this, $request);
		} elseif($field = $this->checkFieldsForAction($this->Fields(), $funcName)) {
			return $field->$funcName($vars, $this, $request);
		}

		return $this->httpError(404);
	}

	/**
	 * @param string $action
	 * @return bool
	 */
	public function checkAccessAction($action) {
		return (
			parent::checkAccessAction($action)
			// Always allow actions which map to buttons. See httpSubmission() for further access checks.
			|| $this->actions->dataFieldByName('action_' . $action)
			// Always allow actions on fields
			|| (
				$field = $this->checkFieldsForAction($this->Fields(), $action)
				&& $field->checkAccessAction($action)
			)
		);
	}

	/**
	 * Returns the appropriate response up the controller chain
	 * if {@link validate()} fails (which is checked prior to executing any form actions).
	 * By default, returns different views for ajax/non-ajax request, and
	 * handles 'application/json' requests with a JSON object containing the error messages.
	 * Behaviour can be influenced by setting {@link $redirectToFormOnValidationError}.
	 *
	 * @return SS_HTTPResponse|string
	 */
	protected function getValidationErrorResponse() {
		$request = $this->getRequest();
		if($request->isAjax()) {
				// Special case for legacy Validator.js implementation
				// (assumes eval'ed javascript collected through FormResponse)
				$acceptType = $request->getHeader('Accept');
				if(strpos($acceptType, 'application/json') !== FALSE) {
					// Send validation errors back as JSON with a flag at the start
					$response = new SS_HTTPResponse(Convert::array2json($this->validator->getErrors()));
					$response->addHeader('Content-Type', 'application/json');
				} else {
					$this->setupFormErrors();
					// Send the newly rendered form tag as HTML
					$response = new SS_HTTPResponse($this->forTemplate());
					$response->addHeader('Content-Type', 'text/html');
				}

				return $response;
			} else {
				if($this->getRedirectToFormOnValidationError()) {
					if($pageURL = $request->getHeader('Referer')) {
						if(Director::is_site_url($pageURL)) {
							// Remove existing pragmas
							$pageURL = preg_replace('/(#.*)/', '', $pageURL);
							$pageURL = Director::absoluteURL($pageURL, true);
							return $this->controller->redirect($pageURL . '#' . $this->FormName());
						}
					}
				}
				return $this->controller->redirectBack();
			}
	}

	/**
	 * Fields can have action to, let's check if anyone of the responds to $funcname them
	 *
	 * @param SS_List|array $fields
	 * @param callable $funcName
	 * @return FormField
	 */
	protected function checkFieldsForAction($fields, $funcName) {
		foreach($fields as $field){
			if(method_exists($field, 'FieldList')) {
				if($field = $this->checkFieldsForAction($field->FieldList(), $funcName)) {
					return $field;
				}
			} elseif ($field->hasMethod($funcName) && $field->checkAccessAction($funcName)) {
				return $field;
			}
		}
	}

	/**
	 * Handle a field request.
	 * Uses {@link Form->dataFieldByName()} to find a matching field,
	 * and falls back to {@link FieldList->fieldByName()} to look
	 * for tabs instead. This means that if you have a tab and a
	 * formfield with the same name, this method gives priority
	 * to the formfield.
	 *
	 * @param SS_HTTPRequest $request
	 * @return FormField
	 */
	public function handleField($request) {
		$field = $this->Fields()->dataFieldByName($request->param('FieldName'));

		if($field) {
			return $field;
		} else {
			// falling back to fieldByName, e.g. for getting tabs
			return $this->Fields()->fieldByName($request->param('FieldName'));
		}
	}

	/**
	 * Convert this form into a readonly form
	 */
	public function makeReadonly() {
		$this->transform(new ReadonlyTransformation());
	}

	/**
	 * Set whether the user should be redirected back down to the
	 * form on the page upon validation errors in the form or if
	 * they just need to redirect back to the page
	 *
	 * @param bool $bool Redirect to form on error?
	 * @return $this
	 */
	public function setRedirectToFormOnValidationError($bool) {
		$this->redirectToFormOnValidationError = $bool;
		return $this;
	}

	/**
	 * Get whether the user should be redirected back down to the
	 * form on the page upon validation errors
	 *
	 * @return bool
	 */
	public function getRedirectToFormOnValidationError() {
		return $this->redirectToFormOnValidationError;
	}

	/**
	 * Add a plain text error message to a field on this form.  It will be saved into the session
	 * and used the next time this form is displayed.
	 * @param string $fieldName
	 * @param string $message
	 * @param string $messageType
	 * @param bool $escapeHtml
	 */
	public function addErrorMessage($fieldName, $message, $messageType, $escapeHtml = true) {
		Session::add_to_array("FormInfo.{$this->FormName()}.errors",  array(
			'fieldName' => $fieldName,
			'message' => $escapeHtml ? Convert::raw2xml($message) : $message,
			'messageType' => $messageType,
		));
	}

	/**
	 * @param FormTransformation $trans
	 */
	public function transform(FormTransformation $trans) {
		$newFields = new FieldList();
		foreach($this->fields as $field) {
			$newFields->push($field->transform($trans));
		}
		$this->fields = $newFields;

		$newActions = new FieldList();
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
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Set the {@link Validator} on this form.
	 * @param Validator $validator
	 * @return $this
	 */
	public function setValidator(Validator $validator ) {
		if($validator) {
			$this->validator = $validator;
			$this->validator->setForm($this);
		}
		return $this;
	}

	/**
	 * Remove the {@link Validator} from this from.
	 */
	public function unsetValidator(){
		$this->validator = null;
		return $this;
	}

	/**
	 * Convert this form to another format.
	 * @param FormTransformation $format
	 */
	public function transformTo(FormTransformation $format) {
		$newFields = new FieldList();
		foreach($this->fields as $field) {
			$newFields->push($field->transformTo($format));
		}
		$this->fields = $newFields;

		// We have to remove validation, if the fields are not editable ;-)
		if($this->validator)
			$this->validator->removeValidation();
	}


	/**
	 * Generate extra special fields - namely the security token field (if required).
	 *
	 * @return FieldList
	 */
	public function getExtraFields() {
		$extraFields = new FieldList();

		$token = $this->getSecurityToken();
		if ($token) {
			$tokenField = $token->updateFieldSet($this->fields);
			if($tokenField) $tokenField->setForm($this);
		}
		$this->securityTokenAdded = true;

		// add the "real" HTTP method if necessary (for PUT, DELETE and HEAD)
		if (strtoupper($this->FormMethod()) != $this->FormHttpMethod()) {
			$methodField = new HiddenField('_method', '', $this->FormHttpMethod());
			$methodField->setForm($this);
			$extraFields->push($methodField);
		}

		return $extraFields;
	}

	/**
	 * Return the form's fields - used by the templates
	 *
	 * @return FieldList The form fields
	 */
	public function Fields() {
		foreach($this->getExtraFields() as $field) {
			if(!$this->fields->fieldByName($field->getName())) $this->fields->push($field);
		}

		return $this->fields;
	}

	/**
	 * Return all <input type="hidden"> fields
	 * in a form - including fields nested in {@link CompositeFields}.
	 * Useful when doing custom field layouts.
	 *
	 * @return FieldList
	 */
	public function HiddenFields() {
		return $this->Fields()->HiddenFields();
	}

	/**
	 * Return all fields except for the hidden fields.
	 * Useful when making your own simplified form layouts.
	 */
	public function VisibleFields() {
		return $this->Fields()->VisibleFields();
	}

	/**
	 * Setter for the form fields.
	 *
	 * @param FieldList $fields
	 * @return $this
	 */
	public function setFields($fields) {
		$this->fields = $fields;
		return $this;
	}

	/**
	 * Return the form's action buttons - used by the templates
	 *
	 * @return FieldList The action list
	 */
	public function Actions() {
		return $this->actions;
	}

	/**
	 * Setter for the form actions.
	 *
	 * @param FieldList $actions
	 * @return $this
	 */
	public function setActions($actions) {
		$this->actions = $actions;
		return $this;
	}

	/**
	 * Unset all form actions
	 */
	public function unsetAllActions(){
		$this->actions = new FieldList();
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
		return $this;
	}

	/**
	 * @return string $name
	 */
	public function getAttribute($name) {
		if(isset($this->attributes[$name])) return $this->attributes[$name];
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		$attrs = array(
			'id' => $this->FormName(),
			'action' => $this->FormAction(),
			'method' => $this->FormMethod(),
			'enctype' => $this->getEncType(),
			'target' => $this->target,
			'class' => $this->extraClass(),
		);

		if($this->validator && $this->validator->getErrors()) {
			if(!isset($attrs['class'])) $attrs['class'] = '';
			$attrs['class'] .= ' validationerror';
		}

		$attrs = array_merge($attrs, $this->attributes);

		return $attrs;
	}

	/**
	 * Return the attributes of the form tag - used by the templates.
	 *
	 * @param array Custom attributes to process. Falls back to {@link getAttributes()}.
	 * If at least one argument is passed as a string, all arguments act as excludes by name.
	 *
	 * @return string HTML attributes, ready for insertion into an HTML tag
	 */
	public function getAttributesHTML($attrs = null) {
		$exclude = (is_string($attrs)) ? func_get_args() : null;

		// Figure out if we can cache this form
		// - forms with validation shouldn't be cached, cos their error messages won't be shown
		// - forms with security tokens shouldn't be cached because security tokens expire
		$needsCacheDisabled = false;
		if ($this->getSecurityToken()->isEnabled()) $needsCacheDisabled = true;
		if ($this->FormMethod() != 'GET') $needsCacheDisabled = true;
		if (!($this->validator instanceof RequiredFields) || count($this->validator->getRequired())) {
			$needsCacheDisabled = true;
		}

		// If we need to disable cache, do it
		if ($needsCacheDisabled) HTTP::set_cache_age(0);

		$attrs = $this->getAttributes();

		// Remove empty
		$attrs = array_filter((array)$attrs, create_function('$v', 'return ($v || $v === 0);'));

		// Remove excluded
		if($exclude) $attrs = array_diff_key($attrs, array_flip($exclude));

		// Prepare HTML-friendly 'method' attribute (lower-case)
		if (isset($attrs['method'])) {
			$attrs['method'] = strtolower($attrs['method']);
		}

		// Create markup
		$parts = array();
		foreach($attrs as $name => $value) {
			$parts[] = ($value === true) ? "{$name}=\"{$name}\"" : "{$name}=\"" . Convert::raw2att($value) . "\"";
		}

		return implode(' ', $parts);
	}

	public function FormAttributes() {
		return $this->getAttributesHTML();
	}

	/**
	 * Set the target of this form to any value - useful for opening the form contents in a new window or refreshing
	 * another frame
	 * 
	 * @param string|FormTemplateHelper
	 */
	public function setTemplateHelper($helper) {
		$this->templateHelper = $helper;
	}

	/**
	 * Return a {@link FormTemplateHelper} for this form. If one has not been
	 * set, return the default helper.
	 *
	 * @return FormTemplateHelper
	 */
	public function getTemplateHelper() {
		if($this->templateHelper) {
			if(is_string($this->templateHelper)) {
				return Injector::inst()->get($this->templateHelper);
			}

			return $this->templateHelper;
		}

		return Injector::inst()->get('FormTemplateHelper');
	}

	/**
	 * Set the target of this form to any value - useful for opening the form
	 * contents in a new window or refreshing another frame.
	 *
	 * @param target $target The value of the target
	 * @return $this
	 */
	public function setTarget($target) {
		$this->target = $target;

		return $this;
	}

	/**
	 * Set the legend value to be inserted into
	 * the <legend> element in the Form.ss template.
	 * @param string $legend
	 * @return $this
	 */
	public function setLegend($legend) {
		$this->legend = $legend;
		return $this;
	}

	/**
	 * Set the SS template that this form should use
	 * to render with. The default is "Form".
	 *
	 * @param string $template The name of the template (without the .ss extension)
	 * @return $this
	 */
	public function setTemplate($template) {
		$this->template = $template;
		return $this;
	}

	/**
	 * Return the template to render this form with.
	 * If the template isn't set, then default to the
	 * form class name e.g "Form".
	 *
	 * @return string
	 */
	public function getTemplate() {
		if($this->template) return $this->template;
		else return $this->class;
	}

	/**
	 * Returns the encoding type for the form.
	 *
	 * By default this will be URL encoded, unless there is a file field present
	 * in which case multipart is used. You can also set the enc type using
	 * {@link setEncType}.
	 */
	public function getEncType() {
		if ($this->encType) {
			return $this->encType;
		}

		if ($fields = $this->fields->dataFields()) {
			foreach ($fields as $field) {
				if ($field instanceof FileField) return self::ENC_TYPE_MULTIPART;
			}
		}

		return self::ENC_TYPE_URLENCODED;
	}

	/**
	 * Sets the form encoding type. The most common encoding types are defined
	 * in {@link ENC_TYPE_URLENCODED} and {@link ENC_TYPE_MULTIPART}.
	 *
	 * @param string $encType
	 * @return $this
	 */
	public function setEncType($encType) {
		$this->encType = $encType;
		return $this;
	}

	/**
	 * Returns the real HTTP method for the form:
	 * GET, POST, PUT, DELETE or HEAD.
	 * As most browsers only support GET and POST in
	 * form submissions, all other HTTP methods are
	 * added as a hidden field "_method" that
	 * gets evaluated in {@link Director::direct()}.
	 * See {@link FormMethod()} to get a HTTP method
	 * for safe insertion into a <form> tag.
	 *
	 * @return string HTTP method
	 */
	public function FormHttpMethod() {
		return $this->formMethod;
	}

	/**
	 * Returns the form method to be used in the <form> tag.
	 * See {@link FormHttpMethod()} to get the "real" method.
	 *
	 * @return string Form HTTP method restricted to 'GET' or 'POST'
	 */
	public function FormMethod() {
		if(in_array($this->formMethod,array('GET','POST'))) {
			return $this->formMethod;
		} else {
			return 'POST';
		}
	}

	/**
	 * Set the form method: GET, POST, PUT, DELETE.
	 *
	 * @param string $method
	 * @param bool $strict If non-null, pass value to {@link setStrictFormMethodCheck()}.
	 * @return $this
	 */
	public function setFormMethod($method, $strict = null) {
		$this->formMethod = strtoupper($method);
		if($strict !== null) $this->setStrictFormMethodCheck($strict);
		return $this;
	}

	/**
	 * If set to true, enforce the matching of the form method.
	 *
	 * This will mean two things:
	 *  - GET vars will be ignored by a POST form, and vice versa
	 *  - A submission where the HTTP method used doesn't match the form will return a 400 error.
	 *
	 * If set to false (the default), then the form method is only used to construct the default
	 * form.
	 *
	 * @param $bool boolean
	 * @return $this
	 */
	public function setStrictFormMethodCheck($bool) {
		$this->strictFormMethodCheck = (bool)$bool;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getStrictFormMethodCheck() {
		return $this->strictFormMethodCheck;
	}

	/**
	 * Return the form's action attribute.
	 * This is build by adding an executeForm get variable to the parent controller's Link() value
	 *
	 * @return string
	 */
	public function FormAction() {
		if ($this->formActionPath) {
			return $this->formActionPath;
		} elseif($this->controller->hasMethod("FormObjectLink")) {
			return $this->controller->FormObjectLink($this->name);
		} else {
			return Controller::join_links($this->controller->Link(), $this->name);
		}
	}

	/**
	 * Set the form action attribute to a custom URL.
	 *
	 * Note: For "normal" forms, you shouldn't need to use this method.  It is
	 * recommended only for situations where you have two relatively distinct
	 * parts of the system trying to communicate via a form post.
	 *
	 * @param string $path
	 * @return $this
	 */
	public function setFormAction($path) {
		$this->formActionPath = $path;

		return $this;
	}

	/**
	 * Returns the name of the form.
	 *
	 * @return string
	 */
	public function FormName() {
		return $this->getTemplateHelper()->generateFormID($this);
	}

	/**
	 * Set the HTML ID attribute of the form.
	 *
	 * @param string $id
	 * @return $this
	 */
	public function setHTMLID($id) {
		$this->htmlID = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHTMLID() {
		return $this->htmlID;
	}

	/**
	 * Returns this form's controller.
	 *
	 * @return Controller
	 * @deprecated 4.0
	 */
	public function Controller() {
		Deprecation::notice('4.0', 'Use getController() rather than Controller() to access controller');

		return $this->getController();
	}

	/**
	 * Get the controller.
	 *
	 * @return Controller
	 */
	public function getController() {
		return $this->controller;
	}

	/**
	 * Set the controller.
	 *
	 * @param Controller $controller
	 * @return Form
	 */
	public function setController($controller) {
		$this->controller = $controller;

		return $this;
	}

	/**
	 * Get the name of the form.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set the name of the form.
	 *
	 * @param string $name
	 * @return Form
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * Returns an object where there is a method with the same name as each data
	 * field on the form.
	 *
	 * That method will return the field itself.
	 *
	 * It means that you can execute $firstName = $form->FieldMap()->FirstName()
	 */
	public function FieldMap() {
		return new Form_FieldMap($this);
	}

	/**
	 * The next functions store and modify the forms
	 * message attributes. messages are stored in session under
	 * $_SESSION[formname][message];
	 *
	 * @return string
	 */
	public function Message() {
		$this->getMessageFromSession();

		return $this->message;
	}

	/**
	 * @return string
	 */
	public function MessageType() {
		$this->getMessageFromSession();

		return $this->messageType;
	}

	/**
	 * @return string
	 */
	protected function getMessageFromSession() {
		if($this->message || $this->messageType) {
			return $this->message;
		} else {
			$this->message = Session::get("FormInfo.{$this->FormName()}.formError.message");
			$this->messageType = Session::get("FormInfo.{$this->FormName()}.formError.type");

			return $this->message;
		}
	}

	/**
	 * Set a status message for the form.
	 *
	 * @param string $message the text of the message
	 * @param string $type Should be set to good, bad, or warning.
	 * @param boolean $escapeHtml Automatically sanitize the message. Set to FALSE if the message contains HTML.
	 *                            In that case, you might want to use {@link Convert::raw2xml()} to escape any
	 *                            user supplied data in the message.
	 * @return $this
	 */
	public function setMessage($message, $type, $escapeHtml = true) {
		$this->message = ($escapeHtml) ? Convert::raw2xml($message) : $message;
		$this->messageType = $type;
		return $this;
	}

	/**
	 * Set a message to the session, for display next time this form is shown.
	 *
	 * @param string $message the text of the message
	 * @param string $type Should be set to good, bad, or warning.
	 * @param boolean $escapeHtml Automatically sanitize the message. Set to FALSE if the message contains HTML.
	 *                            In that case, you might want to use {@link Convert::raw2xml()} to escape any
	 *                            user supplied data in the message.
	 */
	public function sessionMessage($message, $type, $escapeHtml = true) {
		Session::set(
			"FormInfo.{$this->FormName()}.formError.message",
			$escapeHtml ? Convert::raw2xml($message) : $message
		);
		Session::set("FormInfo.{$this->FormName()}.formError.type", $type);
	}

	public static function messageForForm($formName, $message, $type, $escapeHtml = true) {
		Session::set(
			"FormInfo.{$formName}.formError.message",
			$escapeHtml ? Convert::raw2xml($message) : $message
		);
		Session::set("FormInfo.{$formName}.formError.type", $type);
	}

	public function clearMessage() {
		$this->message  = null;
		Session::clear("FormInfo.{$this->FormName()}.errors");
		Session::clear("FormInfo.{$this->FormName()}.formError");
		Session::clear("FormInfo.{$this->FormName()}.data");
	}

	public function resetValidation() {
		Session::clear("FormInfo.{$this->FormName()}.errors");
		Session::clear("FormInfo.{$this->FormName()}.data");
	}

	/**
	 * Returns the DataObject that has given this form its data
	 * through {@link loadDataFrom()}.
	 *
	 * @return DataObject
	 */
	public function getRecord() {
		return $this->record;
	}

	/**
	 * Get the legend value to be inserted into the
	 * <legend> element in Form.ss
	 *
	 * @return string
	 */
	public function getLegend() {
		return $this->legend;
	}

	/**
	 * Processing that occurs before a form is executed.
	 *
	 * This includes form validation, if it fails, we redirect back
	 * to the form with appropriate error messages.
	 *
	 * Triggered through {@link httpSubmission()}.
	 *
	 * Note that CSRF protection takes place in {@link httpSubmission()},
	 * if it fails the form data will never reach this method.
	 *
	 * @return boolean
	 */
	public function validate(){
		if($this->validator){
			$errors = $this->validator->validate();

			if($errors){
				// Load errors into session and post back
				$data = $this->getData();

				// Encode validation messages as XML before saving into session state
				// As per Form::addErrorMessage()
				$errors = array_map(function($error) {
					// Encode message as XML by default
					if($error['message'] instanceof DBField) {
						$error['message'] = $error['message']->forTemplate();;
					} else {
						$error['message'] = Convert::raw2xml($error['message']);
					}
					return $error;
				}, $errors);

				Session::set("FormInfo.{$this->FormName()}.errors", $errors);
				Session::set("FormInfo.{$this->FormName()}.data", $data);

				return false;
			}
		}

		return true;
	}

	const MERGE_DEFAULT = 0;
	const MERGE_CLEAR_MISSING = 1;
	const MERGE_IGNORE_FALSEISH = 2;

	/**
	 * Load data from the given DataObject or array.
	 *
	 * It will call $object->MyField to get the value of MyField.
	 * If you passed an array, it will call $object[MyField].
	 * Doesn't save into dataless FormFields ({@link DatalessField}),
	 * as determined by {@link FieldList->dataFields()}.
	 *
	 * By default, if a field isn't set (as determined by isset()),
	 * its value will not be saved to the field, retaining
	 * potential existing values.
	 *
	 * Passed data should not be escaped, and is saved to the FormField instances unescaped.
	 * Escaping happens automatically on saving the data through {@link saveInto()}.
	 *
	 * Escaping happens automatically on saving the data through
	 * {@link saveInto()}.
	 *
	 * @uses FieldList->dataFields()
	 * @uses FormField->setValue()
	 *
	 * @param array|DataObject $data
	 * @param int $mergeStrategy
	 *  For every field, {@link $data} is interrogated whether it contains a relevant property/key, and
	 *  what that property/key's value is.
	 *
	 *  By default, if {@link $data} does contain a property/key, the fields value is always replaced by {@link $data}'s
	 *  value, even if that value is null/false/etc. Fields which don't match any property/key in {@link $data} are
	 *  "left alone", meaning they retain any previous value.
	 *
	 *  You can pass a bitmask here to change this behaviour.
	 *
	 *  Passing CLEAR_MISSING means that any fields that don't match any property/key in
	 *  {@link $data} are cleared.
	 *
	 *  Passing IGNORE_FALSEISH means that any false-ish value in {@link $data} won't replace
	 *  a field's value.
	 *
	 *  For backwards compatibility reasons, this parameter can also be set to === true, which is the same as passing
	 *  CLEAR_MISSING
	 *
	 * @param FieldList $fieldList An optional list of fields to process.  This can be useful when you have a
	 * form that has some fields that save to one object, and some that save to another.
	 * @return Form
	 */
	public function loadDataFrom($data, $mergeStrategy = 0, $fieldList = null) {
		if(!is_object($data) && !is_array($data)) {
			user_error("Form::loadDataFrom() not passed an array or an object", E_USER_WARNING);
			return $this;
		}

		// Handle the backwards compatible case of passing "true" as the second argument
		if ($mergeStrategy === true) {
			$mergeStrategy = self::MERGE_CLEAR_MISSING;
		}
		else if ($mergeStrategy === false) {
			$mergeStrategy = 0;
		}

		// if an object is passed, save it for historical reference through {@link getRecord()}
		if(is_object($data)) $this->record = $data;

		// dont include fields without data
		$dataFields = $this->Fields()->dataFields();
		if($dataFields) foreach($dataFields as $field) {
			$name = $field->getName();

			// Skip fields that have been excluded
			if($fieldList && !in_array($name, $fieldList)) continue;

			// First check looks for (fieldname)_unchanged, an indicator that we shouldn't overwrite the field value
			if(is_array($data) && isset($data[$name . '_unchanged'])) continue;

			// Does this property exist on $data?
			$exists = false;
			// The value from $data for this field
			$val = null;

			if(is_object($data)) {
				$exists = (
					isset($data->$name) ||
					$data->hasMethod($name) ||
					($data->hasMethod('hasField') && $data->hasField($name))
				);

				if ($exists) {
					$val = $data->__get($name);
				}
			}
			else if(is_array($data)){
				if(array_key_exists($name, $data)) {
					$exists = true;
					$val = $data[$name];
				}
				// If field is in array-notation we need to access nested data
				else if(strpos($name,'[')) {
					// First encode data using PHP's method of converting nested arrays to form data
					$flatData = urldecode(http_build_query($data));
					// Then pull the value out from that flattened string
					preg_match('/' . addcslashes($name,'[]') . '=([^&]*)/', $flatData, $matches);

					if (isset($matches[1])) {
						$exists = true;
						$val = $matches[1];
					}
				}
			}

			// save to the field if either a value is given, or loading of blank/undefined values is forced
			if($exists){
				if ($val != false || ($mergeStrategy & self::MERGE_IGNORE_FALSEISH) != self::MERGE_IGNORE_FALSEISH){
					// pass original data as well so composite fields can act on the additional information
					$field->setValue($val, $data);
				}
			}
			else if(($mergeStrategy & self::MERGE_CLEAR_MISSING) == self::MERGE_CLEAR_MISSING){
				$field->setValue($val, $data);
			}
		}

		return $this;
	}

	/**
	 * Save the contents of this form into the given data object.
	 * It will make use of setCastedField() to do this.
	 *
	 * @param DataObjectInterface $dataObject The object to save data into
	 * @param FieldList $fieldList An optional list of fields to process.  This can be useful when you have a
	 * form that has some fields that save to one object, and some that save to another.
	 */
	public function saveInto(DataObjectInterface $dataObject, $fieldList = null) {
		$dataFields = $this->fields->saveableFields();
		$lastField = null;
		if($dataFields) foreach($dataFields as $field) {
			// Skip fields that have been excluded
			if($fieldList && is_array($fieldList) && !in_array($field->getName(), $fieldList)) continue;


			$saveMethod = "save{$field->getName()}";

			if($field->getName() == "ClassName"){
				$lastField = $field;
			}else if( $dataObject->hasMethod( $saveMethod ) ){
				$dataObject->$saveMethod( $field->dataValue());
			} else if($field->getName() != "ID"){
				$field->saveInto($dataObject);
			}
		}
		if($lastField) $lastField->saveInto($dataObject);
	}

	/**
	 * Get the submitted data from this form through
	 * {@link FieldList->dataFields()}, which filters out
	 * any form-specific data like form-actions.
	 * Calls {@link FormField->dataValue()} on each field,
	 * which returns a value suitable for insertion into a DataObject
	 * property.
	 *
	 * @return array
	 */
	public function getData() {
		$dataFields = $this->fields->dataFields();
		$data = array();

		if($dataFields){
			foreach($dataFields as $field) {
				if($field->getName()) {
					$data[$field->getName()] = $field->dataValue();
				}
			}
		}

		return $data;
	}

	/**
	 * Call the given method on the given field.
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function callfieldmethod($data) {
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
	 *
	 * This is returned when you access a form as $FormObject rather
	 * than <% with FormObject %>
	 *
	 * @return HTML
	 */
	public function forTemplate() {
		$return = $this->renderWith(array_merge(
			(array)$this->getTemplate(),
			array('Form')
		));

		// Now that we're rendered, clear message
		$this->clearMessage();

		return $return;
	}

	/**
	 * Return a rendered version of this form, suitable for ajax post-back.
	 *
	 * It triggers slightly different behaviour, such as disabling the rewriting
	 * of # links.
	 *
	 * @return HTML
	 */
	public function forAjaxTemplate() {
		$view = new SSViewer(array(
			$this->getTemplate(),
			'Form'
		));

		$return = $view->dontRewriteHashlinks()->process($this);

		// Now that we're rendered, clear message
		$this->clearMessage();

		return $return;
	}

	/**
	 * Returns an HTML rendition of this form, without the <form> tag itself.
	 *
	 * Attaches 3 extra hidden files, _form_action, _form_name, _form_method,
	 * and _form_enctype.  These are the attributes of the form.  These fields
	 * can be used to send the form to Ajax.
	 *
	 * @return HTML
	 */
	public function formHtmlContent() {
		$this->IncludeFormTag = false;
		$content = $this->forTemplate();
		$this->IncludeFormTag = true;

		$content .= "<input type=\"hidden\" name=\"_form_action\" id=\"" . $this->FormName . "_form_action\""
			. " value=\"" . $this->FormAction() . "\" />\n";
		$content .= "<input type=\"hidden\" name=\"_form_name\" value=\"" . $this->FormName() . "\" />\n";
		$content .= "<input type=\"hidden\" name=\"_form_method\" value=\"" . $this->FormMethod() . "\" />\n";
		$content .= "<input type=\"hidden\" name=\"_form_enctype\" value=\"" . $this->getEncType() . "\" />\n";

		return $content;
	}

	/**
	 * Render this form using the given template, and return the result as a string
	 * You can pass either an SSViewer or a template name
	 * @param string|array $template
	 * @return HTMLText
	 */
	public function renderWithoutActionButton($template) {
		$custom = $this->customise(array(
			"Actions" => "",
		));

		if(is_string($template)) {
			$template = new SSViewer($template);
		}

		return $template->process($custom);
	}


	/**
	 * Sets the button that was clicked.  This should only be called by the Controller.
	 *
	 * @param callable $funcName The name of the action method that will be called.
	 * @return $this
	 */
	public function setButtonClicked($funcName) {
		$this->buttonClickedFunc = $funcName;

		return $this;
	}

	/**
	 * @return FormAction
	 */
	public function buttonClicked() {
		foreach($this->actions->dataFields() as $action) {
			if($action->hasMethod('actionname') && $this->buttonClickedFunc == $action->actionName()) {
				return $action;
			}
		}
	}

	/**
	 * Return the default button that should be clicked when another one isn't
	 * available.
	 *
	 * @return FormAction
	 */
	public function defaultAction() {
		if($this->hasDefaultAction && $this->actions) {
			return $this->actions->First();
	}
	}

	/**
	 * Disable the default button.
	 *
	 * Ordinarily, when a form is processed and no action_XXX button is
	 * available, then the first button in the actions list will be pressed.
	 * However, if this is "delete", for example, this isn't such a good idea.
	 *
	 * @return Form
	 */
	public function disableDefaultAction() {
		$this->hasDefaultAction = false;

		return $this;
	}

	/**
	 * Disable the requirement of a security token on this form instance. This
	 * security protects against CSRF attacks, but you should disable this if
	 * you don't want to tie a form to a session - eg a search form.
	 *
	 * Check for token state with {@link getSecurityToken()} and
	 * {@link SecurityToken->isEnabled()}.
	 *
	 * @return Form
	 */
	public function disableSecurityToken() {
		$this->securityToken = new NullSecurityToken();

		return $this;
	}

	/**
	 * Enable {@link SecurityToken} protection for this form instance.
	 *
	 * Check for token state with {@link getSecurityToken()} and
	 * {@link SecurityToken->isEnabled()}.
	 *
	 * @return Form
	 */
	public function enableSecurityToken() {
		$this->securityToken = new SecurityToken();

		return $this;
	}

	/**
	 * Returns the security token for this form (if any exists).
	 *
	 * Doesn't check for {@link securityTokenEnabled()}.
	 *
	 * Use {@link SecurityToken::inst()} to get a global token.
	 *
	 * @return SecurityToken|null
	 */
	public function getSecurityToken() {
		return $this->securityToken;
	}

	/**
	 * Returns the name of a field, if that's the only field that the current
	 * controller is interested in.
	 *
	 * It checks for a call to the callfieldmethod action.
	 *
	 * @return string
	 */
	public static function single_field_required() {
		if(self::current_action() == 'callfieldmethod') {
			return $_REQUEST['fieldName'];
	}
	}

	/**
	 * Return the current form action being called, if available.
	 *
	 * @return string
	 */
	public static function current_action() {
		return self::$current_action;
	}

	/**
	 * Set the current form action. Should only be called by {@link Controller}.
	 *
	 * @param string $action
	 */
	public static function set_current_action($action) {
		self::$current_action = $action;
	}

	/**
	 * Compiles all CSS-classes.
	 *
	 * @return string
	 */
	public function extraClass() {
		return implode(array_unique($this->extraClasses), ' ');
	}

	/**
	 * Add a CSS-class to the form-container. If needed, multiple classes can
	 * be added by delimiting a string with spaces.
	 *
	 * @param string $class A string containing a classname or several class
	 *                names delimited by a single space.
	 * @return $this
	 */
	public function addExtraClass($class) {
		//split at white space
		$classes = preg_split('/\s+/', $class);
		foreach($classes as $class) {
			//add classes one by one
			$this->extraClasses[$class] = $class;
		}
		return $this;
	}

	/**
	 * Remove a CSS-class from the form-container. Multiple class names can
	 * be passed through as a space delimited string
	 *
	 * @param string $class
	 * @return $this
	 */
	public function removeExtraClass($class) {
		//split at white space
		$classes = preg_split('/\s+/', $class);
		foreach ($classes as $class) {
			//unset one by one
			unset($this->extraClasses[$class]);
		}
		return $this;
	}

	public function debug() {
		$result = "<h3>$this->class</h3><ul>";
		foreach($this->fields as $field) {
			$result .= "<li>$field" . $field->debug() . "</li>";
		}
		$result .= "</ul>";

		if( $this->validator )
			$result .= '<h3>'._t('Form.VALIDATOR', 'Validator').'</h3>' . $this->validator->debug();

		return $result;
	}


	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// TESTING HELPERS
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Test a submission of this form.
	 * @param string $action
	 * @param array $data
	 * @return SS_HTTPResponse the response object that the handling controller produces.  You can interrogate this in
	 * your unit test.
	 * @throws SS_HTTPResponse_Exception
	 */
	public function testSubmission($action, $data) {
		$data['action_' . $action] = true;

		return Director::test($this->FormAction(), $data, Controller::curr()->getSession());
	}

	/**
	 * Test an ajax submission of this form.
	 *
	 * @param string $action
	 * @param array $data
	 * @return SS_HTTPResponse the response object that the handling controller produces.  You can interrogate this in
	 * your unit test.
	 */
	public function testAjaxSubmission($action, $data) {
		$data['ajax'] = 1;
		return $this->testSubmission($action, $data);
	}
}

/**
 * @package forms
 * @subpackage core
 */
class Form_FieldMap extends ViewableData {

	protected $form;

	public function __construct($form) {
		$this->form = $form;
		parent::__construct();
	}

	/**
	 * Ensure that all potential method calls get passed to __call(), therefore to dataFieldByName
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method) {
		return true;
	}

	public function __call($method, $args = null) {
		return $this->form->Fields()->fieldByName($method);
	}
}
