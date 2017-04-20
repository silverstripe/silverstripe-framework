<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\Control\Controller;
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
        $this->constructExtensions();
    }

    public function getForm(Controller $controller, $name = FormFactory::DEFAULT_NAME, $context = [])
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
     * @param Controller $controller
     * @param string $name
     * @param array $context
     * @return FieldList
     */
    protected function getFormFields(Controller $controller, $name, $context = [])
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
     * @param Controller $controller
     * @param string $name
     * @param array $context
     * @return FieldList
     */
    protected function getFormActions(Controller $controller, $name, $context = [])
    {
        // @todo Deprecate or formalise support for getCMSActions()
        $actions = $context['Record']->getCMSActions();
        $this->invokeWithExtensions('updateFormActions', $actions, $controller, $name, $context);
        return $actions;
    }

    /**
     * @param Controller $controller
     * @param string $name
     * @param array $context
     * @return null|Validator
     */
    protected function getFormValidator(Controller $controller, $name, $context = [])
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
