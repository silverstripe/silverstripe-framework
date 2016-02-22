<?php

namespace SilverStripe\Forms\Schema;

use Convert;
use Form;

class FormSchema {

	/**
	 * Gets the schema for this form as a nested array.
	 *
	 * @param Form $form
	 * @return string
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

		foreach ($form->Fields() as $fieldList) {
			foreach ($fieldList->getForm()->fields()->dataFields() as $field) {
				$schema['fields'][] = $field->getSchemaData();
			}
		}

		return Convert::raw2json($schema);
	}

	/**
	 * Gets the current state of this form as a nested array.
	 *
	 * @param From $form
	 * @return string
	 */
	public function getState(Form $form) {
		$state = ['state' => []];

		return Convert::raw2json($state);
	}
}
