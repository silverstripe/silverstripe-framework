<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Extensible;
use SilverStripe\ORM\DataObject;

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
     * @throws InvalidArgumentException When required context is missing
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
        if (!$context['Record'] instanceof DataObject) {
            return null;
        }

        $compositeValidator = $context['Record']->getCMSCompositeValidator();

        // Extend validator - legacy support, will be removed in 5.0.0
        foreach ($compositeValidator->getValidators() as $validator) {
            $this->invokeWithExtensions('updateFormValidator', $validator, $controller, $name, $context);
        }

        // Extend validator - forward support, will be supported beyond 5.0.0
        $this->invokeWithExtensions('updateCMSCompositeValidator', $compositeValidator);

        return $compositeValidator;
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
