<?php

namespace SilverStripe\Forms\Schema;

trait FormFieldSchemaTrait {

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

	/**
	 * Gets the defaults for $schemaData.
	 * The keys defined here are immutable, meaning undefined keys passed to {@link setSchemaData()} are ignored.
	 * Instead the `data` array should be used to pass around ad hoc data.
	 *
	 * @return array
	 */
	function getSchemaDataDefaults() {
		return [
			'type' => $this->class,
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
}
