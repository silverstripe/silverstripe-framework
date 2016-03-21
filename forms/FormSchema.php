<?php

namespace SilverStripe\Forms\Schema;

use Convert;
use Form;

/**
 * Class FormSchema
 * @package SilverStripe\Forms\Schema
 *
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
		$request = $form->controller()->getRequest();
		$params = $request->AllParams();

		$schema = [
			'name' => $form->getName(),
			'id' => isset($params['ID']) ? $params['ID'] : null,
			'action' => isset($params['Action']) ? $params['Action'] : null,
			'method' => $form->controller()->getRequest()->HttpMethod(),
			'schema_url' => $request->getUrl(),
			'attributes' => $form->getAttributes(),
			'data' => [],
			'fields' => [],
			'actions' => []
		];

		foreach ($form->Actions() as $action) {
			$schema['actions'][] = $action->getSchemaData();
		}

		// TODO Implemented nested fields and use Fields() instead
		foreach ($form->Fields()->dataFields() as $field) {
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
		$state = [
			'id' => $form->getName(),
			'fields' => [],
			'messages' => []
		];

		foreach ($form->Fields()->dataFields() as $field) {
			$state['fields'][] = $field->getSchemaState();
		}

		if($form->Message()) {
			$state['messages'][] = [
				'value' => $form->Message(),
				'type' => $form->MessageType(),
			];
		}

		return $state;
	}
}
