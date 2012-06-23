<?php
/**
 * TableField behaves in the same manner as TableListField, however allows the addition of 
 * fields and editing of attributes specified, and filtering results.
 * 
 * Caution: If you insert DropdownFields in the fieldTypes-array, make sure they have an empty first option.
 * Otherwise the saving can't determine if a new row should really be saved.
 * 
 * Caution: TableField relies on {@FormResponse} to reload the field after it is saved.
 * A TableField-instance should never be saved twice without reloading, because otherwise it 
 * can't determine if a field is new (=create) or existing (=update), and will produce duplicates.
 * 
 * IMPORTANT: This class is about to be deprecated in favour of a new GridFieldEditableColumns component,
 * see http://open.silverstripe.org/ticket/7045
 * 
 * @package forms
 * @subpackage fields-relational
 */
 
class TableField extends TableListField {
	
	protected $fieldList;
	
	/**
	 * A "Field = Value" filter can be specified by setting $this->filterField and $this->filterValue.  This has the advantage of auto-populating
	 * new records
	 */
	protected $filterField = null;

	/**
	 * A "Field = Value" filter can be specified by setting $this->filterField and $this->filterValue.  This has the advantage of auto-populating
	 * new records
	 */
	protected $filterValue = null;
	
	/**
	 * @var $fieldTypes FieldList
	 * Caution: Use {@setExtraData()} instead of manually adding HiddenFields if you want to 
	 * preset relations or other default data.
	 */
	protected $fieldTypes;

	/**
	 * @var $template string Template-Overrides
	 */
	protected $template = "TableField";
	
	/**
	 * @var $extraData array Any extra data that need to be included, e.g. to retain
	 * has-many relations. Format: array('FieldName' => 'Value')
	 */
	protected $extraData;
	
	protected $tempForm;
	
	/**
	 * Influence output without having to subclass the template.
	 */
	protected $permissions = array(
		"edit",
		"delete",
		"add",
		//"export",
	);
	
	public $transformationConditions = array();
	
	/**
	 * @var $requiredFields array Required fields as a numerical array.
	 * Please use an instance of Validator on the including
	 * form.
	 */
	protected $requiredFields = null;
	
	/**
	 * Shows a row of empty fields for adding a new record
	 * (turned on by default). 
	 * Please use {@link TableField::$permissions} to control
	 * if the "add"-functionality incl. button is shown at all.
	 * 
	 * @param boolean $showAddRow
	 */
	public $showAddRow = true;
	
	/**
	 * @param $name string The fieldname
	 * @param $sourceClass string The source class of this field
	 * @param $fieldList array An array of field headings of Fieldname => Heading Text (eg. heading1)
	 * @param $fieldTypes array An array of field types of fieldname => fieldType (eg. formfield). Do not use for extra data/hiddenfields.
	 * @param $filterField string The field to filter by.  Give the filter value in $sourceFilter.  The value will automatically be set on new records.
	 * @param $sourceFilter string If $filterField has a value, then this is the value to filter by.  Otherwise, it is a SQL filter expression.
	 * @param $editExisting boolean (Note: Has to stay on this position for legacy reasons)
	 * @param $sourceSort string
	 * @param $sourceJoin string
	 */
	function __construct($name, $sourceClass, $fieldList = null, $fieldTypes, $filterField = null, 
						$sourceFilter = null, $editExisting = true, $sourceSort = null, $sourceJoin = null) {
		
		$this->fieldTypes = $fieldTypes;
		$this->filterField = $filterField;
		
		$this->editExisting = $editExisting;

		// If we specify filterField, then an implicit source filter of "filterField = sourceFilter" is used.
		if($filterField) {
			$this->filterValue = $sourceFilter;
			$sourceFilter = "\"$filterField\" = '" . Convert::raw2sql($sourceFilter) . "'";
		}
		parent::__construct($name, $sourceClass, $fieldList, $sourceFilter, $sourceSort, $sourceJoin);
	}
	
	/** 
	 * Displays the headings on the template
	 * 
	 * @return SS_List
	 */
	function Headings() {
		$i=0;
		foreach($this->fieldList as $fieldName => $fieldTitle) {
			$extraClass = "col".$i;
			$class = $this->fieldTypes[$fieldName];
			if(is_object($class)) $class = "";
			$class = $class." ".$extraClass;
			$headings[] = new ArrayData(array("Name" => $fieldName, "Title" => $fieldTitle, "Class" => $class));
			$i++;
		}
		return new ArrayList($headings);
	}
	
	/**
	 * Calculates the number of columns needed for colspans
	 * used in template
	 * 
	 * @return int
	 */
	function ItemCount() {
		return count($this->fieldList);
	}
		
	/**
	 * Displays the items from {@link sourceItems()} using the encapsulation object.
	 * If the field value has been set as an array (e.g. after a failed validation),
	 * it generates the rows from array data instead.
	 * Used in the formfield template to iterate over each row.
	 * 
	 * @return SS_List Collection of {@link TableField_Item}
	 */
	function Items() {
		// holds TableField_Item instances
		$items = new ArrayList();

		$sourceItems = $this->sourceItems();

		// either load all rows from the field value,
		// (e.g. when validation failed), or from sourceItems()
		if($this->value) {
			if(!$sourceItems) $sourceItems = new ArrayList();

			// get an array keyed by rows, rather than values
			$rows = $this->sortData(ArrayLib::invert($this->value));
			// ignore all rows which are already saved
			if(isset($rows['new'])) {
				if($sourceItems instanceof DataList) {
					$sourceItems = new ArrayList($sourceItems->toArray());
				}

				$newRows = $this->sortData($rows['new']);
				// iterate over each value (not each row)
				$i = 0;
				foreach($newRows as $idx => $newRow){
					// set a pseudo-ID
					$newRow['ID'] = "new";

					// unset any extradata
					foreach($newRow as $k => $v){
						if($this->extraData && array_key_exists($k, $this->extraData)){
							unset($newRow[$k]);
						}
					}

					// generate a temporary DataObject container (not saved in the database)
					$sourceClass = $this->sourceClass();
					$sourceItems->add(new $sourceClass($newRow));

					$i++;
				}
			}
		} 

		// generate a new TableField_Item instance from each collected item
		if($sourceItems) foreach($sourceItems as $sourceItem) {
			$items->push($this->generateTableFieldItem($sourceItem));
		}

		// add an empty TableField_Item for a single "add row"
		if($this->showAddRow && $this->Can('add')) {
			$items->push(new TableField_Item(null, $this, null, $this->fieldTypes, true));
		}

		return $items;
	}

	/**
	 * Generates a new {@link TableField} instance
	 * by loading a FieldList for this row into a temporary form.
	 * 
	 * @param DataObject $dataObj
	 * @return TableField_Item
	 */
	protected function generateTableFieldItem($dataObj) {
		// Load the data in to a temporary form (for correct field types)
		$form = new Form(
			$this, 
			null, 
			$this->FieldSetForRow(), 
			new FieldList()
		);
		$form->loadDataFrom($dataObj);

		// Add the item to our new ArrayList, with a wrapper class.
		return new TableField_Item($dataObj, $this, $form, $this->fieldTypes);
	}
	
	/**
	 * @return array
	 */
	function FieldList() {
		return $this->fieldList;
	}
	
	/** 
	 * Saves the Dataobjects contained in the field
	 */
	function saveInto(DataObjectInterface $record) {
		// CMS sometimes tries to set the value to one.
		if(is_array($this->value)){
			$newFields = array();
			
			// Sort into proper array
			$value = ArrayLib::invert($this->value);
			$dataObjects = $this->sortData($value, $record->ID);
			
			// New fields are nested in their own sub-array, and need to be sorted separately
 			if(isset($dataObjects['new']) && $dataObjects['new']) {
 				$newFields = $this->sortData($dataObjects['new'], $record->ID);
 			}

			// Update existing fields
			// @todo Should this be in an else{} statement?
			$savedObjIds = $this->saveData($dataObjects, $this->editExisting);
			
			// Save newly added record
			if($savedObjIds || $newFields) {
				$savedObjIds = $this->saveData($newFields,false);
 			}

			// Add the new records to the DataList
			if($savedObjIds) foreach($savedObjIds as $id => $status) {
				$this->getDataList()->add($id);
			}	

			// Update the internal source items cache
			$this->value = null;
			$items = $this->sourceItems();
			
			// FormResponse::update_dom_id($this->id(), $this->FieldHolder());
		}
	}
	
	/**
	 * Get all {@link FormField} instances necessary for a single row,
	 * respecting the casting set in {@link $fieldTypes}.
	 * Doesn't populate with any data. Optionally performs a readonly
	 * transformation if {@link $IsReadonly} is set, or the current user
	 * doesn't have edit permissions.
	 * 
	 * @return FieldList
	 */
	function FieldSetForRow() {
		$fieldset = new FieldList();
		if($this->fieldTypes){
			foreach($this->fieldTypes as $key => $fieldType) {
				if(isset($fieldType->class) && is_subclass_of($fieldType, 'FormField')) {
					// using clone, otherwise we would just add stuff to the same field-instance
					$field = clone $fieldType;
				} elseif(strpos($fieldType, '(') === false) {
					$field = new $fieldType($key);
				} else {
					$fieldName = $key;
					$fieldTitle = "";
					$field = eval("return new $fieldType;");
				}
				if($this->IsReadOnly || !$this->Can('edit')) {
					$field = $field->performReadonlyTransformation();
				}
				$fieldset->push($field);
			}
		}else{
			USER_ERROR("TableField::FieldSetForRow() - Fieldtypes were not specified",E_USER_WARNING);
		}

		return $fieldset;
	}
	
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->permissions = array('show');
		$clone->setReadonly(true);
		return $clone;
	}

	function performDisabledTransformation() {
		$clone = clone $this;
		$clone->setPermissions(array('show'));
		$clone->setDisabled(true);
		return $clone;
	}
	
	/**
	 * Needed for Form->callfieldmethod.
	 */
	public function getField($fieldName, $combinedFieldName = null) {
		$fieldSet = $this->FieldSetForRow();
		$field = $fieldSet->dataFieldByName($fieldName);
		if(!$field) {
			return false;
		}
		
		if($combinedFieldName) {
			$field->Name = $combinedFieldName;
		}

		return $field;
	}
		
	/**
	 * Called on save, it creates the appropriate objects and writes them
	 * to the database.
	 * 
	 * @param SS_List $dataObjects
	 * @param boolean $existingValues If set to TRUE, it tries to find existing objects
	 *  based on the database IDs passed as array keys in $dataObjects parameter.
	 *  If set to FALSE, it will always create new object (default: TRUE)
	 * @return array Array of saved object IDs in the key, and the status ("Updated") in the value
	 */
	function saveData($dataObjects, $existingValues = true) {
		if(!$dataObjects) return false;

		$savedObjIds = array();
		$fieldset = $this->FieldSetForRow();

		// add hiddenfields
		if($this->extraData) {
			foreach($this->extraData as $fieldName => $fieldValue) {
				$fieldset->push(new HiddenField($fieldName));
			}
		}

		$form = new Form($this, null, $fieldset, new FieldList());

		foreach ($dataObjects as $objectid => $fieldValues) {
			// 'new' counts as an empty column, don't save it
			if($objectid === "new") continue;

			// extra data was creating fields, but
			if($this->extraData) {
				$fieldValues = array_merge( $this->extraData, $fieldValues );
			}

			// either look for an existing object, or create a new one
			if($existingValues) {
				$obj = DataObject::get_by_id($this->sourceClass(), $objectid);
			} else {
				$sourceClass = $this->sourceClass();
				$obj = new $sourceClass();
			}

			// Legacy: Use the filter as a predefined relationship-ID 
			if($this->filterField && $this->filterValue) {
				$filterField = $this->filterField;
				$obj->$filterField = $this->filterValue;
			}

			// Determine if there is changed data for saving
			$dataFields = array();
			foreach($fieldValues as $type => $value) {
				// if the field is an actual datafield (not a preset hiddenfield)
				if(is_array($this->extraData)) { 
					if(!in_array($type, array_keys($this->extraData))) {
						$dataFields[$type] = $value;
					}
				// all fields are real 
				} else {  
					$dataFields[$type] = $value;
				}
			}
			$dataValues = ArrayLib::array_values_recursive($dataFields);
			// determine if any of the fields have a value (loose checking with empty())
			$hasData = false;
			foreach($dataValues as $value) {
				if(!empty($value)) $hasData = true;
			}

			if($hasData) {
				$form->loadDataFrom($fieldValues, true);
				$form->saveInto($obj);

				$objectid = $obj->write();

				$savedObjIds[$objectid] = "Updated";
			}

		}

		return $savedObjIds;
   }
	
	/** 
	 * Organises the data in the appropriate manner for saving
	 * 
	 * @param array $data 
	 * @param int $recordID
	 * @return array Collection of maps suitable to construct DataObjects
	 */
	function sortData($data, $recordID = null) {
		if(!$data) return false;
		
		$sortedData = array();
		
		foreach($data as $field => $rowData) {
			$i = 0;
			if(!is_array($rowData)) continue;
			
			foreach($rowData as $id => $value) {
				if($value == '$recordID') $value = $recordID;
				
				if($value) $sortedData[$id][$field] = $value;

				$i++;
			}
			
			// TODO ADD stuff for removing rows with incomplete data
		}
		
    	return $sortedData;
	}
	
	/**
	 * @param $extraData array
	 */
	function setExtraData($extraData) {
		$this->extraData = $extraData;
		return $this;
	}
	
	/**
	 * @return array
	 */
	function getExtraData() {
		return $this->extraData;
	}
	
	/**
	 * Sets the template to be rendered with
	 */
	function FieldHolder($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . "/prototype/prototype.js");
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/TableListField.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/TableField.js');
		Requirements::css(FRAMEWORK_DIR . '/css/TableListField.css');
		
		$obj = $properties ? $this->customise($properties) : $this;
		return $obj->renderWith($this->template);
	}
		
	function setTransformationConditions($conditions) {
		$this->transformationConditions = $conditions;
		return $this;
	}
	
	function php($data) {
		$valid = true;
		
		if($data['methodName'] != 'delete') {
			$items = $this->Items();
			if($items) foreach($items as $item) {
				foreach($item->Fields() as $field) {
					$valid = $field->validate($this) && $valid;
				}
			}

			return $valid;
		} else {
			return $valid;
		}
	}
	
	function validate($validator) {
		$errorMessage = '';
		$valid = true;
		
		// @todo should only contain new elements
		$items = $this->Items();
		if($items) foreach($items as $item) {
			foreach($item->Fields() as $field) {
				$valid = $field->validate($validator) && $valid;
			}
		}

		//debug::show($this->form->Message());
		if($this->requiredFields&&$sourceItemsNew&&$sourceItemsNew->count()) {
			foreach ($this->requiredFields as $field) {
				foreach($sourceItemsNew as $item){
					$cellName = $this->getName().'['.$item->ID.']['.$field.']';
					$cName =  $this->getName().'[new]['.$field.'][]';
					
					if($fieldObj = $fields->dataFieldByName($cellName)) {
						if(!trim($fieldObj->Value())){
							$title = $fieldObj->Title();
							$errorMessage .= sprintf(
								_t('TableField.ISREQUIRED', "In %s '%s' is required"),
								$this->name,
								$title
							);
							$errorMessage .= "<br />";
						}
					}
				}
			}
		}

		if($errorMessage){
			$messageType .= "validation";
			$message .= "<br />".$errorMessage;
		
			$validator->validationError($this->name, $message, $messageType);
		}

		return $valid;
	}
	
	function setRequiredFields($fields) {
		$this->requiredFields = $fields;
		return $this;
	}
}

/**
 * Single record in a TableField.
 * @package forms
 * @subpackage fields-relational
 * @see TableField
 */ 
class TableField_Item extends TableListField_Item {
	
	/**
	 * @var FieldList $fields
	 */
	protected $fields;
	
	protected $data;
	
	protected $fieldTypes;
	
	protected $isAddRow;
	
	protected $extraData;
	
	/** 
	 * Each row contains a dataobject with any number of attributes
	 * @param $ID int The ID of the record
	 * @param $form Form A Form object containing all of the fields for this item.  The data should be loaded in
	 * @param $fieldTypes array An array of name => fieldtype for use when creating a new field
	 * @param $parent TableListField The parent table for quick reference of names, and id's for storing values.
	 */
	function __construct($item = null, $parent, $form, $fieldTypes, $isAddRow = false) {
		$this->data = $form;
		$this->fieldTypes = $fieldTypes;
		$this->isAddRow = $isAddRow;
		$this->item = $item;

		parent::__construct(($this->item) ? $this->item : new DataObject(), $parent);
		
		$this->fields = $this->createFields();
	}
	/** 
	 * Represents each cell of the table with an attribute.
	 *
	 * @return FieldList
	 */
	function createFields() {
		// Existing record
		if($this->item && $this->data) {
			$form = $this->data;
			$this->fieldset = $form->Fields();
			$this->fieldset->removeByName('SecurityID');
			if($this->fieldset) {
				$i=0;
				foreach($this->fieldset as $field) {
					$origFieldName = $field->getName();

					// set unique fieldname with id
					$combinedFieldName = $this->parent->getName() . "[" . $this->ID() . "][" . $origFieldName . "]";
					// ensure to set field to nested array notation
					// if its an unsaved row, or the "add row" which is present by default
					if($this->isAddRow || $this->ID() == 'new') $combinedFieldName .= '[]';
					
					// get value
					if(strpos($origFieldName,'.') === false) {
						$value = $field->dataValue();
					} else {					
						// this supports the syntax fieldName = Relation.RelatedField				
						$fieldNameParts = explode('.', $origFieldName)	;
						$tmpItem = $this->item;
						for($j=0;$j<sizeof($fieldNameParts);$j++) {
							$relationMethod = $fieldNameParts[$j];
							$idField = $relationMethod . 'ID';
							if($j == sizeof($fieldNameParts)-1) {
								$value = $tmpItem->$relationMethod;
							} else {
								$tmpItem = $tmpItem->$relationMethod();
							}
						}
					}
					
					$field->Name = $combinedFieldName;
					$field->setValue($field->dataValue());
					$field->addExtraClass('col'.$i);
					$field->setForm($this->data); 

					// transformation
					if(isset($this->parent->transformationConditions[$origFieldName])) {
						$transformation = $this->parent->transformationConditions[$origFieldName]['transformation'];
						$rule = str_replace("\$","\$this->item->", $this->parent->transformationConditions[$origFieldName]['rule']);
						$ruleApplies = null;
						eval('$ruleApplies = ('.$rule.');');
						if($ruleApplies) {
							$field = $field->$transformation();
						}
					}
					
					// formatting
					$item = $this->item;
					$value = $field->Value();
					if(array_key_exists($origFieldName, $this->parent->fieldFormatting)) {
						$format = str_replace('$value', "__VAL__", $this->parent->fieldFormatting[$origFieldName]);
						$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$item->$1', $format);
						$format = str_replace('__VAL__', '$value', $format);
						eval('$value = "' . $format . '";');
						$field->dontEscape = true;
						$field->setValue($value);
					}
					
					$this->fields[] = $field;
					$i++;
				}
			}
		// New record
		} else {
			$list = $this->parent->FieldList();
			$i=0;
			foreach($list as $fieldName => $fieldTitle) {
				if(strpos($fieldName, ".")) {
					$shortFieldName = substr($fieldName, strpos($fieldName, ".")+1, strlen($fieldName));
				} else {
					$shortFieldName = $fieldName;
				}
				$combinedFieldName = $this->parent->getName() . "[new][" . $shortFieldName . "][]";
				$fieldType = $this->fieldTypes[$fieldName];
				if(isset($fieldType->class) && is_subclass_of($fieldType, 'FormField')) {
					$field = clone $fieldType; // we can't use the same instance all over, as we change names
					$field->Name = $combinedFieldName;
				} elseif(strpos($fieldType, '(') === false) {
					//echo ("<li>Type: ".$fieldType." fieldName: ". $filedName. " Title: ".$fieldTitle."</li>");
					$field = new $fieldType($combinedFieldName,$fieldTitle);
				} else {
					$field = eval("return new " . $fieldType . ";");
				}
				$field->addExtraClass('col'.$i);
				$this->fields[] = $field;
				$i++;
			}
		}
		return new FieldList($this->fields);
	}
	
	function Fields($xmlSafe = true) {
		return $this->fields;
	}
	
	function ExtraData() {
		$content = ""; 
		$id = ($this->item->ID) ? $this->item->ID : "new";
		if($this->parent->getExtraData()) {
			foreach($this->parent->getExtraData() as $fieldName=>$fieldValue) {
				$name = $this->parent->getName() . "[" . $id . "][" . $fieldName . "]";
				if($this->isAddRow) $name .= '[]';
				$field = new HiddenField($name, null, $fieldValue);
				$content .= $field->FieldHolder() . "\n";
			}
		}

		return $content;
	}
	
	/**
	 * Get the flag isAddRow of this item, 
	 * to indicate if the item is that blank last row in the table which is not in the database
	 * 
	 * @return boolean
	 */
	function IsAddRow(){
		return $this->isAddRow;
	}
	
}

