<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Extensible;

/**
 * Default form builder class.
 *
 * @internal WARNING: Experimental and volatile API.
 *
 * Allows extension by either controller or object via the following methods:
 * - updateFormActions
 * - updateFormValidator
 * - updateFormFields
 * - updateForm
 */
class DefaultFormFactory implements FormFactory
{
    use Extensible;

    public function __construct()
    {
    }

    /**
     * @param RequestHandler $controller
     * @param string $name
     * @param array $context
     * @return Form
     */
    public function getForm(RequestHandler $controller = null, $name = FormFactory::DEFAULT_NAME, $context = [])
    {
        // Validate context
        foreach ($this->getRequiredContext() as $required) {
            if (!isset($context[$required])) {
                throw new InvalidArgumentException("Missing required context $required");
            }
        }

        $fields = $this->getFormFields($controller, $name, $context);
        $actions = $this->getFormActions($controller, $name, $context);
        $validator = $this->getFormValidator($controller, $name, $context);
        $form = Form::create($controller, $name, $fields, $actions, $validator);

        // Extend form
        $this->invokeWithExtensions('updateForm', $form, $controller, $name, $context);

        // Populate form from record
        $form->loadDataFrom($context['Record']);

        return $form;
    }

    /**
     * Build field list for this form
     *
     * @param RequestHandler $controller
     * @param string $name
     * @param array $context
     * @return FieldList
     */
    protected function getFormFields(RequestHandler $controller = null, $name, $context = [])
    {
        // Fall back to standard "getCMSFields" which itself uses the FormScaffolder as a fallback
        // @todo Deprecate or formalise support for getCMSFields()
        $fields = $context['Record']->getCMSFields();
        $this->invokeWithExtensions('updateFormFields', $fields, $controller, $name, $context);
        return $fields;
    }

    /**
     * Build list of actions for this form
     *
     * @param RequestHandler $controller
     * @param string $name
     * @param array $context
     * @return FieldList
     */
    protected function getFormActions(RequestHandler $controller = null, $name, $context = [])
    {
        // @todo Deprecate or formalise support for getCMSActions()
        $actions = $context['Record']->getCMSActions();
        $this->invokeWithExtensions('updateFormActions', $actions, $controller, $name, $context);
        return $actions;
    }

    /**
     * @param RequestHandler $controller
     * @param string $name
     * @param array $context
     * @return null|Validator
     */
    protected function getFormValidator(RequestHandler $controller = null, $name, $context = [])
    {
        $validator = null;
        if ($context['Record']->hasMethod('getCMSValidator')) {
            // @todo Deprecate or formalise support for getCMSValidator()
            $validator = $context['Record']->getCMSValidator();
        }

        // Extend validator
        $this->invokeWithExtensions('updateFormValidator', $validator, $controller, $name, $context);
        return $validator;
    }

    /**
     * Return list of mandatory context keys
     *
     * @return mixed
     */
    public function getRequiredContext()
    {
        return ['Record'];
    }
}
