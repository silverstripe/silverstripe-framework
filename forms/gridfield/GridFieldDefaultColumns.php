<?php
/**
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldDefaultColumns implements GridField_ColumnProvider {

	public function augmentColumns($gridField, &$columns) {
		$baseColumns = array_keys($gridField->getDisplayFields());
		foreach($baseColumns as $col) $columns[] = $col;
	}

	public function getColumnsHandled($gridField) {
		return array_keys($gridField->getDisplayFields());
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
		$columns = $gridField->getDisplayFields();
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
		if(array_key_exists($fieldName, $gridField->FieldCasting)) {
			return $gridField->getCastedValue($value, $gridField->FieldCasting[$fieldName]);
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
		if(!array_key_exists($fieldName, $gridField->FieldFormatting)) {
			return $value;
		}

		$format = str_replace('$value', "__VAL__", $gridField->FieldFormatting[$fieldName]);
		$format = preg_replace('/\$([A-Za-z0-9-_]+)/', '$item->$1', $format);
		$format = str_replace('__VAL__', '$value', $format);
		eval('$value = "' . $format . '";');
		return $value;
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