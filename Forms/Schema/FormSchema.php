<?php

namespace SilverStripe\Forms\Schema;

use SilverStripe\Control\Session;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;

/**
 * Represents a {@link Form} as structured data which allows a frontend library to render it.
 * Includes information about the form as well as its fields.
 * Can create a "schema" (structure only) as well as "state" (data only).
 */
class FormSchema {

	/**
	 * Gets the schema for this form as a nested array.
	 *
	 * @param Form $form
	 * @return array
	 */
	public function getSchema(Form $form) {
		$schema = [
			'name' => $form->getName(),
			'id' => $form->FormName(),
			'action' => $form->FormAction(),
			'method' => $form->FormMethod(),
			'attributes' => $form->getAttributes(),
			'data' => [],
			'fields' => [],
			'actions' => []
		];

		/** @var FormField $action */
		foreach ($form->Actions() as $action) {
			$schema['actions'][] = $action->getSchemaData();
		}

		/** @var FormField $field */
		foreach ($form->Fields() as $field) {
			$schema['fields'][] = $field->getSchemaData();
		}

		return $schema;
	}

	/**
	 * Gets the current state of this form as a nested array.
	 *
	 * @param Form $form
	 * @return array
	 */
	public function getState(Form $form) {
		// Ensure that session errors are populated within form field messages
		$form->setupFormErrors();

		// @todo - Replace with ValidationResult handling
		// Currently tri-state; null (unsubmitted), true (submitted-valid), false (submitted-invalid)
		$errors = Session::get("FormInfo.{$form->FormName()}.errors");
		$valid = isset($errors) ? empty($errors) : null;

		$state = [
			'id' => $form->FormName(),
			'fields' => [],
			'valid' => $valid,
			'messages' => [],
		];

		// flattened nested fields are returned, rather than only top level fields.
		$state['fields'] = array_merge(
			$this->getFieldStates($form->Fields()),
			$this->getFieldStates($form->Actions())
		);

		if($message = $form->Message()) {
			$state['messages'][] = [
				// TODO Make form / field messages not always stored as html
				'value' => ['html' => $message],
				'type' => $form->MessageType(),
			];
		}

		return $state;
	}

	protected function getFieldStates($fields) {
		$states = [];
		/** @var FormField $field */
		foreach ($fields as $field) {
			$states[] = $field->getSchemaState();

			if ($field instanceof CompositeField) {
				$subFields = $field->FieldList();
				$states = array_merge($states, $this->getFieldStates($subFields));
			}
		}
		return $states;
	}
}
