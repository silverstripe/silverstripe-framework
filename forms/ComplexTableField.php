<?php

/**
 * @package forms
 * @subpackage fields-relational
 */

/**
 * Provides a tabuar list in your form with view, edit and add links to edit records
 * with a "has-one"-relationship. Detail-views are shown in a greybox-iframe.
 * Features pagination in the overview as well as the detail-views.
 *
 * CAUTION: You need to make sure that the original form-call to the main controller (e.g. EditForm())
 * returns a form which includes this field even if no data is loaded,
 * to provide a "starting point" for action_callfieldmethod and ReferencedField.
 *
 * All URL data sent to and from ComplexTableField is encapsulated in $_REQUEST['ctf']
 * to avoid side-effects with the main controller.
 *
 * Example-URL for a "DetailForm"-call explained:
 * "/admin/family/?executeForm=EditForm&action_callfieldmethod&fieldName=Individual&childID=7&methodName=edit"
 *  - executeForm			Name of the form on the main rendering page (e.g. "FamilyAdmin")
 *  - action_callfieldmethod	Trigger to call a method of a single field in "EditForm" instead of rendering the whole thing
 *  - fieldName				Name of the targeted formField
 *  - methodName				Method on the formfield (e.g. "ComplexTableField")
 *  - childID				Identifier of the database-record (the targeted table is determined by the $sourceClass parameter)
 *
 * @todo Find a less fragile solution for accessing this field through the main controller and ReferencedField, e.g.
 *      build a seperate CTF-instance (doesn't necessarly have to be connected to the original by ReferencedField)
 * @todo Control width/height of popup by constructor (hardcoded at the moment)
 * @todo Integrate search from MemberTableField.php
 * @todo Less performance-hungry implementation of detail-view paging (don't return all items on a single view)
 * @todo Use automatic has-many and many-many functions to return a ComponentSet rather than building the join manually
 * @package forms
 * @subpackage fields-relational
 */
class ComplexTableField extends TableListField {

	protected $detailFormFields, $viewAction, $sourceJoin, $sourceItems, $unpagedSourceItems;

	/**
	 * @var Controller
	 */
	protected $controller;

	/**
	 * @var string Classname of the parent-relation to correctly link new records.
	 */
	protected $parentClass;

	/**
	 * @var string Database column name for the used relation (e.g. FamilyID
	 * if one Family has_many Individuals).
	 */
	protected $parentIdName;

	/**
	 * @var array Influence output without having to subclass the template.
	 */
	protected $permissions = array(
		"add",
		"edit",
		"show",
		"delete",
		//"export",
	);
	
	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "ComplexTableField";

	/**
	 * Template for popup (form rendering)
	 *
	 * @var string
	 */
	protected $templatePopup = "ComplexTableField_popup";

	/**
	 * Classname for each row/item
	 *
	 * @var string
	 */
	public $itemClass = 'ComplexTableField_Item';
	
	/**
	 * Classname for the popup form
	 *
	 * @var string
	 */
	public $popupClass = 'ComplexTableField_Popup';
	
	/**
	 * @var boolean Trigger pagination (defaults to true for ComplexTableField)
	 */
	protected $showPagination = true;

	/**
	 * @var string Caption the popup will show (defaults to the selected action).
	 * This is set by javascript and used by greybox.
	 */
	protected $popupCaption = null;

	/**
	 * @var $detailFormValidator Validator
	 */
	protected $detailFormValidator = null;
	
	/**
	 * Automatically detect a has-one relationship
	 * in the popup (=child-class) and save the relation ID.
	 *
	 * @var boolean
	 */
	protected $relationAutoSetting = true;
	
	/**
	 * Set the method for saving changes to items in the detail pop-up.
	 * By default, this is write, which just saves the changes to the database.
	 */
	public $itemWriteMethod = "write";
	
	/**
	 * See class comments
	 *
	 * @param ContentController $controller
	 * @param string $name
	 * @param string $sourceClass
	 * @param array $fieldList
	 * @param FieldSet $detailFormFields
	 * @param string $sourceFilter
	 * @param string $sourceSort
	 * @param string $sourceJoin
	 */
	function __construct($controller, $name, $sourceClass, $fieldList, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {
		$this->detailFormFields = $detailFormFields;
		$this->controller = $controller;
		$this->pageSize = 10;

		Requirements::javascript("jsparty/greybox/AmiJS.js");
		Requirements::javascript("jsparty/greybox/greybox.js");
		Requirements::javascript('sapphire/javascript/TableListField.js');
		Requirements::javascript("sapphire/javascript/ComplexTableField.js");
		Requirements::css("jsparty/greybox/greybox.css");
		Requirements::css("sapphire/css/ComplexTableField.css");
		
		parent::__construct($name, $sourceClass, $fieldList, $sourceFilter, $sourceSort, $sourceJoin);
		
	}

	function isComposite() {
		return false;
	}

	/**
	 * @return String
	 */
	function FieldHolder() {
		// set caption if required
		if($this->popupCaption) {
			$id = $this->id();
			if(Director::is_ajax()) {
			$js = <<<JS
$('$id').GB_Caption = '$this->popupCaption';
JS;
				FormResponse::add($js);
			} else {
			$js = <<<JS
Event.observe(window, 'load', function() { \$('$id').GB_Caption = '$this->popupCaption'; });
JS;
				Requirements::customScript($js);
			}
		}

		// compute sourceItems here instead of Items() to ensure that
		// pagination and filters are respected on template accessors
		$this->sourceItems();

		return $this->renderWith($this->template);
	}

	/**
	 * Returns non-paginated items.
	 * Please use Items() for pagination.
	 * This function is called whenever a complete result-set is needed,
	 * so even if a single record is displayed in a popup, we need the results
	 * to make pagination work.
	 *
	 * @todo Merge with more efficient querying of TableListField
	 */
	function sourceItems() {
		if($this->sourceItems) {
			return $this->sourceItems;
		}

		$limitClause = "";
		if($this->pageSize) {
			$limitClause = "{$this->pageSize}";
		} else {
			$limitClause = "0";
		}
		if(isset($_REQUEST['ctf'][$this->Name()]['start']) && is_numeric($_REQUEST['ctf'][$this->Name()]['start'])) {
			$SQL_start = intval($_REQUEST['ctf'][$this->Name()]['start']);
			$limitClause .= " OFFSET {$SQL_start}";
		}
		
		$sort = $this->sourceSort;
		if(isset($_REQUEST['ctf'][$this->Name()]['sort'])) {
			$sort = Convert::raw2sql($_REQUEST['ctf'][$this->Name()]['sort']);
		}
				
		$this->sourceItems = DataObject::get($this->sourceClass, $this->sourceFilter, $sort, $this->sourceJoin, $limitClause);

		$this->unpagedSourceItems = DataObject::get($this->sourceClass, $this->sourceFilter, $sort, $this->sourceJoin);

		$this->totalCount = ($this->unpagedSourceItems) ? $this->unpagedSourceItems->TotalItems() : null;

		return $this->sourceItems;
	}

	/**
	 * @return DataObjectSet
	 */
	function Items() {
		$this->sourceItems = $this->sourceItems();

		if(!$this->sourceItems) {
			return null;
		}

		$pageStart = (isset($_REQUEST['ctf'][$this->Name()]['start']) && is_numeric($_REQUEST['ctf'][$this->Name()]['start'])) ? $_REQUEST['ctf'][$this->Name()]['start'] : 0;
		$this->sourceItems->setPageLimits($pageStart, $this->pageSize, $this->totalCount);

		$output = new DataObjectSet();
		foreach($this->sourceItems as $pageIndex=>$item) {
			$output->push(Object::create($this->itemClass,$item, $this, $pageStart+$pageIndex));
		}
		return $output;
	}

	/**
	 * Sets the popup-title by javascript. Make sure to use FormResponse in ajax-requests,
	 * otherwise the title-change will only take effect on items existing during page-load.
	 *
	 * @param $caption String
	 */
	function setPopupCaption($caption) {
		$this->popupCaption = Convert::raw2js($caption);
	}

	/**
	 * Renders view, edit and add, depending on the given information.
	 * The form needs several parameters to function independently of its "parent-form", some derived from the context into a hidden-field,
	 * some derived from the parent context (which is not accessible here) and delivered by GET:
	 * ID, Identifier of the currently edited record (only if record is loaded).
	 * <parentIDName>, Link back to the correct parent record (e.g. "parentID").
	 * parentClass, Link back to correct container-class (the parent-record might have many 'has-one'-relationships)
	 * CAUTION: "ID" in the DetailForm would be the "childID" in the overview table.
	 * 
	 * @param int $childID
	 */
	function DetailForm($childID = null) {

		// Get all the requests
		$ID = isset($_REQUEST['ctf']['ID']) ? Convert::raw2xml($_REQUEST['ctf']['ID']) : null;
		if(!isset($childID)) $childID = isset($_REQUEST['ctf']['childID']) ? Convert::raw2xml($_REQUEST['ctf']['childID']) : null;
		$childClass = Convert::raw2xml($_REQUEST['fieldName']);
		$this->methodName = isset($_REQUEST['methodName']) ? $_REQUEST['methodName'] : null;

		// used to discover fields if requested and for population of field
		if(is_numeric($childID)) {
 			// we have to use the basedataclass, otherwise we might exclude other subclasses 
 			$childData = DataObject::get_by_id(ClassInfo::baseDataClass($this->sourceClass), $childID); 
		}
		
		// If the fieldset is passed, use it, else use the formfields returned
		// from the object via a string method call.
		if(is_a($this->detailFormFields,"Fieldset")){
			$detailFields = clone $this->detailFormFields;
		} else if( isset( $childData ) && is_string($this->detailFormFields)){
			$functioncall = $this->detailFormFields;
			if($childData->hasMethod($functioncall)){
				$detailFields = $childData->$functioncall();
			}
		} elseif(! isset( $childData ) || $this->methodName == 'add') {
			$SNG_sourceClass = singleton($this->sourceClass);
			if(is_numeric($ID) && $this->getParentClass()) {
				// make sure the relation-link is existing, even if we just add the sourceClass
				// and didn't save it
				$parentIDName = $this->getParentIdName( $this->getParentClass(), $this->sourceClass );
				$SNG_sourceClass->$parentIDName = $ID;
			}
			$functioncall = $this->detailFormFields;
			if($SNG_sourceClass->hasMethod($functioncall)){
				$detailFields = $SNG_sourceClass->$functioncall();
			}
			else
				$detailFields = $SNG_sourceClass->getCMSFields();
		} else {
			$detailFields = $childData->getCMSFields();
		}
		
		if($this->getParentClass()) {
			$parentIdName = $this->getParentIdName($this->getParentClass(), $this->sourceClass);
			if(!$parentIdName) {
				user_error("ComplexTableField::DetailForm() Cannot automatically 
					determine 'has-one'-relationship to parent, 
					please use setParentClass() to set it manually", 
				E_USER_WARNING);
				return;
			}
			// add relational fields
			$detailFields->push(new HiddenField("ctf[parentClass]"," ",$this->getParentClass()));
			
			if( $this->relationAutoSetting )
				$detailFields->push(new HiddenField("$parentIdName"," ",$ID));
		} 

		// the ID field confuses the Controller-logic in finding the right view for ReferencedField
		$detailFields->removeByName('ID');

		// only add childID if we're not adding a record		
		if($this->methodName != 'add') {
			$detailFields->push(new HiddenField("ctf[childID]","",$childID));
		}

		// add a namespaced ID instead thats "converted" by saveComplexTableField()
		$detailFields->push(new HiddenField("ctf[ClassName]","",$this->sourceClass));

		$readonly = ($this->methodName == "show");

		// if no custom validator is set, and there's on present on the object (e.g. Member), use it
		if(!isset($this->detailFormValidator) && singleton($this->sourceClass)->hasMethod('getValidator')) {
			$this->detailFormValidator = singleton($this->sourceClass)->getValidator();
		}

		$form = Object::create($this->popupClass,$this, "DetailForm", $detailFields, $this->sourceClass, $readonly, $this->detailFormValidator);
	
		if (is_numeric($childID)) {
			if ($this->methodName == "show" || $this->methodName == "edit") {
				$form->loadDataFrom($childData);
			}
		}

		if ($this->methodName == "show") {
			$form->makeReadonly();
		}

		return $form;
	}

	/**
	 * @param $validator Validator
	 */
	function setDetailFormValidator( Validator $validator ) {
		$this->detailFormValidator = $validator;
	}
	
	/**
	 * Returns the content of this formfield without surrounding layout. Triggered by Javascript
	 * to update content after a DetailForm-save-action.
	 *
	 * @return String
	 */
	function ajax_render() {
		return $this->renderWith($this->template);
	}

	/**
	 * Just a hook, processed in {DetailForm()}
	 *
	 * @return String
	 */
	function show() {
		if($this->Can('show') !== true) {
			return false;
		}

		$this->methodName = "edit";

		$this->sourceItems = $this->sourceItems();

		$this->pageSize = 1;
		
		if(isset($_REQUEST['ctf'][$this->Name()]['start']) && is_numeric($_REQUEST['ctf'][$this->Name()]['start'])) {
			$this->unpagedSourceItems->setPageLimits($_REQUEST['ctf'][$this->Name()]['start'], $this->pageSize, $this->totalCount);
		}

		echo $this->renderWith($this->templatePopup);
	}

	/**
	 * Just a hook, processed in {DetailForm()}
	 *
	 * @return String
	 */
	function edit() {
		if($this->Can('edit') !== true) {
			return false;
		}

		$this->methodName = "edit";

		$this->sourceItems = $this->sourceItems();

		$this->pageSize = 1;

		if(is_numeric($_REQUEST['ctf']['start'])) {
			$this->unpagedSourceItems->setPageLimits($_REQUEST['ctf']['start'], $this->pageSize, $this->totalCount);
		}

		echo $this->renderWith($this->templatePopup);
	}

	/**
	 * Just a hook, processed in {DetailForm()}
	 *
	 * @return String
	 */
	function add() {
		if($this->Can('add') !== true) {
			return false;
		}

		$this->methodName = "add";

		echo $this->renderWith($this->templatePopup);
	}

	/**
	 * Calculates the number of columns needed for colspans
	 * used in template
	 *
	 * @return Int
	 */
	function ItemCount() {
		return count($this->fieldList);
	}

	/**
	 * Used to toggle paging (makes no sense when adding a record)
	 *
	 * @return Boolean
	 */
	function IsAddMode() {
		return ($this->methodName == "add");
	}
	
	function sourceID() { 
		$idField = $this->form->dataFieldByName('ID'); 
		if(!$idField) { 
			user_error("ComplexTableField needs a formfield named 'ID' to be present", E_USER_ERROR); 
		} 
		// because action_callfieldmethod never actually loads data into the form,
		// we can't rely on $idField being populated, and fall back to the request-params.
		// this is a workaround for a bug where each subsequent popup-call didn't have ID
		// of the parent set, and so didn't properly save the relation
		return ($idField->Value()) ? $idField->Value() : (isset($_REQUEST['ctf']['ID']) ? $_REQUEST['ctf']['ID'] : null); 
 	} 
	 
	/**
	 * #################################
	 *           Pagination
	 * #################################
	 */
	function PopupBaseLink() {
		$link = $this->FormAction() . "&action_callfieldmethod&fieldName={$this->Name()}";
		if(!strpos($link,'ctf[ID]')) {
			$link = str_replace('&amp;','&',HTTP::setGetVar('ctf[ID]',$this->sourceID(),$link));
		}
		return $link;
	}

	function PopupCurrentItem() {
		return $_REQUEST['ctf']['start']+1;
	}

	function PopupFirstLink() {
		if(!is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == 0) {
			return null;
		}

		$item = $this->unpagedSourceItems->First();
		$start = 0;
		return Convert::raw2att($this->PopupBaseLink() . "&methodName={$_REQUEST['methodName']}&ctf[childID]={$item->ID}&ctf[start]={$start}");
	}

	function PopupLastLink() {
		if(!is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == $this->totalCount-1) {
			return null;
		}

		$item = $this->unpagedSourceItems->Last();
		$start = $this->totalCount - 1;
		return Convert::raw2att($this->PopupBaseLink() . "&methodName={$_REQUEST['methodName']}&ctf[childID]={$item->ID}&ctf[start]={$start}");
	}

	function PopupNextLink() {
		if(!is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == $this->totalCount-1) {
			return null;
		}

		$item = $this->unpagedSourceItems->getIterator()->getOffset($_REQUEST['ctf']['start'] + 1);

		$start = $_REQUEST['ctf']['start'] + 1;
		return Convert::raw2att($this->PopupBaseLink() . "&methodName={$_REQUEST['methodName']}&ctf[childID]={$item->ID}&ctf[start]={$start}");
	}

	function PopupPrevLink() {
		if(!is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == 0) {
			return null;
		}

		$item = $this->unpagedSourceItems->getIterator()->getOffset($_REQUEST['ctf']['start'] - 1);

		$start = $_REQUEST['ctf']['start'] - 1;
		return Convert::raw2att($this->PopupBaseLink() . "&methodName={$_REQUEST['methodName']}&ctf[childID]={$item->ID}&ctf[start]={$start}");
	}
	
	/**
     * Method handles pagination in asset popup.
     *
     * @return Object DataObjectSet
     */
	
	function Pagination() {
		$this->pageSize = 9;
		$currentItem  = $this->PopupCurrentItem();
		$result = new DataObjectSet();
        if($currentItem < 6) {
        	$offset = 1;
        } elseif($this->totalCount - $currentItem <= 4) {
        	$offset = $currentItem - (10 - ($this->totalCount - $currentItem));
        	$offset = $offset <= 0 ? 1 : $offset;
        } else {
        	$offset = $currentItem  - 5; 
        }
		for($i = $offset;$i <= $offset + $this->pageSize && $i <= $this->totalCount;$i++) {
            $start = $i - 1;
			$item = $this->unpagedSourceItems->getIterator()->getOffset($i-1);
			$links['link'] = Convert::raw2att($this->PopupBaseLink() . "&methodName={$_REQUEST['methodName']}&ctf[childID]={$item->ID}&ctf[start]={$start}");
            $links['number'] = $i;
            $links['active'] = $i == $currentItem ? false : true;
            $result->push(new ArrayData($links)); 	
		}
        return $result;
	}



	/**
	 * #################################
	 *           Utility
	 * #################################
	 */

	/**
	 * Get part of class ancestry (even if popup is not subclassed it might be styled differently in css)
	 */
	function PopupClasses() {
		global $_ALL_CLASSES;

		$items = array();
		$parents = $_ALL_CLASSES['parents'][$this->class];
		foreach($parents as $parent) {
			if(!in_array($parent,$_ALL_CLASSES['parents']["TableListField"])) $items[] = $parent . "_Popup";
		}
		$items[] = $this->class . "_Popup";

		return implode(" ", $items);
	}

	function AddLink() {
		return Convert::raw2att("{$this->PopupBaseLink()}&methodName=add");
	}

	/**
	 * @return FieldSet
	 */
	function createFieldSet() {
		$fieldset = new FieldSet();
		foreach($this->fieldTypes as $key => $fieldType){
			$fieldset->push(new $fieldType($key));
		}
		return $fieldset;
	}

	/**
	 * Determines on which relation-class the DetailForm is saved
	 * by looking at the surrounding form-record.
	 *
	 * @return String
	 */
	function getParentClass() {
		if($this->parentClass === false) {
			// purposely set parent-relation to false
			return false;
		} elseif(!empty($this->parentClass)) {
			return $this->parentClass;
		} else {
			return $this->form->getRecord()->ClassName;
		}
	}

	/**
	 * (Optional) Setter for a correct parent-relation-class.
	 * Defaults to the record loaded into the surrounding form as a fallback.
	 * Caution: Please use the classname, not the actual column-name in the database.
	 *
	 * @param $className string
	 */
	function setParentClass($className) {
		$this->parentClass = $className;
	}

	/**
	 * Returns the db-fieldname of the currently used has_one-relationship.
	 */
	function getParentIdName( $parentClass, $childClass ) {
		return $this->getParentIdNameRelation( $childClass, $parentClass, 'has_one' );
	}
	
	/**
	 * Manually overwrites the parent-ID relations.
	 * @see setParentClass()
	 * 
	 * @param String $str Example: FamilyID (when one Individual has_one Family)
	 */
	function setParentIdName($str) {
		$this->parentIdName = $str;
	}
	
	/**
	 * Returns the db-fieldname of the currently used relationship.
	 */
	function getParentIdNameRelation( $parentClass, $childClass, $relation ){
		if($this->parentIdName) return $this->parentIdName; 
		
		$relations = singleton( $parentClass )->$relation();
		$classes = ClassInfo::ancestry( $childClass );
		foreach( $relations as $k => $v ) {
			if( $v == $childClass )
				return $k . 'ID';
			else if( array_key_exists( $v, $classes ) )
				return $classes[ $v ] . 'ID';
		}
		return false;
	}

	function setTemplatePopup($template) {
		$this->templatePopup = $template;
	}


}

/**
 * Single row of a {@link ComplexTableField}.
 * @package forms
 * @subpackage fields-relational
 */
class ComplexTableField_Item extends TableListField_Item {
	/**
	 * Needed to transfer pagination-status from overview.
	 */
	protected $start;

	function __construct(DataObject $item, ComplexTableField $parent, $start) {
		$this->start = $start;

		parent::__construct($item, $parent);
	}

	function PopupBaseLink() {
		return $this->parent->FormAction() . "&action_callfieldmethod&fieldName={$this->parent->Name()}&ctf[childID]={$this->item->ID}&ctf[ID]={$this->parent->sourceID()}&ctf[start]={$this->start}";
	}

	function EditLink() {
		return Convert::raw2att($this->PopupBaseLink() . "&methodName=edit");
	}

	function ShowLink() {
		return Convert::raw2att($this->PopupBaseLink() . "&methodName=show");
	}

	function DeleteLink() {
		return Convert::raw2att($this->PopupBaseLink() . "&methodName=delete");
	}
}


/**
 * ComplexTablefield_popup is rendered with a lightbox and can load a more
 * detailed view of the source class your presenting.
 * You can customise the fields and requirements as well as any
 * permissions you might need.
 * @package forms
 * @subpackage fields-relational
 */
class ComplexTableField_Popup extends Form {
	protected $sourceClass;

	function __construct($controller, $name, $field, $sourceClass, $readonly=false, $validator = null) {

		/**
		 * WARNING: DO NOT CHANGE THE ORDER OF THESE JS FILES
		 * Some have special requirements.
		 */
		Requirements::clear();
		//Requirements::css('cms/css/layout.css');
		Requirements::css('jsparty/tabstrip/tabstrip.css');
		Requirements::css('sapphire/css/Form.css');
		Requirements::css('sapphire/css/ComplexTableField_popup.css');
		Requirements::css('cms/css/typography.css');
		Requirements::css('cms/css/cms_right.css');
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/tabstrip/tabstrip.js");
		Requirements::javascript("jsparty/scriptaculous/scriptaculous.js");
		Requirements::javascript("jsparty/scriptaculous/controls.js");
		Requirements::javascript("jsparty/layout_helpers.js");
		Requirements::javascript("cms/javascript/LeftAndMain.js");
		Requirements::javascript("cms/javascript/LeftAndMain_right.js");
		Requirements::javascript("sapphire/javascript/TableField.js");
		Requirements::javascript("sapphire/javascript/ComplexTableField.js");
		Requirements::javascript("sapphire/javascript/ComplexTableField_popup.js");

		$this->sourceClass = $sourceClass;
		if(singleton($sourceClass)->hasMethod('getRequirementsForPopup')){
			singleton($sourceClass)->getRequirementsForPopup();
		}
		
		$actions = new FieldSet();	
		if(!$readonly) {
			$actions->push(
				$saveAction = new FormAction("saveComplexTableField", "Save")
			);	
			$saveAction->addExtraClass('save');
		}
		
		parent::__construct($controller, $name, $field, $actions, $validator);
	}

	function FieldHolder() {
		return $this->renderWith('ComplexTableField_Form');
	}

	function ShowPagination() {
		return $this->controller->ShowPagination();
	}


	/**
	 * Use the URL-Parameter "action_saveComplexTableField"
	 * to provide a clue to the main controller if the main form has to be rendered,
	 * even if there is no action relevant for the main controller (to provide the instance of ComplexTableField
	 * which in turn saves the record.
	 *
	 * @see {Form::ReferencedField}).
	 */
	function saveComplexTableField() {
		if(isset($_REQUEST['ctf']['childID']) && is_numeric($_REQUEST['ctf']['childID'])) {
			$childObject = DataObject::get_by_id($this->sourceClass, $_REQUEST['ctf']['childID']);
		} else {
			$childObject = new $this->sourceClass();
			$this->fields->removeByName('ID');
		}

		$this->saveInto($childObject);
		
		$funcName = $this->controller->itemWriteMethod;
		if(!$funcName) $funcName = "write";
		$childObject->$funcName();

		// if ajax-call in an iframe, update window
		if(Director::is_ajax()) {
			// Newly saved objects need their ID reflected in the reloaded form to avoid double saving 
			$form = $this->controller->DetailForm($childObject->ID);
			$form->loadDataFrom($childObject);
			FormResponse::update_dom_id($form->FormName(), $form->formHtmlContent(), true, 'update');
			return FormResponse::respond();
		} else {
			Director::redirectBack();
		}
	}

}
?>
