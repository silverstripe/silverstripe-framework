<?php
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
	public $parentClass;

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
	public $templatePopup = "ComplexTableField_popup";

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


	static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'$Action!' => '$Action',
	);

	function handleItem($request) {
		return new ComplexTableField_ItemRequest($this, $request->param('ID'));
	}
	
	function getViewer() {
		return new SSViewer('ComplexTableField');
	}

	
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
	
	function sourceClass() {
		return $this->sourceClass;
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
	 


	function AddLink() {
		return $this->Link() . '/add';
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

	////////////////////////////////////////////////////////////////////////////////////////////////////

	function getFieldsFor($childData) {
		// Add the relation value to related records
		if(!$childData->ID && $this->getParentClass()) {
			// make sure the relation-link is existing, even if we just add the sourceClass and didn't save it
			$parentIDName = $this->getParentIdName( $this->getParentClass(), $this->sourceClass() );
			$childData->$parentIDName = $childData->ID;
		}

		// If the fieldset is passed, use it
		if(is_a($this->detailFormFields,"Fieldset")) {
			$detailFields = $this->detailFormFields;

		// Else use the formfields returned from the object via a string method call.
		} else {
			if(!is_string($this->detailFormFields)) $this->detailFormFields = "getCMSFields";
			$functioncall = $this->detailFormFields;
			if(!$childData->hasMethod($functioncall)) $functioncall = "getCMSFields";
			
			$detailFields = $childData->$functioncall();
		}

		// the ID field confuses the Controller-logic in finding the right view for ReferencedField
		$detailFields->removeByName('ID');

		// only add childID if we're not adding a record		
		if($childData->ID) {
			$detailFields->push(new HiddenField("ctf[childID]","",$childData->ID));
		}

		// add a namespaced ID instead thats "converted" by saveComplexTableField()
		$detailFields->push(new HiddenField("ctf[ClassName]","",$this->sourceClass()));

		if($this->getParentClass()) {
			$parentIdName = $this->getParentIdName($this->getParentClass(), $this->sourceClass());
			/*
			if(!$parentIdName) {
				user_error("ComplexTableField::DetailForm() Cannot automatically 
					determine 'has-one'-relationship to parent class " . $this->ctf->getParentClass() .  ",
					please use setParentClass() to set it manually", 
				E_USER_WARNING);
				return;
			}
			*/
			
			if($parentIdName) {
				// add relational fields
				$detailFields->push(new HiddenField("ctf[parentClass]"," ",$this->getParentClass()));
			
				if( $this->relationAutoSetting ) {
					// Hack for model admin: model admin will have included a dropdown for the relation itself
					$detailFields->removeByName($parentIdName);
					$detailFields->push(new HiddenField("$parentIdName"," ",$this->sourceID()));
				}
			}
		} 
		
		return $detailFields;
	}

	function getValidatorFor($childData) {
		// if no custom validator is set, and there's on present on the object (e.g. Member), use it
		if(!isset($this->detailFormValidator) && $childData->hasMethod('getValidator')) {
			$this->detailFormValidator = $childData->getValidator();
		}
		return $this->detailFormValidator;
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function add() {
		if(!$this->can('add')) return;
		
		return $this->customise(array(
			'DetailForm' => $this->AddForm(),
		))->renderWith($this->templatePopup);
	}

	function AddForm($childID = null) {
		$className = $this->sourceClass();
		$childData = new $className();
		
		$fields = $this->getFieldsFor($childData);
		$validator = $this->getValidatorFor($childData);

		$form = Object::create(
				$this->popupClass,
				$this, "AddForm", 
				$fields, $validator, false, $childData);

		return $form;
	}

	/**
	 * Use the URL-Parameter "action_saveComplexTableField"
	 * to provide a clue to the main controller if the main form has to be rendered,
	 * even if there is no action relevant for the main controller (to provide the instance of ComplexTableField
	 * which in turn saves the record.
	 *
	 * @see {Form::ReferencedField}).
	 */
	function saveComplexTableField($data, $form, $params) {
		$className = $this->sourceClass();
		$childData = new $className();
		$form->saveInto($childData);
		$childData->write();

		// if ajax-call in an iframe, update window
		if(Director::is_ajax()) {
			// Newly saved objects need their ID reflected in the reloaded form to avoid double saving 
			$childRequestHandler = new ComplexTableField_ItemRequest($this, $childData->ID);
			$form = $childRequestHandler->DetailForm();
			FormResponse::update_dom_id($form->FormName(), $form->formHtmlContent(), true, 'update');
			return FormResponse::respond();
		} else {
			Director::redirectBack();
		}
	}
}

/**
 * @todo Tie this into ComplexTableField_Item better.
 */
class ComplexTableField_ItemRequest extends RequestHandlingData {
	protected $ctf;
	protected $itemID;
	protected $methodName;
	
	static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);
	
	function Link() {
		return $this->ctf->Link() . '/item/' . $this->itemID;
	}
	
	function __construct($ctf, $itemID) {
		$this->ctf = $ctf;
		$this->itemID = $itemID;
	}
	
	function index() {
		return $this->show();
	}

	/**
	 * Just a hook, processed in {DetailForm()}
	 *
	 * @return String
	 */
	function show() {
		if($this->ctf->Can('show') !== true) {
			return false;
		}

		$this->methodName = "show";
		echo $this->renderWith($this->ctf->templatePopup);
	}
	
	/**
	 * Returns a 1-element data object set that can be used for pagination.
	 */
	/* this doesn't actually work :-(
	function Paginator() { 
		$paginatingSet = new DataObjectSet(array($this->dataObj()));
		$start = isset($_REQUEST['ctf']['start']) ? $_REQUEST['ctf']['start'] : 0;
		$paginatingSet->setPageLimits($start, 1, $this->ctf->TotalCount());
		return $paginatingSet;
	}
	*/

	/**
	 * Just a hook, processed in {DetailForm()}
	 *
	 * @return String
	 */
	function edit() {
		if($this->ctf->Can('edit') !== true) {
			return false;
		}

		$this->methodName = "edit";
		echo $this->renderWith($this->ctf->templatePopup);
	}

	function delete() {
		if($this->ctf->Can('delete') !== true) {
			return false;
		}

		$this->dataObj()->delete();
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Return the data object being manipulated
	 */
	function dataObj() {
		// used to discover fields if requested and for population of field
		if(is_numeric($this->itemID)) {
 			// we have to use the basedataclass, otherwise we might exclude other subclasses 
 			return DataObject::get_by_id(ClassInfo::baseDataClass($this->ctf->sourceClass()), $this->itemID); 
		}
		
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
		$childData = $this->dataObj();
		
		$fields = $this->ctf->getFieldsFor($childData);
		$validator = $this->ctf->getValidatorFor($childData);
		$readonly = ($this->methodName == "show");

		$form = Object::create(
				$this->ctf->popupClass,
				$this, "DetailForm", 
				$fields, $validator, $readonly, $childData);
	
		$form->loadDataFrom($childData);
		if ($readonly) $form->makeReadonly();

		return $form;
	}

	/**
	 * Use the URL-Parameter "action_saveComplexTableField"
	 * to provide a clue to the main controller if the main form has to be rendered,
	 * even if there is no action relevant for the main controller (to provide the instance of ComplexTableField
	 * which in turn saves the record.
	 *
	 * @see {Form::ReferencedField}).
	 */
	function saveComplexTableField($data, $form, $request) {
		$form->saveInto($this->dataObj());
		$this->dataObj()->write();

		// if ajax-call in an iframe, update window
		if(Director::is_ajax()) {
			// Newly saved objects need their ID reflected in the reloaded form to avoid double saving 
			$form = $this->DetailForm();
			//$form->loadDataFrom($this->dataObject);
			FormResponse::update_dom_id($form->FormName(), $form->formHtmlContent(), true, 'update');
			return FormResponse::respond();
			
		} else {
			Director::redirectBack();
		}
	}
	
	function PopupCurrentItem() {
		return $_REQUEST['ctf']['start']+1;
	}
	
	function PopupFirstLink() {
		$this->ctf->LinkToItem();
		
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == 0) {
			return null;
		}

		$item = $this->unpagedSourceItems->First();
		$start = 0;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
	}

	function PopupLastLink() {
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == $this->totalCount-1) {
			return null;
		}

		$item = $this->unpagedSourceItems->Last();
		$start = $this->totalCount - 1;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
	}

	function PopupNextLink() {
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == $this->totalCount-1) {
			return null;
		}

		$item = $this->unpagedSourceItems->getIterator()->getOffset($_REQUEST['ctf']['start'] + 1);

		$start = $_REQUEST['ctf']['start'] + 1;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
	}

	function PopupPrevLink() {
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == 0) {
			return null;
		}

		$item = $this->unpagedSourceItems->getIterator()->getOffset($_REQUEST['ctf']['start'] - 1);

		$start = $_REQUEST['ctf']['start'] - 1;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
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
			$links['link'] = Controller::join_links($this->Link() . "$this->methodName?ctf[start]={$start}");
            $links['number'] = $i;
            $links['active'] = $i == $currentItem ? false : true;
            $result->push(new ArrayData($links)); 	
		}
        return $result;
	}

	function ShowPagination() {
		return false;
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
	function getParentIdNameRelation($parentClass, $childClass, $relation) {
		if($this->parentIdName) return $this->parentIdName; 
		
		$relations = singleton($parentClass)->$relation();
		$classes = ClassInfo::ancestry($childClass);
		foreach($relations as $k => $v) {
			if(array_key_exists($v, $classes)) return $k . 'ID';
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

	function Link() {
		return $this->parent->Link() . '/item/' . $this->item->ID;
	}

	function EditLink() {
		return $this->Link() . "/edit";
	}

	function ShowLink() {
		return $this->Link() . "/show";
	}

	function DeleteLink() {
		return $this->Link() . "/delete";
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
	protected $dataObject;

	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		$this->dataObject = $dataObject;

		/**
		 * WARNING: DO NOT CHANGE THE ORDER OF THESE JS FILES
		 * Some have special requirements.
		 */
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

 		if($this->dataObject->hasMethod('getRequirementsForPopup')) {
			$this->dataObject->getRequirementsForPopup();
		}
		
		$actions = new FieldSet();	
		if(!$readonly) {
			$actions->push(
				$saveAction = new FormAction("saveComplexTableField", "Save")
			);	
			$saveAction->addExtraClass('save');
		}
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	function FieldHolder() {
		return $this->renderWith('ComplexTableField_Form');
	}
}

/**
 * Used by ModelAdmin scaffolding, to manage many-many relationships.
 */
class ScaffoldingComplexTableField_Popup extends Form {
	protected $sourceClass;
	protected $dataObject;
	
	public static $allowed_actions = array(
		'filter', 'record', 'httpSubmission', 'handleAction', 'handleField'
	);

	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		$this->dataObject = $dataObject;

		/**
		 * WARNING: DO NOT CHANGE THE ORDER OF THESE JS FILES
		 * Some have special requirements.
		 */
		//Requirements::css('cms/css/layout.css');
		Requirements::css('jsparty/tabstrip/tabstrip.css');
		Requirements::css('sapphire/css/Form.css');
		Requirements::css('sapphire/css/ComplexTableField_popup.css');
		Requirements::css('cms/css/typography.css');
		Requirements::css('cms/css/cms_right.css');
		Requirements::css('jsparty/jquery/plugins/autocomplete/jquery.ui.autocomplete.css');
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
		// jQuery requirements (how many of these are actually needed?)
		Requirements::javascript('jsparty/jquery/jquery.js');
		Requirements::javascript('jsparty/jquery/plugins/livequery/jquery.livequery.js');
		Requirements::javascript('jsparty/jquery/ui/ui.core.js');
		Requirements::javascript('jsparty/jquery/ui/ui.tabs.js');
		Requirements::javascript('jsparty/jquery/plugins/form/jquery.form.js');
		Requirements::javascript('jsparty/jquery/plugins/dimensions/jquery.dimensions.js');
		Requirements::javascript('jsparty/jquery/plugins/autocomplete/jquery.ui.autocomplete.js');
		Requirements::javascript('sapphire/javascript/ScaffoldComplexTableField.js');
		Requirements::javascript('cms/javascript/ModelAdmin.js');
		
 		if($this->dataObject->hasMethod('getRequirementsForPopup')) {
			$this->dataObject->getRequirementsForPopup();
		}
		
		$actions = new FieldSet();	
		if(!$readonly) {
			$actions->push(
				$saveAction = new FormAction("saveComplexTableField", "Save")
			);	
			$saveAction->addExtraClass('save');
		}
		
		$fields->push(new HiddenField("ComplexTableField_Path", Director::absoluteBaseURL()));
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	function FieldHolder() {
		return $this->renderWith('ComplexTableField_Form');
	}
	
	
	/**
	 * Handle a generic action passed in by the URL mapping.
	 *
	 * @param HTTPRequest $request
	 */
	public function handleAction($request) {
		$action = str_replace("-","_",$request->param('Action'));
		if(!$this->action) $this->action = 'index';
		
		if($this->checkAccessAction($action)) {
			if($this->hasMethod($action)) {
				$result = $this->$action($request);
			
				// Method returns an array, that is used to customise the object before rendering with a template
				if(is_array($result)) {
					return $this->getViewer($action)->process($this->customise($result));
				
				// Method returns a string / object, in which case we just return that
				} else {
					return $result;
				}
			
			// There is no method, in which case we just render this object using a (possibly alternate) template
			} else {
				return $this->getViewer($action)->process($this);
			}
		} else {
			return $this->httpError(403, "Action '$action' isn't allowed on class $this->class");
		}		
	}
	
	/**
	 * Action to render results for an autocomplete filter.
	 *
	 * @param HTTPRequest $request
	 * @return void
	 */	
	function filter($request) {
		//$model = singleton($this->modelClass);
		$context = $this->dataObject->getDefaultSearchContext();
		$value = $request->getVar('q');
		$results = $context->getResults(array("Name"=>$value));
		header("Content-Type: text/plain");
		foreach($results as $result) {
			echo $result->Name . "\n";
		}		
	}
	
	/**
	 * Action to populate edit box with a single data object via Ajax query
	 */
	function record($request) {
		$type = $request->getVar('type');
		$value = $request->getVar('value');
		if ($type && $value) {
			$record = DataObject::get_one($this->dataObject->class, "$type = '$value'");
			header("Content-Type: text/plain");
			echo json_encode(array("record"=>$record->toMap()));
		}
	}

}

?>
