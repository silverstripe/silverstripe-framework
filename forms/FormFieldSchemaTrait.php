<?php

namespace SilverStripe\Forms\Schema;

use Exception;

/**
 * Class FormFieldSchemaTrait
 * @package SilverStripe\Forms\Schema
 *
 * Allows {@link FormField} to be represented as structured data,
 * including both structure (name, id, attributes, etc.) and state (field value).
 * Can be used by {@link FormSchema} to represent a form in JSON,
 * to be consumed by a front-end application.
 *
 * WARNING: Experimental API.
 */
trait FormFieldSchemaTrait {

	/**
	 * The data type backing the field. Represents the type of value the
	 * form expects to receive via a postback.
	 *
	 * The values allowed in this list include:
	 *
	 *   - String: Single line text
	 *   - Hidden: Hidden field which is posted back without modification
	 *   - Text: Multi line text
	 *   - HTML: Rich html text
	 *   - Integer: Whole number value
	 *   - Decimal: Decimal value
	 *   - MultiSelect: Select many from source
	 *   - SingleSelect: Select one from source
	 *   - Date: Date only
	 *   - DateTime: Date and time
	 *   - Time: Time only
	 *   - Boolean: Yes or no
	 *   - Custom: Custom type declared by the front-end component. For fields with this type,
	 *     the component property is mandatory, and will determine the posted value for this field.
	 *   - Structural: Represents a field that is NOT posted back. This may contain other fields,
	 *     or simply be a block of stand-alone content. As with 'Custom',
	 *     the component property is mandatory if this is assigned.
	 *
	 * @var string
	 */
	protected $schemaDataType;

	/**
	 * The type of front-end component to render the FormField as.
	 *
	 * @var string
	 */
	protected $schemaComponent;

	/**
	 * Structured schema data representing the FormField.
	 * Used to render the FormField as a ReactJS Component on the front-end.
	 *
	 * @var array
	 */
	protected $schemaData = [];

	/**
	 * Structured schema state representing the FormField's current data and validation.
	 * Used to render the FormField as a ReactJS Component on the front-end.
	 *
	 * @var array
	 */
	protected $schemaState = [];

	/**
	 * Sets the component type the FormField will be rendered as on the front-end.
	 *
	 * @param string $componentType
	 * @return FormField
	 */
	public function setSchemaComponent($componentType) {
		$this->schemaComponent = $componentType;
		return $this;
	}

	/**
	 * Gets the type of front-end component the FormField will be rendered as.
	 *
	 * @return string
	 */
	public function getSchemaComponent() {
		return $this->schemaComponent;
	}

	/**
	 * Sets the schema data used for rendering the field on the front-end.
	 * Merges the passed array with the current `$schemaData` or {@link getSchemaDataDefaults()}.
	 * Any passed keys that are not defined in {@link getSchemaDataDefaults()} are ignored.
	 * If you want to pass around ad hoc data use the `data` array e.g. pass `['data' => ['myCustomKey' => 'yolo']]`.
	 *
	 * @param array $schemaData - The data to be merged with $this->schemaData.
	 * @return FormField
	 *
	 * @todo Add deep merging of arrays like `data` and `attributes`.
	 */
	public function setSchemaData($schemaData = []) {
		$current = $this->getSchemaData();

		$this->schemaData = array_merge($current, array_intersect_key($schemaData, $current));
		return $this;
	}

	/**
	 * Gets the schema data used to render the FormField on the front-end.
	 *
	 * @return array
	 */
	public function getSchemaData() {
		return array_merge($this->getSchemaDataDefaults(), $this->schemaData);
	}

	public function getSchemaDataType() {
		if ($this->schemaDataType == null) {
			throw new Exception('You need to set a schemaDataType on ' . $this->getName() . ' field');
		}

		return $this->schemaDataType;
	}

	/**
	 * Gets the defaults for $schemaData.
	 * The keys defined here are immutable, meaning undefined keys passed to {@link setSchemaData()} are ignored.
	 * Instead the `data` array should be used to pass around ad hoc data.
	 *
	 * @return array
	 */
	public function getSchemaDataDefaults() {
		return [
			'type' => $this->getSchemaDataType(),
			'component' => $this->getSchemaComponent(),
			'id' => $this->ID,
			'holder_id' => null,
			'name' => $this->getName(),
			'title' => $this->Title(),
			'source' => null,
			'extraClass' => $this->ExtraClass(),
			'description' => $this->getDescription(),
			'rightTitle' => $this->RightTitle(),
			'leftTitle' => $this->LeftTitle(),
			'readOnly' => $this->isReadOnly(),
			'disabled' => $this->isDisabled(),
			'customValidationMessage' => $this->getCustomValidationMessage(),
			'attributes' => [],
			'data' => [],
		];
	}

	/**
	 * Sets the schema data used for rendering the field on the front-end.
	 * Merges the passed array with the current `$schemaData` or {@link getSchemaDataDefaults()}.
	 * Any passed keys that are not defined in {@link getSchemaDataDefaults()} are ignored.
	 * If you want to pass around ad hoc data use the `data` array e.g. pass `['data' => ['myCustomKey' => 'yolo']]`.
	 *
	 * @param array $schemaData - The data to be merged with $this->schemaData.
	 * @return FormField
	 *
	 * @todo Add deep merging of arrays like `data` and `attributes`.
	 */
	public function setSchemaState($schemaState = []) {
		$current = $this->getSchemaState();

		$this->schemaState = array_merge($current, array_intersect_key($schemaState, $current));
		return $this;
	}

	/**
	 * Gets the schema state used to render the FormField on the front-end.
	 *
	 * @return array
	 */
	public function getSchemaState() {
		return array_merge($this->getSchemaStateDefaults(), $this->schemaState);
	}

	/**
	 * Gets the defaults for $schemaState.
	 * The keys defined here are immutable, meaning undefined keys passed to {@link setSchemaState()} are ignored.
	 * Instead the `data` array should be used to pass around ad hoc data.
	 * Includes validation data if the field is associated to a {@link Form},
	 * and {@link Form->validate()} has been called.
	 *
	 * @return array
	 */
	public function getSchemaStateDefaults() {
		$field = $this;
		$form = $this->getForm();
		$validator = $form ? $form->getValidator() : null;
		$errors = $validator ? (array)$validator->getErrors() : [];
		$messages = array_filter(array_map(function($error) use ($field) {
			if($error['fieldName'] === $field->getName()) {
				return [
					'value' => $error['message'],
					'type' => $error['messageType']
				];
			}
		}, $errors));

		return [
			'id' => $this->ID(),
			'value' => $this->Value(),
			'valid' => (count($messages) === 0),
			'messages' => (array)$messages,
			'data' => [],
		];
	}
}
