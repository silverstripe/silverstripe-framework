<?php
/**
 * GridFieldFilterHeader alters the gridfield with some filtering fields in the header of each column
 * 
 * @see GridField
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridFieldFilterHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	protected $throwExceptionOnBadDataType = true;
	
        /**
	 * @var
	 */
	protected $separator = '|';
        
        /**
	 *
	 * @param var $separator - Column filter separator
	 */
	public function __construct($separator=null) {
		if($separator) $this->separator = $separator;
	}
	
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
			if(isset($data['filter'])){
				foreach($data['filter'] as $key => $filter ){
					$state->Columns->$key = $filter;
				}
			}
		} elseif($actionName === 'reset') {
			$state->Columns = null;
		}
	}

        // daterange filters
        // use @$this->separator to separate two dates or dateTimes
        public function DateRange ($columnName, $value) {
            $filters = array();
            
            if (strpos($value, $this->separator)) {
                $dates = explode($this->separator, $value);
                sort($dates);
                $filters[$columnName.':GreaterThan'] = $dates[0];
                if (isset($dates[1])) {
                    $filters[$columnName.':LessThan'] = $dates[1];
                }
            } else {
                $filters[$columnName.':PartialMatch'] = $value;
            }
            
            return $filters;
        }


	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$this->checkDataType($dataList)) return $dataList;
		
                $filters = array();
		
		$state = $gridField->State->GridFieldFilterHeader;
		if(!isset($state->Columns)) {
			return $dataList;
		} 
		
		$filterArguments = $state->Columns->toArray();
		$dataListClone = null;
		foreach($filterArguments as $columnName => $value ) {
			if($dataList->canFilterBy($columnName) && $value) {
                            // daterange filters use @$this->separator to separate values
                            switch ($columnName) {
                                case 'Created':
                                case 'LastEdited':
                                case 'Date':
                                case 'DateTime':
                                    $filters = array_merge($this->DateRange($columnName, $value), $filters);
                                    break;
                                default:
                                    // use @$this->separator to separate values 
                                    // example: two|tree 
                                    // query: "YourClass"."Column" LIKE '%two%' OR "YourClass"."Column" LIKE '%tree%'
                                    $value = explode($this->separator, $value);
                                    $filters[$columnName.':PartialMatch'] = $value;
                            }
			}
		}
                if (sizeof($filters) > 0) { $dataListClone = $dataList->filter($filters); }
                
		return ($dataListClone) ? $dataListClone : $dataList;
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
		
			
			if($title && $gridField->getList()->canFilterBy($columnField)) {
				$value = '';
				if(isset($filterArguments[$columnField])) {
					$value = $filterArguments[$columnField];
				}
				$field = new TextField('filter['.$columnField.']', '', $value);
				$field->addExtraClass('ss-gridfield-sort');
				$field->addExtraClass('no-change-track');

				$field->setAttribute('placeholder',
					_t('GridField.FilterBy', "Filter by ") . _t('GridField.'.$metadata['title'], $metadata['title']));

				$field = new FieldGroup(
					$field,
					GridField_FormAction::create($gridField, 'reset', false, 'reset', null)
						->addExtraClass('ss-gridfield-button-reset')
						->setAttribute('title', _t('GridField.ResetFilter', "Reset"))
						->setAttribute('id', 'action_reset_' . $gridField->getModelClass() . '_' . $columnField)

				);
			} else {
				if($currentColumn == count($columns)){
					$field = new FieldGroup(
						GridField_FormAction::create($gridField, 'filter', false, 'filter', null)
							->addExtraClass('ss-gridfield-button-filter')
							->setAttribute('title', _t('GridField.Filter', "Filter"))
							->setAttribute('id', 'action_filter_' . $gridField->getModelClass() . '_' . $columnField),
						GridField_FormAction::create($gridField, 'reset', false, 'reset', null)
							->addExtraClass('ss-gridfield-button-close')
							->setAttribute('title', _t('GridField.ResetFilter', "Reset"))
							->setAttribute('id', 'action_reset_' . $gridField->getModelClass() . '_' . $columnField)
					);
					$field->addExtraClass('filter-buttons');
					$field->addExtraClass('no-change-track');
				}else{
					$field = new LiteralField('', '');
				}
			}

			$forTemplate->Fields->push($field);
		}

		return array(
			'header' => $forTemplate->renderWith('GridFieldFilterHeader_Row'),
		);
	}
}
