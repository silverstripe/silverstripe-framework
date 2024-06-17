<?php

namespace SilverStripe\Forms;

use BadMethodCallException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HasRequestHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Session;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\NullSecurityToken;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\AttributesHTML;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;

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
 */
class Form extends ViewableData implements HasRequestHandler
{
    use AttributesHTML;
    use FormMessage;

    /**
     */
    const DEFAULT_NAME = 'Form';

    /**
     * Form submission data is URL encoded
     */
    const ENC_TYPE_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * Form submission data is multipart form
     */
    const ENC_TYPE_MULTIPART  = 'multipart/form-data';

    /**
     * Accessed by Form.ss.
     * A performance enhancement over the generate-the-form-tag-and-then-remove-it code that was there previously
     *
     * @var bool
     */
    public $IncludeFormTag = true;

    /**
     * @var FieldList
     */
    protected $fields;

    /**
     * @var FieldList
     */
    protected $actions;

    /**
     * Parent (optional) request handler
     *
     * @var RequestHandler
     */
    protected $controller;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @see setValidationResponseCallback()
     * @var callable
     */
    protected $validationResponseCallback;

    /**
     * @var string
     */
    protected $formMethod = "POST";

    /**
     * @var boolean
     */
    protected $strictFormMethodCheck = true;

    /**
     * Populated by {@link loadDataFrom()}.
     *
     * @var ViewableData|null
     */
    protected $record;

    /**
     * Keeps track of whether this form has a default action or not.
     * Set to false by $this->disableDefaultAction();
     *
     * @var bool
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
     * another template for customization.
     *
     * @see Form::setTemplate()
     * @var string|array|null
     */
    protected $template;

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
     * List of additional CSS classes for the form tag.
     *
     * @var array
     */
    protected $extraClasses = [];

    /**
     * @config
     * @var array $default_classes The default classes to apply to the Form
     */
    private static $default_classes = [];

    /**
     * @var string|null
     */
    protected $encType;

    /**
     * Any custom form attributes set through {@link setAttributes()}.
     * Some attributes are calculated on the fly, so please use {@link getAttributes()} to access them.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $validationExemptActions = [];

    /**
     * @config
     * @var array
     */
    private static $casting = [
        'AttributesHTML' => 'HTMLFragment', // property $AttributesHTML version
        'getAttributesHTML' => 'HTMLFragment', // method $getAttributesHTML($arg) version
        'FormAttributes' => 'HTMLFragment',
        'FormName' => 'Text',
        'Legend' => 'HTMLFragment',
    ];

    /**
     * @var FormTemplateHelper
     */
    private $templateHelper = null;

    /**
     * HTML ID for this form.
     *
     * @var string
     */
    private $htmlID = null;

    /**
     * Custom form action path, if not linking to itself.
     * E.g. could be used to post to an external link
     *
     * @var string
     */
    protected $formActionPath = false;

    /**
     * @var bool
     */
    protected $securityTokenAdded = false;

    /**
     * @var bool
     */
    protected $notifyUnsavedChanges = false;

    /**
     * Create a new form, with the given fields an action buttons.
     *
     * @param RequestHandler $controller Optional parent request handler
     * @param string $name The method on the controller that will return this form object.
     * @param FieldList $fields All of the fields in the form - a {@link FieldList} of {@link FormField} objects.
     * @param FieldList $actions All of the action buttons in the form - a {@link FieldLis} of
     *                           {@link FormAction} objects
     * @param Validator|null $validator Override the default validator instance (Default: {@link RequiredFields})
     */
    public function __construct(
        RequestHandler $controller = null,
        $name = Form::DEFAULT_NAME,
        FieldList $fields = null,
        FieldList $actions = null,
        Validator $validator = null
    ) {
        parent::__construct();

        $fields = $fields ? $fields : FieldList::create();
        $actions = $actions ? $actions : FieldList::create();

        $fields->setForm($this);
        $actions->setForm($this);

        $this->fields = $fields;
        $this->actions = $actions;
        $this->setController($controller);
        $this->setName($name);

        // Form validation
        $this->validator = ($validator) ? $validator : new RequiredFields();
        $this->validator->setForm($this);

        // Form error controls
        $this->restoreFormState();

        // Check if CSRF protection is enabled, either on the parent controller or from the default setting. Note that
        // method_exists() is used as some controllers (e.g. GroupTest) do not always extend from Object.
        if (ClassInfo::hasMethod($controller, 'securityTokenEnabled')) {
            $securityEnabled = $controller->securityTokenEnabled();
        } else {
            $securityEnabled = SecurityToken::is_enabled();
        }

        $this->securityToken = ($securityEnabled) ? new SecurityToken() : new NullSecurityToken();

        $this->setupDefaultClasses();
    }

    /**
     * @return bool
     */
    public function getNotifyUnsavedChanges()
    {
        return $this->notifyUnsavedChanges;
    }

    /**
     * @param bool $flag
     */
    public function setNotifyUnsavedChanges($flag)
    {
        $this->notifyUnsavedChanges = $flag;
    }

    /**
     * Load form state from session state
     *
     * @return $this
     */
    public function restoreFormState()
    {
        // Restore messages
        $result = $this->getSessionValidationResult();
        if (isset($result)) {
            $this->loadMessagesFrom($result);
        }

        // load data in from previous submission upon error
        $data = $this->getSessionData();
        if (isset($data)) {
            $this->loadDataFrom($data, Form::MERGE_AS_INTERNAL_VALUE);
        }
        return $this;
    }

    /**
     * Flush persistent form state details
     *
     * @return $this
     */
    public function clearFormState()
    {
        $this
            ->getSession()
            ->clear("FormInfo.{$this->FormName()}.result")
            ->clear("FormInfo.{$this->FormName()}.data");
        return $this;
    }

    /**
     * Helper to get current request for this form
     *
     * @return HTTPRequest|null
     */
    protected function getRequest()
    {
        // Check if current request handler has a request object
        $controller = $this->getController();
        if ($controller && !($controller->getRequest() instanceof NullHTTPRequest)) {
            return $controller->getRequest();
        }
        // Fall back to current controller
        if (Controller::has_curr() && !(Controller::curr()->getRequest() instanceof NullHTTPRequest)) {
            return Controller::curr()->getRequest();
        }
        return null;
    }

    /**
     * Get session for this form
     *
     * @return Session
     */
    protected function getSession()
    {
        $request = $this->getRequest();
        if ($request) {
            return $request->getSession();
        }
        throw new BadMethodCallException("Session not available in the current context");
    }

    /**
     * Return any form data stored in the session
     *
     * @return array
     */
    public function getSessionData()
    {
        return $this->getSession()->get("FormInfo.{$this->FormName()}.data");
    }

    /**
     * Store the given form data in the session
     *
     * @param array $data
     * @return $this
     */
    public function setSessionData($data)
    {
        $this->getSession()->set("FormInfo.{$this->FormName()}.data", $data);
        return $this;
    }

    /**
     * Return any ValidationResult instance stored for this object
     *
     * @return ValidationResult|null The ValidationResult object stored in the session
     */
    public function getSessionValidationResult()
    {
        $resultData = $this->getSession()->get("FormInfo.{$this->FormName()}.result");
        if (isset($resultData)) {
            return unserialize($resultData ?? '');
        }
        return null;
    }

    /**
     * Sets the ValidationResult in the session to be used with the next view of this form.
     * @param ValidationResult $result The result to save
     * @param bool $combineWithExisting If true, then this will be added to the existing result.
     * @return $this
     */
    public function setSessionValidationResult(ValidationResult $result, $combineWithExisting = false)
    {
        // Combine with existing result
        if ($combineWithExisting) {
            $existingResult = $this->getSessionValidationResult();
            if ($existingResult) {
                if ($result) {
                    $existingResult->combineAnd($result);
                } else {
                    $result = $existingResult;
                }
            }
        }

        // Serialise
        $resultData = $result ? serialize($result) : null;
        $this->getSession()->set("FormInfo.{$this->FormName()}.result", $resultData);
        return $this;
    }

    /**
     * Clear form message (and in session)
     *
     * @return $this
     */
    public function clearMessage()
    {
        $this->setMessage(null);
        $this->clearFormState();
        return $this;
    }

    /**
     * Populate this form with messages from the given ValidationResult.
     * Note: This will try not to clear any pre-existing messages, but will clear them
     * if a new message has a different message type or cast than the existing ones.
     *
     * @param ValidationResult $result
     * @return $this
     */
    public function loadMessagesFrom($result)
    {
        // Set message on either a field or the parent form
        foreach ($result->getMessages() as $message) {
            $fieldName = $message['fieldName'];

            if ($fieldName) {
                $owner = $this->fields->dataFieldByName($fieldName) ?: $this;
            } else {
                $owner = $this;
            }

            $owner->appendMessage($message['message'], $message['messageType'], $message['messageCast'], true);
        }
        return $this;
    }

    /**
     * Set message on a given field name. This message will not persist via redirect.
     *
     * @param string $fieldName
     * @param string $message
     * @param string $messageType
     * @param string $messageCast
     * @return $this
     */
    public function setFieldMessage(
        $fieldName,
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $messageCast = ValidationResult::CAST_TEXT
    ) {
        $field = $this->fields->dataFieldByName($fieldName);
        if ($field) {
            $field->setMessage($message, $messageType, $messageCast);
        }
        return $this;
    }

    public function castingHelper($field)
    {
        // Override casting for field message
        if (strcasecmp($field ?? '', 'Message') === 0 && ($helper = $this->getMessageCastingHelper())) {
            return $helper;
        }
        return parent::castingHelper($field);
    }

    /**
     * set up the default classes for the form. This is done on construct so that the default classes can be removed
     * after instantiation
     */
    protected function setupDefaultClasses()
    {
        $defaultClasses = static::config()->get('default_classes');
        if ($defaultClasses) {
            foreach ($defaultClasses as $class) {
                $this->addExtraClass($class);
            }
        }
    }

    /**
     * @return callable
     */
    public function getValidationResponseCallback()
    {
        return $this->validationResponseCallback;
    }

    /**
     * Overrules validation error behaviour in {@link httpSubmission()}
     * when validation has failed. Useful for optional handling of a certain accepted content type.
     *
     * The callback can opt out of handling specific responses by returning NULL,
     * in which case the default form behaviour will kick in.
     *
     * @param $callback
     * @return Form
     */
    public function setValidationResponseCallback($callback)
    {
        $this->validationResponseCallback = $callback;

        return $this;
    }

    /**
     * Convert this form into a readonly form
     *
     * @return $this
     */
    public function makeReadonly()
    {
        $this->transform(new ReadonlyTransformation());
        return $this;
    }

    /**
     * Set whether the user should be redirected back down to the
     * form on the page upon validation errors in the form or if
     * they just need to redirect back to the page
     *
     * @param bool $bool Redirect to form on error?
     * @return $this
     */
    public function setRedirectToFormOnValidationError($bool)
    {
        $this->redirectToFormOnValidationError = $bool;
        return $this;
    }

    /**
     * Get whether the user should be redirected back down to the
     * form on the page upon validation errors
     *
     * @return bool
     */
    public function getRedirectToFormOnValidationError()
    {
        return $this->redirectToFormOnValidationError;
    }

    /**
     * @param FormTransformation $trans
     */
    public function transform(FormTransformation $trans)
    {
        $newFields = new FieldList();
        foreach ($this->fields as $field) {
            $newFields->push($field->transform($trans));
        }
        $this->fields = $newFields;

        $newActions = new FieldList();
        foreach ($this->actions as $action) {
            $newActions->push($action->transform($trans));
        }
        $this->actions = $newActions;


        // We have to remove validation, if the fields are not editable ;-)
        if ($this->validator) {
            $this->validator->removeValidation();
        }
    }

    /**
     * Get the {@link Validator} attached to this form.
     * @return Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set the {@link Validator} on this form.
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        if ($validator) {
            $this->validator = $validator;
            $this->validator->setForm($this);
        }
        return $this;
    }

    /**
     * Remove the {@link Validator} from this from.
     */
    public function unsetValidator()
    {
        $this->validator = null;
        return $this;
    }

    /**
     * Set actions that are exempt from validation
     *
     * @param array $actions
     * @return $this
     */
    public function setValidationExemptActions($actions)
    {
        $this->validationExemptActions = $actions;
        return $this;
    }

    /**
     * Get a list of actions that are exempt from validation
     *
     * @return array
     */
    public function getValidationExemptActions()
    {
        return $this->validationExemptActions;
    }

    /**
     * Passed a FormAction, returns true if that action is exempt from Form validation
     *
     * @param FormAction $action
     * @return bool
     */
    public function actionIsValidationExempt($action)
    {
        // Non-actions don't bypass validation
        if (!$action) {
            return false;
        }
        if ($action->getValidationExempt()) {
            return true;
        }
        if (in_array($action->actionName(), $this->getValidationExemptActions() ?? [])) {
            return true;
        }
        return false;
    }

    /**
     * Generate extra special fields - namely the security token field (if required).
     *
     * @return FieldList
     */
    public function getExtraFields()
    {
        $extraFields = new FieldList();

        $token = $this->getSecurityToken();
        if ($token) {
            $tokenField = $token->updateFieldSet($this->fields);
            if ($tokenField) {
                $tokenField->setForm($this);
            }
        }
        $this->securityTokenAdded = true;

        // add the "real" HTTP method if necessary (for PUT, DELETE and HEAD)
        if (strtoupper($this->FormMethod() ?? '') != $this->FormHttpMethod()) {
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
    public function Fields()
    {
        foreach ($this->getExtraFields() as $field) {
            if (!$this->fields->fieldByName($field->getName())) {
                $this->fields->push($field);
            }
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
    public function HiddenFields()
    {
        return $this->Fields()->HiddenFields();
    }

    /**
     * Return all fields except for the hidden fields.
     * Useful when making your own simplified form layouts.
     */
    public function VisibleFields()
    {
        return $this->Fields()->VisibleFields();
    }

    /**
     * Setter for the form fields.
     *
     * @param FieldList $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $fields->setForm($this);
        $this->fields = $fields;

        return $this;
    }

    /**
     * Return the form's action buttons - used by the templates
     *
     * @return FieldList The action list
     */
    public function Actions()
    {
        return $this->actions;
    }

    /**
     * Setter for the form actions.
     *
     * @param FieldList $actions
     * @return $this
     */
    public function setActions($actions)
    {
        $actions->setForm($this);
        $this->actions = $actions;

        return $this;
    }

    /**
     * Unset all form actions
     */
    public function unsetAllActions()
    {
        $this->actions = new FieldList();
        return $this;
    }

    protected function getDefaultAttributes(): array
    {
        $attrs = [
            'id' => $this->FormName(),
            'action' => $this->FormAction(),
            'method' => $this->FormMethod(),
            'enctype' => $this->getEncType(),
            'target' => $this->target,
            'class' => $this->extraClass(),
        ];

        if ($this->validator && $this->validator->getErrors()) {
            if (!isset($attrs['class'])) {
                $attrs['class'] = '';
            }
            $attrs['class'] .= ' validationerror';
        }

        return $attrs;
    }

    public function FormAttributes()
    {
        return $this->getAttributesHTML();
    }

    /**
     * Set the target of this form to any value - useful for opening the form contents in a new window or refreshing
     * another frame
    *
     * @param string|FormTemplateHelper $helper
    */
    public function setTemplateHelper($helper)
    {
        $this->templateHelper = $helper;
    }

    /**
     * Return a {@link FormTemplateHelper} for this form. If one has not been
     * set, return the default helper.
     *
     * @return FormTemplateHelper
     */
    public function getTemplateHelper()
    {
        if ($this->templateHelper) {
            if (is_string($this->templateHelper)) {
                return Injector::inst()->get($this->templateHelper);
            }

            return $this->templateHelper;
        }

        return FormTemplateHelper::singleton();
    }

    /**
     * Set the target of this form to any value - useful for opening the form
     * contents in a new window or refreshing another frame.
     *
     * @param string $target The value of the target
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Set the legend value to be inserted into
     * the <legend> element in the Form.ss template.
     * @param string $legend
     * @return $this
     */
    public function setLegend($legend)
    {
        $this->legend = $legend;
        return $this;
    }

    /**
     * Set the SS template that this form should use
     * to render with. The default is "Form".
     *
     * @param string|array $template The name of the template (without the .ss extension) or array form
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Return the template to render this form with.
     *
     * @return string|array
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Returns the ordered list of preferred templates for rendering this form
     * If the template isn't set, then default to the
     * form class name e.g "Form".
     *
     * @return array
     */
    public function getTemplates()
    {
        $templates = SSViewer::get_templates_by_class(static::class, '', __CLASS__);
        // Prefer any custom template
        if ($this->getTemplate()) {
            array_unshift($templates, $this->getTemplate());
        }
        return $templates;
    }

    /**
     * Returns the encoding type for the form.
     *
     * By default this will be URL encoded, unless there is a file field present
     * in which case multipart is used. You can also set the enc type using
     * {@link setEncType}.
     */
    public function getEncType()
    {
        if ($this->encType) {
            return $this->encType;
        }

        if ($fields = $this->fields->dataFields()) {
            foreach ($fields as $field) {
                if ($field instanceof FileField) {
                    return Form::ENC_TYPE_MULTIPART;
                }
            }
        }

        return Form::ENC_TYPE_URLENCODED;
    }

    /**
     * Sets the form encoding type. The most common encoding types are defined
     * in {@link ENC_TYPE_URLENCODED} and {@link ENC_TYPE_MULTIPART}.
     *
     * @param string $encType
     * @return $this
     */
    public function setEncType($encType)
    {
        $this->encType = $encType;
        return $this;
    }

    /**
     * Returns the real HTTP method for the form:
     * GET, POST, PUT, DELETE or HEAD.
     * As most browsers only support GET and POST in
     * form submissions, all other HTTP methods are
     * added as a hidden field "_method" that
     * gets evaluated in {@link HTTPRequest::detect_method()}.
     * See {@link FormMethod()} to get a HTTP method
     * for safe insertion into a <form> tag.
     *
     * @return string HTTP method
     */
    public function FormHttpMethod()
    {
        return $this->formMethod;
    }

    /**
     * Returns the form method to be used in the <form> tag.
     * See {@link FormHttpMethod()} to get the "real" method.
     *
     * @return string Form HTTP method restricted to 'GET' or 'POST'
     */
    public function FormMethod()
    {
        if (in_array($this->formMethod, ['GET','POST'])) {
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
    public function setFormMethod($method, $strict = null)
    {
        $this->formMethod = strtoupper($method ?? '');
        if ($strict !== null) {
            $this->setStrictFormMethodCheck($strict);
        }
        return $this;
    }

    /**
     * If set to true (the default), enforces the matching of the form method.
     *
     * This will mean two things:
     *  - GET vars will be ignored by a POST form, and vice versa
     *  - A submission where the HTTP method used doesn't match the form will return a 400 error.
     *
     * If set to false then the form method is only used to construct the default
     * form.
     *
     * @param $bool boolean
     * @return $this
     */
    public function setStrictFormMethodCheck($bool)
    {
        $this->strictFormMethodCheck = (bool)$bool;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getStrictFormMethodCheck()
    {
        return $this->strictFormMethodCheck;
    }

    /**
     * Return the form's action attribute.
     * This is build by adding an executeForm get variable to the parent controller's Link() value
     *
     * @return string
     */
    public function FormAction()
    {
        if ($this->formActionPath) {
            return $this->formActionPath;
        }

        // Get action from request handler link
        return $this->getRequestHandler()->Link();
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
    public function setFormAction($path)
    {
        $this->formActionPath = $path;

        return $this;
    }

    /**
     * Returns the name of the form.
     *
     * @return string
     */
    public function FormName()
    {
        return $this->getTemplateHelper()->generateFormID($this);
    }

    /**
     * Set the HTML ID attribute of the form.
     *
     * @param string $id
     * @return $this
     */
    public function setHTMLID($id)
    {
        $this->htmlID = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getHTMLID()
    {
        return $this->htmlID;
    }

    /**
     * Get the controller or parent request handler.
     *
     * @return RequestHandler
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Set the controller or parent request handler.
     *
     * @param RequestHandler $controller
     * @return $this
     */
    public function setController(RequestHandler $controller = null)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Get the name of the form.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the form.
     *
     * @param string $name
     * @return Form
     */
    public function setName($name)
    {
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
    public function FieldMap()
    {
        return new Form_FieldMap($this);
    }

    /**
     * Set a message to the session, for display next time this form is shown.
     *
     * @param string $message the text of the message
     * @param string $type Should be set to good, bad, or warning.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     */
    public function sessionMessage($message, $type = ValidationResult::TYPE_ERROR, $cast = ValidationResult::CAST_TEXT)
    {
        $this->setMessage($message, $type, $cast);
        $result = $this->getSessionValidationResult() ?: ValidationResult::create();
        $result->addMessage($message, $type, null, $cast);
        $this->setSessionValidationResult($result);
    }

    /**
     * Set an error to the session, for display next time this form is shown.
     *
     * @param string $message the text of the message
     * @param string $type Should be set to good, bad, or warning.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     */
    public function sessionError($message, $type = ValidationResult::TYPE_ERROR, $cast = ValidationResult::CAST_TEXT)
    {
        $this->setMessage($message, $type, $cast);
        $result = $this->getSessionValidationResult() ?: ValidationResult::create();
        $result->addError($message, $type, null, $cast);
        $this->setSessionValidationResult($result);
    }

    /**
     * Set an error message for a field in the session, for display next time this form is shown.
     *
     * @param string $message the text of the message
     * @param string $fieldName Name of the field to set the error message on it.
     * @param string $type Should be set to good, bad, or warning.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     */
    public function sessionFieldError($message, $fieldName, $type = ValidationResult::TYPE_ERROR, $cast = ValidationResult::CAST_TEXT)
    {
        $this->setMessage($message, $type, $cast);
        $result = $this->getSessionValidationResult() ?: ValidationResult::create();
        $result->addFieldMessage($fieldName, $message, $type, null, $cast);
        $this->setSessionValidationResult($result);
    }

    /**
     * Returns the record that has given this form its data
     * through {@link loadDataFrom()}.
     *
     * @return ViewableData
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Get the legend value to be inserted into the
     * <legend> element in Form.ss
     *
     * @return string
     */
    public function getLegend()
    {
        return $this->legend;
    }

    /**
     * Processing that occurs before a form is executed.
     *
     * This includes form validation, if it fails, we throw a ValidationException
     *
     * This includes form validation, if it fails, we redirect back
     * to the form with appropriate error messages.
     * Always return true if the current form action is exempt from validation
     *
     * Triggered through {@link httpSubmission()}.
     *
     *
     * Note that CSRF protection takes place in {@link httpSubmission()},
     * if it fails the form data will never reach this method.
     *
     * @return ValidationResult
     */
    public function validationResult()
    {
        // Automatically pass if there is no validator, or the clicked button is exempt
        // Note: Soft support here for validation with absent request handler
        $handler = $this->getRequestHandler();
        $action = $handler ? $handler->buttonClicked() : null;
        $validator = $this->getValidator();
        if (!$validator || $this->actionIsValidationExempt($action)) {
            return ValidationResult::create();
        }

        // Invoke validator
        $result = $validator->validate();
        $this->loadMessagesFrom($result);
        return $result;
    }

    const MERGE_DEFAULT             = 0b0000;
    const MERGE_CLEAR_MISSING       = 0b0001;
    const MERGE_IGNORE_FALSEISH     = 0b0010;
    const MERGE_AS_INTERNAL_VALUE   = 0b0100;
    const MERGE_AS_SUBMITTED_VALUE  = 0b1000;

    /**
     * Load data from the given record or array.
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
     * @uses FieldList::dataFields()
     * @uses FormField::setSubmittedValue()
     * @uses FormField::setValue()
     *
     * @param array|ViewableData $data
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
     *  Passing MERGE_CLEAR_MISSING means that any fields that don't match any property/key in
     *  {@link $data} are cleared.
     *
     *  Passing MERGE_IGNORE_FALSEISH means that any false-ish value in {@link $data} won't replace
     *  a field's value.
     *
     *  Passing MERGE_AS_INTERNAL_VALUE forces the data to be parsed using the internal representation of the matching
     *  form field. This is helpful if you are loading an array of values retrieved from `Form::getData()` and you
     *  do not want them parsed as submitted data. MERGE_AS_SUBMITTED_VALUE does the opposite and forces the data to be
     *  parsed as it would be submitted from a form.
     *
     *  For backwards compatibility reasons, this parameter can also be set to === true, which is the same as passing
     *  MERGE_CLEAR_MISSING
     *
     * @param array $fieldList An optional list of fields to process.  This can be useful when you have a
     * form that has some fields that save to one object, and some that save to another.
     * @return $this
     */
    public function loadDataFrom($data, $mergeStrategy = 0, $fieldList = null)
    {
        if (!is_object($data) && !is_array($data)) {
            user_error("Form::loadDataFrom() not passed an array or an object", E_USER_WARNING);
            return $this;
        }

        // Handle the backwards compatible case of passing "true" as the second argument
        if ($mergeStrategy === true) {
            $mergeStrategy = Form::MERGE_CLEAR_MISSING;
        } elseif ($mergeStrategy === false) {
            $mergeStrategy = 0;
        }

        // If an object is passed, save it for historical reference through {@link getRecord()}
        // Also use this to determine if we are loading a submitted form, or loading
        // from a record
        $submitted = true;
        if (is_object($data)) {
            $this->record = $data;
            $submitted = false;
        }

        // Using the `MERGE_AS_INTERNAL_VALUE` or `MERGE_AS_SUBMITTED_VALUE` flags users can explicitly specify which
        // `setValue` method to use.
        if (($mergeStrategy & Form::MERGE_AS_INTERNAL_VALUE) == Form::MERGE_AS_INTERNAL_VALUE) {
            $submitted = false;
        } elseif (($mergeStrategy & Form::MERGE_AS_SUBMITTED_VALUE) == Form::MERGE_AS_SUBMITTED_VALUE) {
            $submitted = true;
        }

        // Don't include fields without data
        $dataFields = $this->Fields()->dataFields();

        if (!$dataFields) {
            return $this;
        }

        foreach ($dataFields as $field) {
            $name = $field->getName();

            // Skip fields that have been excluded
            if ($fieldList && !in_array($name, $fieldList ?? [])) {
                continue;
            }

            // First check looks for (fieldname)_unchanged, an indicator that we shouldn't overwrite the field value
            if (is_array($data) && isset($data[$name . '_unchanged'])) {
                continue;
            }

            // Does this property exist on $data?
            $exists = false;
            // The value from $data for this field
            $val = null;

            if (is_object($data)) {
                // Allow dot-syntax traversal of has-one relations fields
                if (strpos($name ?? '', '.') !== false) {
                    $exists = (
                        $data->hasMethod('relField')
                    );
                    try {
                        $val = $data->relField($name);
                    } catch (\LogicException $e) {
                        // There's no other way to tell whether the relation actually exists
                        $exists = false;
                    }
                // Regular ViewableData access
                } else {
                    $exists = (
                        isset($data->$name) ||
                        $data->hasMethod($name) ||
                        ($data->hasMethod('hasField') && $data->hasField($name))
                    );

                    if ($exists) {
                        $val = $data->__get($name);
                    }
                }

            // Regular array access. Note that dot-syntax not supported here
            } elseif (is_array($data)) {
                if (array_key_exists($name, $data ?? [])) {
                    $exists = true;
                    $val = $data[$name];
                // PHP turns the '.'s in POST vars into '_'s
                } elseif (array_key_exists($altName = str_replace('.', '_', $name ?? ''), $data ?? [])) {
                    $exists = true;
                    $val = $data[$altName];
                } elseif (preg_match_all('/(.*)\[(.*)\]/U', $name ?? '', $matches)) {
                    // If field is in array-notation we need to access nested data
                    //discard first match which is just the whole string
                    array_shift($matches);
                    $keys = array_pop($matches);
                    $name = array_shift($matches);
                    $name = array_shift($name);
                    if (array_key_exists($name, $data ?? [])) {
                        $tmpData = &$data[$name];
                        // drill down into the data array looking for the corresponding value
                        foreach ($keys as $arrayKey) {
                            if ($tmpData && $arrayKey !== '') {
                                $tmpData = &$tmpData[$arrayKey];
                            } else {
                                //empty square brackets means new array
                                if (is_array($tmpData)) {
                                    $tmpData = array_shift($tmpData);
                                }
                            }
                        }
                        if ($tmpData) {
                            $val = $tmpData;
                            $exists = true;
                        }
                    }
                }
            }

            // save to the field if either a value is given, or loading of blank/undefined values is forced
            $setValue = false;
            if ($exists) {
                if ($val != false || ($mergeStrategy & Form::MERGE_IGNORE_FALSEISH) != Form::MERGE_IGNORE_FALSEISH) {
                    $setValue = true;
                }
            } elseif (($mergeStrategy & Form::MERGE_CLEAR_MISSING) == Form::MERGE_CLEAR_MISSING) {
                $setValue = true;
            }

            // pass original data as well so composite fields can act on the additional information
            if ($setValue) {
                if ($submitted) {
                    $field->setSubmittedValue($val, $data);
                } else {
                    $field->setValue($val, $data);
                }
            }
        }
        return $this;
    }

    /**
     * Save the contents of this form into the given data object.
     * It will make use of setCastedField() to do this.
     *
     * @param ViewableData&DataObjectInterface $dataObject The object to save data into
     * @param array<string>|null $fieldList An optional list of fields to process.  This can be useful when you have a
     * form that has some fields that save to one object, and some that save to another.
     */
    public function saveInto(DataObjectInterface $dataObject, $fieldList = null)
    {
        $form = $this;
        $dataObject->invokeWithExtensions('onBeforeFormSaveInto', $form, $fieldList);

        $dataFields = $this->fields->saveableFields();
        $lastField = null;

        if ($dataFields) {
            foreach ($dataFields as $field) {
                // Skip fields that have been excluded
                if ($fieldList && is_array($fieldList) && !in_array($field->getName(), $fieldList ?? [])) {
                    continue;
                }

                $saveMethod = "save{$field->getName()}";

                if ($field->getName() == "ClassName") {
                    $lastField = $field;
                } elseif ($dataObject->hasMethod($saveMethod)) {
                    $dataObject->$saveMethod($field->dataValue());
                } elseif ($field->getName() !== "ID") {
                    $field->saveInto($dataObject);
                }
            }
        }

        if ($lastField) {
            $lastField->saveInto($dataObject);
        }

        $dataObject->invokeWithExtensions('onAfterFormSaveInto', $form, $fieldList);
    }

    /**
     * Get the submitted data from this form through
     * {@link FieldList->dataFields()}, which filters out
     * any form-specific data like form-actions.
     * Calls {@link FormField->dataValue()} on each field,
     * which returns a value suitable for insertion into a record
     * property.
     *
     * @return array
     */
    public function getData()
    {
        $dataFields = $this->fields->dataFields();
        $data = [];

        if ($dataFields) {
            foreach ($dataFields as $field) {
                if ($field->getName()) {
                    $data[$field->getName()] = $field->dataValue();
                }
            }
        }

        return $data;
    }

    /**
     * Return a rendered version of this form.
     *
     * This is returned when you access a form as $FormObject rather
     * than <% with FormObject %>
     *
     * @return DBHTMLText
     */
    public function forTemplate()
    {
        if (!$this->canBeCached()) {
            HTTPCacheControlMiddleware::singleton()->disableCache();
        }

        $context = $this;
        $this->extend('onBeforeRender', $context);

        $return = $context->renderWith($context->getTemplates());

        // Now that we're rendered, clear message
        $context->clearMessage();

        return $return;
    }

    /**
     * Return a rendered version of this form, suitable for ajax post-back.
     *
     * It triggers slightly different behaviour, such as disabling the rewriting
     * of # links.
     *
     * @return DBHTMLText
     */
    public function forAjaxTemplate()
    {
        $view = SSViewer::create($this->getTemplates());

        $return = $view->dontRewriteHashlinks()->process($this);

        // Now that we're rendered, clear message
        $this->clearMessage();

        return $return;
    }

    /**
     * Render this form using the given template, and return the result as a string
     * You can pass either an SSViewer or a template name
     * @param string|array $template
     * @return DBHTMLText
     */
    public function renderWithoutActionButton($template)
    {
        $custom = $this->customise([
            "Actions" => "",
        ]);

        if (is_string($template)) {
            $template = SSViewer::create($template);
        }

        return $template->process($custom);
    }

    /**
     * Return the default button that should be clicked when another one isn't
     * available.
     *
     * @return FormAction|null
     */
    public function defaultAction()
    {
        if ($this->hasDefaultAction && $this->actions) {
            return $this->actions->flattenFields()->filterByCallback(function ($field) {
                return $field instanceof FormAction;
            })->first();
        }
        return null;
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
    public function disableDefaultAction()
    {
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
    public function disableSecurityToken()
    {
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
    public function enableSecurityToken()
    {
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
    public function getSecurityToken()
    {
        return $this->securityToken;
    }

    /**
     * Compiles all CSS-classes.
     *
     * @return string
     */
    public function extraClass()
    {
        return implode(' ', array_unique($this->extraClasses ?? []));
    }

    /**
     * Check if a CSS-class has been added to the form container.
     *
     * @param string $class A string containing a classname or several class
     * names delimited by a single space.
     * @return boolean True if all of the classnames passed in have been added.
     */
    public function hasExtraClass($class)
    {
        //split at white space
        $classes = preg_split('/\s+/', $class ?? '');
        foreach ($classes as $class) {
            if (!isset($this->extraClasses[$class])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add a CSS-class to the form-container. If needed, multiple classes can
     * be added by delimiting a string with spaces.
     *
     * @param string $class A string containing a classname or several class
     *              names delimited by a single space.
     * @return $this
     */
    public function addExtraClass($class)
    {
        //split at white space
        $classes = preg_split('/\s+/', $class ?? '');
        foreach ($classes as $class) {
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
    public function removeExtraClass($class)
    {
        //split at white space
        $classes = preg_split('/\s+/', $class ?? '');
        foreach ($classes as $class) {
            //unset one by one
            unset($this->extraClasses[$class]);
        }
        return $this;
    }

    public function debug()
    {
        $class = static::class;
        $result = "<h3>$class</h3><ul>";
        foreach ($this->fields as $field) {
            $result .= "<li>$field" . $field->debug() . "</li>";
        }
        $result .= "</ul>";

        if ($this->validator) {
            $result .= '<h3>' . _t(__CLASS__ . '.VALIDATOR', 'Validator') . '</h3>' . $this->validator->debug();
        }

        return $result;
    }

    /**
     * Current request handler, build by buildRequestHandler(),
     * accessed by getRequestHandler()
     *
     * @var FormRequestHandler
     */
    protected $requestHandler = null;

    /**
     * Get request handler for this form
     *
     * @return FormRequestHandler
     */
    public function getRequestHandler()
    {
        if (!$this->requestHandler) {
            $this->requestHandler = $this->buildRequestHandler();
        }
        return $this->requestHandler;
    }

    /**
     * Assign a specific request handler for this form
     *
     * @param FormRequestHandler $handler
     * @return $this
     */
    public function setRequestHandler(FormRequestHandler $handler)
    {
        $this->requestHandler = $handler;
        return $this;
    }

    /**
     * Scaffold new request handler for this form
     *
     * @return FormRequestHandler
     */
    protected function buildRequestHandler()
    {
        return FormRequestHandler::create($this);
    }

    /**
     * Can the body of this form be cached?
     *
     * @return bool
     */
    protected function canBeCached()
    {
        if ($this->getSecurityToken()->isEnabled()) {
            return false;
        }

        if ($this->FormMethod() !== 'GET') {
            return false;
        }

        $validator = $this->getValidator();

        if (!$validator) {
            return true;
        }

        return $validator->canBeCached();
    }
}
