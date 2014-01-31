<?php
/**
 * GridFieldFilterHeader alters the {@link GridField} with some filtering 
 * fields in the header of each column.
 * 
 * @see GridField
 * 
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldFilterHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	protected $throwExceptionOnBadDataType = true;
	
	/**
	 * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
	 * 
	 *  - true: An exception is thrown
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
		if($dataList instanceof SS_Filterable) {
			return true;
		} else {
			if($this->throwExceptionOnBadDataType) {
				throw new LogicException(
					get_class($this) . " expects an SS_Filterable list to be passed to the GridField.");
			}
			return false;
		}
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;

		return array('filter', 'reset');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if(!$this->checkDataType($gridField->getList())) return;

		$state = $gridField->State->GridFieldFilterHeader;
		if($actionName === 'filter') {
			if(isset($data['filter'][$gridField->getName()])){
				foreach($data['filter'][$gridField->getName()] as $key => $filter ){
					$state->Columns->$key = $filter;
				}
			}
		} elseif($actionName === 'reset') {
			$state->Columns = null;
		}
	}


	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$this->checkDataType($dataList)) return $dataList;
		
		$state = $gridField->State->GridFieldFilterHeader;
		if(!isset($state->Columns)) {
			return $dataList;
		} 
		
		$filterArguments = $state->Columns->toArray();
		$dataListClone = clone($dataList);
		foreach($filterArguments as $columnName => $value ) {
			if($dataList->canFilterBy($columnName) && $value) {
				$dataListClone = $dataListClone->filter($columnName.':PartialMatch', $value);
			}
		}
		return $dataListClone;
	}

	public function getHTMLFragments($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;

		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;
		$columns = $gridField->getColumns();
		$filterArguments = $gridField->State->GridFieldFilterHeader->Columns->toArray();
		$currentColumn = 0;
		foreach($columns as $columnField) {
			$currentColumn++;
			$metadata = $gridField->getColumnMetadata($columnField);
			$title = $metadata['title'];
			$fields = new FieldGroup();
			
			if($title && $gridField->getList()->canFilterBy($columnField)) {
				$value = '';
				if(isset($filterArguments[$columnField])) {
					$value = $filterArguments[$columnField];
				}
				$field = new TextField('filter[' . $gridField->getName() . '][' . $columnField . ']', '', $value);
				$field->addExtraClass('ss-gridfield-sort');
				$field->addExtraClass('no-change-track');

				$field->setAttribute('placeholder',
					_t('GridField.FilterBy', "Filter by ") . _t('GridField.'.$metadata['title'], $metadata['title']));

				$fields->push($field);
				$fields->push(
					GridField_FormAction::create($gridField, 'reset', false, 'reset', null)
						->addExtraClass('ss-gridfield-button-reset')
						->setAttribute('title', _t('GridField.ResetFilter', "Reset"))
						->setAttribute('id', 'action_reset_' . $gridField->getModelClass() . '_' . $columnField)
				);
			} 

			if($currentColumn == count($columns)){
				$fields->push(
					GridField_FormAction::create($gridField, 'filter', false, 'filter', null)
						->addExtraClass('ss-gridfield-button-filter')
						->setAttribute('title', _t('GridField.Filter', "Filter"))
						->setAttribute('id', 'action_filter_' . $gridField->getModelClass() . '_' . $columnField)
				);
				$fields->push(
					GridField_FormAction::create($gridField, 'reset', false, 'reset', null)
						->addExtraClass('ss-gridfield-button-close')
						->setAttribute('title', _t('GridField.ResetFilter', "Reset"))
						->setAttribute('id', 'action_reset_' . $gridField->getModelClass() . '_' . $columnField)
				);
				$fields->addExtraClass('filter-buttons');
				$fields->addExtraClass('no-change-track');
			}

			$forTemplate->Fields->push($fields);
		}

		return array(
			'header' => $forTemplate->renderWith('GridFieldFilterHeader_Row'),
		);
	}
}
