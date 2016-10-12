<?php

namespace SilverStripe\Forms;

use SilverStripe\Control\Controller;
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
class DefaultFormFactory implements FormFactory {
	use Extensible;

	/**
	 * @var Controller $controller
	 */
	protected $controller;

	/**
	 * @var DataObject $record
	 */
	protected $record;

	/**
	 * @param Controller $controller
	 * @param DataObject $model
	 */
	public function __construct($controller, $model) {
		$this->setController($controller);
		$this->setRecord($model);
		$this->constructExtensions();
	}

	/**
	 * Generates a form using the stored controller and model.
	 *
	 * @param string $name
	 * @return Form $form
	 */
	public function getForm($name = 'Form') {
		$record = $this->getRecord();
		$fields = $this->getFormFields();
		$actions = $this->getFormActions();
		$validator = $this->getFormValidator();
		$form = Form::create($this->controller, $name, $fields, $actions, $validator);

		// Extend form
		$this->extendAll('updateForm', $form);

		// Load into form
		$form->loadDataFrom($record);

		return $form;
	}

	/**
	 * Build field list for this form
	 *
	 * @return FieldList
	 */
	protected function getFormFields() {
		// Fall back to standard "getCMSFields" which itself uses the FormScaffolder as a fallback
		$record = $this->getRecord();
		// @todo Deprecate or formalise support for getCMSFields()
		$fields = $record->getCMSFields();
		$this->extendAll('updateFormFields', $fields);
		return $fields;
	}

	/**
	 * Build list of actions for this form
	 *
	 * @return FieldList
	 */
	protected function getFormActions() {
		// by default no actions, it's a bit unpredictable
		$record = $this->getRecord();

		// Support legacy behaviour
		// @todo Deprecate or formalise support for getCMSActions()
		$actions = $record->getCMSActions();

		// Extend actions
		$this->extendAll('updateFormActions', $actions);
		return $actions;
	}

	/**
	 * @return Controller
	 */
	public function getController() {
		return $this->controller;
	}

	/**
	 * @param Controller $controller
	 * @return $this
	 */
	public function setController(Controller $controller) {
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @return DataObject
	 */
	public function getRecord() {
		return $this->record;
	}

	/**
	 * @param DataObject $record
	 * @return $this
	 */
	public function setRecord(DataObject $record) {
		$this->record = $record;
		return $this;
	}

	/**
	 * @return Validator|null
	 */
	protected function getFormValidator() {
		$validator = null;
		$record = $this->getRecord();

		// Support legacy behaviour
		if ($record->hasMethod('getCMSValidator')) {
			// @todo Deprecate or formalise support for getCMSValidator()
			$validator = $record->getCMSValidator();
		}

		// Extend validator
		$this->extendAll('updateFormValidator', $validator);
		return $validator;
	}

	/**
	 * Runs the given extension on the builder, record, and controller
	 *
	 * @param string $method name of extension method to call
	 * @param mixed $object Object parameter
	 */
	protected function extendAll($method, &$object) {
		$this->invokeWithExtensions($method, $object, $this);
		$this->getRecord()->invokeWithExtensions($method, $object, $this);
		$this->getController()->invokeWithExtensions($method, $object, $this);
	}
}
