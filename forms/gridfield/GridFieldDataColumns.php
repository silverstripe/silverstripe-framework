<?php
/**
 * 
 * @see GridField
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridFieldDataColumns implements GridField_ColumnProvider {

	/** @var array */
	public $fieldCasting = array();

	/** @var array */
	public $fieldFormatting = array();
	
	/**
	 * This is the columns that will be visible
	 *
	 * @var array
	 */
	protected $displayFields = array();

	public function augmentColumns($gridField, &$columns) {
		$baseColumns = array_keys($this->getDisplayFields($gridField));
		foreach($baseColumns as $col) $columns[] = $col;
	}

	public function getColumnsHandled($gridField) {
		return array_keys($this->getDisplayFields($gridField));
	}
	
	/**
	 * Override the default behaviour of showing the models summaryFields with
	 * these fields instead
	 * Example: array( 'Name' => 'Members name', 'Email' => 'Email address')
	 *
	 * @param array $fields 
	 */
	public function setDisplayFields($fields) {
		if(!is_array($fields)) {
			throw new InvalidArgumentException('Arguments passed to GridFieldDataColumns::setDisplayFields() must be an array');
		}
		$this->displayFields = $fields;
		return $this;
	}

	/**
	 * Get the DisplayFields
	 * 
	 * @return array
	 * @see GridFieldDataColumns::setDisplayFields
	 */
	public function getDisplayFields($gridField) {
		if(!$this->displayFields) {
			return singleton($gridField->getModelClass())->summaryFields();
		}
		return $this->displayFields;
	}

	/**
	 * Specify castings with fieldname as the key, and the desired casting as value.
	 * Example: array("MyCustomDate"=>"Date","MyShortText"=>"Text->FirstSentence")
	 *
	 * @param array $casting
	 */
	public function setFieldCasting($casting) {
		$this->fieldCasting = $casting;
		return $this;
	}

	/**
	 * Specify custom formatting for fields, e.g. to render a link instead of pure text.
	 * Caution: Make sure to escape special php-characters like in a normal php-statement.
	 * Example:	"myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'.
	 * Alternatively, pass a anonymous function, which takes one parameter: The list item.
	 *
	 * @return array
	 */
	public function getFieldCasting() {
		return $this->fieldCasting;
	}

	/**
	 * @param array $formatting
	 */
	public function setFieldFormatting($formatting) {
		$this->fieldFormatting = $formatting;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFieldFormatting() {
		return $this->fieldFormatting;
	}

	/**
	 *
	 * @param string $fieldName
	 * @param string $value
	 * @param boolean $xmlSafe
	 * @return type 
	 */
	public function getColumnContent($gridField, $item, $column) {
		// Find the data column for the given named column
		$fieldName = $column;
		$xmlSafe = true;
		
		// This supports simple FieldName syntax
		if(strpos($fieldName, '.') === false) {
			$value = ($item->XML_val($fieldName) && $xmlSafe) ? $item->XML_val($fieldName) : $item->RAW_val($fieldName);
		} else {
			$fieldNameParts = explode('.', $fieldName);
			$tmpItem = $item;
			for($idx = 0; $idx < sizeof($fieldNameParts); $idx++) {
				$relationMethod = $fieldNameParts[$idx];
				// Last value for value
				if($idx == sizeof($fieldNameParts) - 1) {
					if($tmpItem) {
						$value = ($tmpItem->XML_val($relationMethod) && $xmlSafe) ? $tmpItem->XML_val($relationMethod) : $tmpItem->RAW_val($relationMethod);
					}
					// else get the object for the next iteration
				} else {
					if($tmpItem) {
						$tmpItem = $tmpItem->$relationMethod();
					}
				}
			}
		}

		$value = $this->castValue($gridField, $column, $value);
		$value = $this->formatValue($gridField, $item, $column, $value);
		$value = $this->escapeValue($gridField, $value);

		return $value;
	}
	
	public function getColumnAttributes($gridField, $item, $column) {
		return array('class' => 'col-' . preg_replace('/[^\w]/', '-', $column));
	}
	
	public function getColumnMetadata($gridField, $column) {
		$columns = $this->getDisplayFields($gridField);
		return array(
			'title' => $columns[$column],
		);
	}
	
	/**
	 *
	 * @param type $fieldName
	 * @param type $value
	 * @return type 
	 */
	protected function castValue($gridField, $fieldName, $value) {
		if(array_key_exists($fieldName, $this->fieldCasting)) {
			return $gridField->getCastedValue($value, $this->fieldCasting[$fieldName]);
		} elseif(is_object($value) && method_exists($value, 'Nice')) {
			return $value->Nice();
		}
		return $value;
	}
	
	/**
	 *
	 * @param type $fieldName
	 * @param type $value
	 * @return type 
	 */
	protected function formatValue($gridField, $item, $fieldName, $value) {
		if(!array_key_exists($fieldName, $this->fieldFormatting)) {
			return $value;
		}

		$spec = $this->fieldFormatting[$fieldName];
		if(is_callable($spec)) {
			return $spec($item);
		} else {
			$format = str_replace('$value', "__VAL__", $spec);
			$format = preg_replace('/\$([A-Za-z0-9-_]+)/', '$item->$1', $format);
			$format = str_replace('__VAL__', '$value', $format);
			eval('$value = "' . $format . '";');
			return $value;
		}
	}
	
	/**
	 * Remove values from a value using FieldEscape setter
	 *
	 * @param type $value
	 * @return type
	 */
	protected function escapeValue($gridField, $value) {
		if(!$escape = $gridField->FieldEscape) {
			return $value;
		}

		foreach($escape as $search => $replace) {
			$value = str_replace($search, $replace, $value);
		}
		return $value;
	}
}
