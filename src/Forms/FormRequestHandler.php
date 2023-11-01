<?php

namespace SilverStripe\Forms;

use BadMethodCallException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationException;

class FormRequestHandler extends RequestHandler
{
    /**
     * @var callable|null
     */
    protected $buttonClickedFunc;

    /**
     * @config
     * @var array
     */
    private static $allowed_actions = [
        'handleField',
        'httpSubmission',
        'forTemplate',
    ];

    /**
     * @config
     * @var array
     */
    private static $url_handlers = [
        'field/$FieldName!' => 'handleField',
        'POST ' => 'httpSubmission',
        'GET ' => 'httpSubmission',
        'HEAD ' => 'httpSubmission',
    ];

    /**
     * Form model being handled
     *
     * @var Form
     */
    protected $form = null;

    /**
     * Build a new request handler for a given Form model
     *
     * @param Form $form
     */
    public function __construct(Form $form)
    {
        $this->form = $form;
        parent::__construct();

        // Inherit parent controller request
        $parent = $this->form->getController();
        if ($parent) {
            $this->setRequest($parent->getRequest());
        }
    }


    /**
     * Get link for this form
     *
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        // Forms without parent controller have no link;
        // E.g. Submission handled via graphql
        $controller = $this->form->getController();
        if (empty($controller)) {
            return null;
        }

        // Respect FormObjectLink() method
        if ($controller->hasMethod("FormObjectLink")) {
            $base = $controller->FormObjectLink($this->form->getName());
        } else {
            $base = Controller::join_links($controller->Link(), $this->form->getName());
        }

        // Join with action and decorate
        $link = Controller::join_links($base, $action, '/');
        $this->extend('updateLink', $link, $action);
        return $link;
    }

    /**
     * Handle a form submission.  GET and POST requests behave identically.
     * Populates the form with {@link loadDataFrom()}, calls {@link validate()},
     * and only triggers the requested form action/method
     * if the form is valid.
     *
     * @param HTTPRequest $request
     * @return mixed
     * @throws HTTPResponse_Exception
     */
    public function httpSubmission($request)
    {
        // Strict method check
        if ($this->form->getStrictFormMethodCheck()) {
            // Throws an error if the method is bad...
            $allowedMethod = $this->form->FormMethod();
            if ($allowedMethod !== $request->httpMethod()) {
                $response = Controller::curr()->getResponse();
                $response->addHeader('Allow', $allowedMethod);
                $this->httpError(405, _t(
                    "SilverStripe\\Forms\\Form.BAD_METHOD",
                    "This form requires a {method} submission",
                    ['method' => $allowedMethod]
                ));
            }

            // ...and only uses the variables corresponding to that method type
            $vars = $allowedMethod === 'GET'
                ? $request->getVars()
                : $request->postVars();
        } else {
            $vars = $request->requestVars();
        }

        // Ensure we only process saveable fields (non structural, readonly, or disabled)
        $allowedFields = array_keys($this->form->Fields()->saveableFields() ?? []);

        // Populate the form
        $this->form->loadDataFrom($vars, true, $allowedFields);

        // Protection against CSRF attacks
        $token = $this->form->getSecurityToken();
        if (! $token->checkRequest($request)) {
            $securityID = $token->getName();
            if (empty($vars[$securityID])) {
                $this->httpError(400, _t(
                    "SilverStripe\\Forms\\Form.CSRF_FAILED_MESSAGE",
                    "There seems to have been a technical problem. Please click the back button, " . "refresh your browser, and try again."
                ));
            } else {
                // Clear invalid token on refresh
                $this->form->clearFormState();
                $data = $this->form->getData();
                unset($data[$securityID]);
                $this->form
                    ->setSessionData($data)
                    ->sessionError(_t(
                        "SilverStripe\\Forms\\Form.CSRF_EXPIRED_MESSAGE",
                        "Your session has expired. Please re-submit the form."
                    ));
                // Return the user
                return $this->redirectBack();
            }
        }

        // Determine the action button clicked
        $funcName = null;
        foreach ($vars as $paramName => $paramVal) {
            if (substr($paramName ?? '', 0, 7) == 'action_') {
                // Break off querystring arguments included in the action
                if (strpos($paramName ?? '', '?') !== false) {
                    list($paramName, $paramVars) = explode('?', $paramName ?? '', 2);
                    $newRequestParams = [];
                    parse_str($paramVars ?? '', $newRequestParams);
                    $vars = array_merge((array)$vars, (array)$newRequestParams);
                }

                // Cleanup action_, _x and _y from image fields
                $funcName = preg_replace(['/^action_/','/_x$|_y$/'], '', $paramName ?? '');
                break;
            }
        }

        // If the action wasn't set, choose the default on the form.
        if (!isset($funcName) && $defaultAction = $this->form->defaultAction()) {
            $funcName = $defaultAction->actionName();
        }

        if (isset($funcName)) {
            $this->setButtonClicked($funcName);
        }

        // Permission checks (first on controller, then falling back to request handler)
        $controller = $this->form->getController();
        if (// Ensure that the action is actually a button or method on the form,
            // and not just a method on the controller.
            $controller
            && $controller->hasMethod($funcName)
            && !$controller->checkAccessAction($funcName)
            // If a button exists, allow it on the controller
            // buttonClicked() validates that the action set above is valid
            && !$this->buttonClicked()
        ) {
            $this->httpError(
                403,
                sprintf('Action "%s" not allowed on controller (Class: %s)', $funcName, get_class($controller))
            );
        } elseif (// No checks for button existence or $allowed_actions is performed -
            // all form methods are callable (e.g. the legacy "callfieldmethod()")
            $this->hasMethod($funcName)
            && !$this->checkAccessAction($funcName)
        ) {
            $this->httpError(
                403,
                sprintf('Action "%s" not allowed on form request handler (Class: "%s")', $funcName, static::class)
            );
        }

        // Action handlers may throw ValidationExceptions.
        try {
            // Or we can use the Validator attached to the form
            $result = $this->form->validationResult();
            if (!$result->isValid()) {
                return $this->getValidationErrorResponse($result);
            }

            // First, try a handler method on the controller (has been checked for allowed_actions above already)
            $controller = $this->form->getController();
            $args = [$funcName, $request, $vars];
            if ($controller && $controller->hasMethod($funcName)) {
                $controller->setRequest($request);
                return $this->invokeFormHandler($controller, ...$args);
            }

            // Otherwise, try a handler method on the form request handler.
            if ($this->hasMethod($funcName)) {
                return $this->invokeFormHandler($this, ...$args);
            }

            // Otherwise, try a handler method on the form itself
            if ($this->form->hasMethod($funcName)) {
                return $this->invokeFormHandler($this->form, ...$args);
            }

            // Check for inline actions
            $field = $this->checkFieldsForAction($this->form->Fields(), $funcName);
            if ($field) {
                return $this->invokeFormHandler($field, ...$args);
            }
        } catch (ValidationException $e) {
            // The ValidationResult contains all the relevant metadata
            $result = $e->getResult();
            $this->form->loadMessagesFrom($result);
            return $this->getValidationErrorResponse($result);
        }

        // Determine if legacy form->allowed_actions is set
        $legacyActions = $this->form->config()->get('allowed_actions');
        if ($legacyActions) {
            throw new BadMethodCallException(
                "allowed_actions are not valid on Form class " . get_class($this->form) . ". Implement these in subclasses of " . static::class . " instead"
            );
        }

        $this->httpError(404, "Could not find a suitable form-action callback function");
    }

    /**
     * @param string $action
     * @return bool
     */
    public function checkAccessAction($action)
    {
        if (parent::checkAccessAction($action)) {
            return true;
        }

        $actions = $this->getAllActions();
        foreach ($actions as $formAction) {
            if ($formAction->actionName() === $action) {
                return true;
            }
        }

            // Always allow actions on fields
        $field = $this->checkFieldsForAction($this->form->Fields(), $action);
        if ($field && $field->checkAccessAction($action)) {
            return true;
        }

        return false;
    }



    /**
     * Returns the appropriate response up the controller chain
     * if {@link validate()} fails (which is checked prior to executing any form actions).
     * By default, returns different views for ajax/non-ajax request, and
     * handles 'application/json' requests with a JSON object containing the error messages.
     * Behaviour can be influenced by setting {@link $redirectToFormOnValidationError},
     * and can be overruled by setting {@link $validationResponseCallback}.
     */
    protected function getValidationErrorResponse(ValidationResult $result): HTTPResponse
    {
        // Check for custom handling mechanism
        $callback = $this->form->getValidationResponseCallback();
        if ($callback && $callbackResponse = call_user_func($callback, $result)) {
            return $callbackResponse;
        }

        // Check if handling via ajax
        if ($this->getRequest()->isAjax()) {
            return $this->getAjaxErrorResponse($result);
        }

        // Prior to redirection, persist this result in session to re-display on redirect
        $this->form->setSessionValidationResult($result);
        $this->form->setSessionData($this->form->getData());

        // Determine redirection method
        if ($this->form->getRedirectToFormOnValidationError()) {
            return $this->redirectBackToForm();
        }
        return $this->redirectBack();
    }

    /**
     * Redirect back to this form with an added #anchor link
     */
    public function redirectBackToForm(): HTTPResponse
    {
        $pageURL = $this->getReturnReferer();
        if (!$pageURL) {
            return $this->redirectBack();
        }

        // Add backURL and anchor
        $pageURL = Controller::join_links(
            $this->addBackURLParam($pageURL),
            '#' . $this->form->FormName()
        );

        // Redirect
        return $this->redirect($pageURL);
    }

    /**
     * Helper to add ?BackURL= to any link
     *
     * @param string $link
     * @return string
     */
    protected function addBackURLParam($link)
    {
        $backURL = $this->getBackURL();
        if ($backURL) {
            return Controller::join_links($link, '?BackURL=' . urlencode($backURL ?? ''));
        }
        return $link;
    }

    /**
     * Build HTTP error response for ajax requests
     *
     * @internal called from {@see Form::getValidationErrorResponse}
     * @param ValidationResult $result
     */
    protected function getAjaxErrorResponse(ValidationResult $result): HTTPResponse
    {
        // Ajax form submissions accept json encoded errors by default
        $acceptType = $this->getRequest()->getHeader('Accept');
        if (strpos($acceptType ?? '', 'application/json') !== false) {
            // Send validation errors back as JSON with a flag at the start
            $response = new HTTPResponse(json_encode($result->getMessages()));
            $response->addHeader('Content-Type', 'application/json');
            return $response;
        }

        // Send the newly rendered form tag as HTML
        $this->form->loadMessagesFrom($result);
        $response = new HTTPResponse($this->form->forTemplate());
        $response->addHeader('Content-Type', 'text/html');
        return $response;
    }

    /**
     * Fields can have action to, let's check if anyone of the responds to $funcname them
     *
     * @param SS_List|array $fields
     * @param callable $funcName
     * @return FormField
     */
    protected function checkFieldsForAction($fields, $funcName)
    {
        foreach ($fields as $field) {
            if (ClassInfo::hasMethod($field, 'FieldList')) {
                if ($field = $this->checkFieldsForAction($field->FieldList(), $funcName)) {
                    return $field;
                }
            } elseif ($field->hasMethod($funcName) && $field->checkAccessAction($funcName)) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Handle a field request.
     * Uses {@link Form->dataFieldByName()} to find a matching field,
     * and falls back to {@link FieldList->fieldByName()} to look
     * for tabs instead. This means that if you have a tab and a
     * formfield with the same name, this method gives priority
     * to the formfield.
     *
     * @param HTTPRequest $request
     * @return FormField
     */
    public function handleField($request)
    {
        $field = $this->form->Fields()->dataFieldByName($request->param('FieldName'));

        if ($field) {
            return $field;
        } else {
            // falling back to fieldByName, e.g. for getting tabs
            return $this->form->Fields()->fieldByName($request->param('FieldName'));
        }
    }

    /**
     * Sets the button that was clicked.  This should only be called by the Controller.
     *
     * @param callable $funcName The name of the action method that will be called.
     * @return $this
     */
    public function setButtonClicked($funcName)
    {
        $this->buttonClickedFunc = $funcName;
        return $this;
    }

    /**
     * Get instance of button which was clicked for this request
     *
     * @return FormAction
     */
    public function buttonClicked()
    {
        $actions = $this->getAllActions();
        foreach ($actions as $action) {
            if ($this->buttonClickedFunc === $action->actionName()) {
                return $action;
            }
        }
        return null;
    }

    /**
     * Get a list of all actions, including those in the main "fields" FieldList
     *
     * @return array
     */
    protected function getAllActions()
    {
        $fields = $this->form->Fields()->dataFields();
        $actions = $this->form->Actions()->dataFields();

        $fieldsAndActions = array_merge($fields, $actions);
        $actions = array_filter($fieldsAndActions ?? [], function ($fieldOrAction) {
            return $fieldOrAction instanceof FormAction;
        });

        return $actions;
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
        // Check if button is exempt, or if there is no validator
        $action = $this->buttonClicked();
        $validator = $this->form->getValidator();
        if (!$validator || $this->form->actionIsValidationExempt($action)) {
            return ValidationResult::create();
        }

        // Invoke validator
        $result = $validator->validate();
        $this->form->loadMessagesFrom($result);
        return $result;
    }

    public function forTemplate()
    {
        return $this->form->forTemplate();
    }

    /**
     * @param $subject
     * @param string $funcName
     * @param HTTPRequest $request
     * @param array $vars
     * @return mixed
     */
    private function invokeFormHandler($subject, string $funcName, HTTPRequest $request, array $vars)
    {
        $this->extend('beforeCallFormHandler', $request, $funcName, $vars, $this->form, $subject);
        $result = $subject->$funcName($vars, $this->form, $request, $this);
        $this->extend('afterCallFormHandler', $request, $funcName, $vars, $this->form, $subject, $result);

        return $result;
    }
}
