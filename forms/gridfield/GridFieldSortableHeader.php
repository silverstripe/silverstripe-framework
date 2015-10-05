<?php

/**
 * GridFieldSortableHeader adds column headers to a {@link GridField} that can
 * also sort the columns.
 *
 * @see GridField
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldSortableHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	protected $throwExceptionOnBadDataType = true;

	/**
	 * @var array
	 */
	public $fieldSorting = array();

	/**
	 * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
	 *
	 *  - true:  An exception is thrown
	 *  - false: This component will be ignored - it won't make any changes to the GridField.
	 *
	 * By default, this is set to true so that it's clearer what's happening, but the predefined
	 * {@link GridFieldConfig} subclasses set this to false for flexibility.
	 */
	public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType) {
		$this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType;
	}

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	public function getThrowExceptionOnBadDataType() {
		return $this->throwExceptionOnBadDataType;
	}

	/**
	 * Check that this dataList is of the right data type.
	 * Returns false if it's a bad data type, and if appropriate, throws an exception.
	 */
	protected function checkDataType($dataList) {
		if($dataList instanceof SS_Sortable) {
			return true;
		} else {
			if($this->throwExceptionOnBadDataType) {
				throw new LogicException(
					get_class($this) . " expects an SS_Sortable list to be passed to the GridField.");
			}
			return false;
		}
	}

	/**
	 * Specify sortings with fieldname as the key, and actual fieldname to sort as value.
	 * Example: array("MyCustomTitle"=>"Title", "MyCustomBooleanField" => "ActualBooleanField")
	 *
	 * @param array $casting
	 */
	public function setFieldSorting($sorting) {
		$this->fieldSorting = $sorting;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFieldSorting() {
		return $this->fieldSorting;
	}

	/**
	 * Returns the header row providing titles with sort buttons
	 */
	public function getHTMLFragments($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;

		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;

		$state = $gridField->State->GridFieldSortableHeader;
		$columns = $gridField->getColumns();
		$currentColumn = 0;
		$list = $gridField->getList();

		foreach($columns as $columnField) {
			$currentColumn++;
			$metadata = $gridField->getColumnMetadata($columnField);
			$fieldName = str_replace('.', '-', $columnField);
			$title = $metadata['title'];

			if(isset($this->fieldSorting[$columnField]) && $this->fieldSorting[$columnField]) {
				$columnField = $this->fieldSorting[$columnField];
			}

			$allowSort = ($title && $list->canSortBy($columnField));

			if(!$allowSort && strpos($columnField, '.') !== false) {
				// we have a relation column with dot notation
				// @see DataObject::relField for approximation
				$parts = explode('.', $columnField);
				$tmpItem = singleton($list->dataClass());
				for($idx = 0; $idx < sizeof($parts); $idx++) {
					$methodName = $parts[$idx];
					if($tmpItem instanceof SS_List) {
						// It's impossible to sort on a HasManyList/ManyManyList
						break;
					} elseif(method_exists($tmpItem, 'hasMethod') && $tmpItem->hasMethod($methodName)) {
						// The part is a relation name, so get the object/list from it
						$tmpItem = $tmpItem->$methodName();
					} elseif($tmpItem instanceof DataObject && $tmpItem->hasDatabaseField($methodName)) {
						// Else, if we've found a database field at the end of the chain, we can sort on it.
						// If a method is applied further to this field (E.g. 'Cost.Currency') then don't try to sort.
						$allowSort = $idx === sizeof($parts) - 1;
						break;
					} else {
						// If neither method nor field, then unable to sort
						break;
					}
				}
			}

			if($allowSort) {
				$dir = 'asc';
				if($state->SortColumn(null) == $columnField && $state->SortDirection('asc') == 'asc') {
					$dir = 'desc';
				}

				$field = Object::create(
					'GridField_FormAction', $gridField, 'SetOrder'.$fieldName, $title,
					"sort$dir", array('SortColumn' => $columnField)
				)->addExtraClass('ss-gridfield-sort');

				if($state->SortColumn(null) == $columnField){
					$field->addExtraClass('ss-gridfield-sorted');

					if($state->SortDirection('asc') == 'asc')
						$field->addExtraClass('ss-gridfield-sorted-asc');
					else
						$field->addExtraClass('ss-gridfield-sorted-desc');
				}
			} else {
				if($currentColumn == count($columns)
						&& $gridField->getConfig()->getComponentByType('GridFieldFilterHeader')){

					$field = new LiteralField($fieldName,
						'<button name="showFilter" class="ss-gridfield-button-filter trigger"></button>');
				} else {
					$field = new LiteralField($fieldName, '<span class="non-sortable">' . $title . '</span>');
				}
			}
			$forTemplate->Fields->push($field);
		}

		return array(
			'header' => $forTemplate->renderWith('GridFieldSortableHeader_Row'),
		);
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;

		return array('sortasc', 'sortdesc');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if(!$this->checkDataType($gridField->getList())) return;

		$state = $gridField->State->GridFieldSortableHeader;
		switch($actionName) {
			case 'sortasc':
				$state->SortColumn = $arguments['SortColumn'];
				$state->SortDirection = 'asc';
				break;

			case 'sortdesc':
				$state->SortColumn = $arguments['SortColumn'];
				$state->SortDirection = 'desc';
				break;
		}
	}

	/**
	 * Returns the manipulated (sorted) DataList. Field names will simply add an 
	 * 'ORDER BY' clause, relation names will add appropriate joins to the
	 * {@link DataQuery} first.
	 *
	 * @param GridField
	 * @param SS_List
	 * @return SS_List
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$this->checkDataType($dataList)) return $dataList;

		$state = $gridField->State->GridFieldSortableHeader;
		if ($state->SortColumn == "") {
			return $dataList;
		}

		$column = $state->SortColumn;

		// if we have a relation column with dot notation
		if(strpos($column, '.') !== false) {
			$lastAlias = $dataList->dataClass();
			$tmpItem = singleton($lastAlias);
			$parts = explode('.', $state->SortColumn);

			for($idx = 0; $idx < sizeof($parts); $idx++) {
				$methodName = $parts[$idx];

				// If we're not on the last item, we're looking at a relation
				if($idx !== sizeof($parts) - 1) {
					// Traverse to the relational list
					$tmpItem = $tmpItem->$methodName();

					$joinClass = ClassInfo::table_for_object_field(
						$lastAlias, 
						$methodName . "ID"
					);

					// if the field isn't in the object tree then it is likely
					// been aliased. In that event, assume what the user has
					// provided is the correct value
					if(!$joinClass) $joinClass = $lastAlias;

					$dataList = $dataList->leftJoin(
						$tmpItem->class,
						'"' . $methodName . '"."ID" = "' . $joinClass . '"."' . $methodName . 'ID"',
						$methodName
					);

					// Store the last 'alias' name as it'll be used for the next
					// join, or the 'sort' column
					$lastAlias = $methodName;
				} else {
					// Change relation.relation.fieldname to alias.fieldname
					$column = $lastAlias . '.' . $methodName;
				}
			}
		}

		// We need to manually create our ORDER BY "Foo"."Bar" string for relations,
		// as ->sort() won't do it by itself. Blame PostgreSQL for making this necessary
		$pieces = explode('.', $column);
		$column = '"' . implode('"."', $pieces) . '"';

		return $dataList->sort($column, $state->SortDirection('asc'));
	}
}
