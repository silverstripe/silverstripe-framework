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

	/**
	 * Determines the fields of the detail pop-up form.  It can take many forms:
	 *  - A FieldSet object: Use that field set directly.
	 *  - A method name, eg, 'getCMSFields': Call that method on the child object to get the fields.
	 */
	protected $addTitle;
    
	protected $detailFormFields;
	
	protected $viewAction, $sourceJoin, $sourceItems;

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
	 * @var callback A function callback invoked
	 * after initializing the popup and its base calls to
	 * the {@link Requirements} class.
	 */
	public $requirementsForPopupCallback = null;

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
	 * Default size for the popup box
	 */
	protected $popupWidth = 560;
	protected $popupHeight = 390;
	
	public $defaultAction = 'show';
	
	public $actions = array(
		'show' => array(
			'label' => 'Show',
			'icon' => 'cms/images/show.png',
			'icon_disabled' => 'cms/images/show_disabled.png',
			'class' => 'popuplink showlink',
		),
		'edit' => array(
			'label' => 'Edit',
			'icon' => 'cms/images/edit.gif', 
			'icon_disabled' => 'cms/images/edit_disabled.gif',
			'class' => 'popuplink editlink',
		),
		'delete' => array(
			'label' => 'Delete',
			'icon' => 'cms/images/delete.gif', 
			'icon_disabled' => 'cms/images/delete_disabled.gif',
			'class' => 'popuplink deletelink',
		),
	);

	static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'$Action!' => '$Action',
	);

	function handleItem($request) {
		return new ComplexTableField_ItemRequest($this, $request->param('ID'));
	}
	
	function getViewer() {
		return new SSViewer($this->template);
	}

	function setPopupSize($width, $height) {
		$width = (int)$width;
		$height = (int)$height;
		
		if($width < 0 || $height < 0) {
			user_error("setPopupSize expects non-negative arguments.", E_USER_WARNING);
			return;
		}
		
		$this->popupWidth = $width;
		$this->popupHeight = $height;
	}
	
	function PopupWidth() {
		return $this->popupWidth;
	}
	       
	function PopupHeight() {
		return $this->popupHeight;
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
	function __construct($controller, $name, $sourceClass, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {
		$this->detailFormFields = $detailFormFields;
		$this->controller = $controller;
		$this->pageSize = 10;
		
		parent::__construct($name, $sourceClass, $fieldList, $sourceFilter, $sourceSort, $sourceJoin);
	}

	/**
	 * Return the record filter for this table.
	 * It will automatically add a relation filter if relationAutoSetting is true, and it can determine an appropriate
	 * filter.
	 */
	function sourceFilter() {
		$sourceFilter = parent::sourceFilter();
		
		if($this->relationAutoSetting
			 	&& $this->getParentClass() 
				&& ($filterKey = $this->getParentIdName($this->getParentClass(), $this->sourceClass()))
				&& ($filterValue = $this->sourceID()) ) {
					
			$newFilter = "\"$filterKey\" = '" . Convert::raw2sql($filterValue) . "'";

			if($sourceFilter && is_array($sourceFilter)) {
				// Note that the brackets below are taken into account when building this
				$sourceFilter = implode(") AND (", $sourceFilter);
			}

			$sourceFilter = $sourceFilter ? "($sourceFilter) AND ($newFilter)" : $newFilter;
		}
		return $sourceFilter;
	}

	function isComposite() {
		return false;
	}

	/**
	 * @return String
	 */
	function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . "/greybox/AmiJS.js");
		Requirements::javascript(THIRDPARTY_DIR . "/greybox/greybox.js");
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/TableListField.js');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ComplexTableField.js");
		Requirements::css(THIRDPARTY_DIR . "/greybox/greybox.css");
		Requirements::css(SAPPHIRE_DIR . "/css/TableListField.css");
		Requirements::css(SAPPHIRE_DIR . "/css/ComplexTableField.css");
		
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
			$output->push(new $this->itemClass($item, $this));
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
    
    function setAddTitle($addTitle) {
		if(is_string($addTitle))
			$this->addTitle = $addTitle;
	}
    
    function Title() {
		return $this->addTitle ? $this->addTitle : parent::Title();
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
		return ($this->methodName == "add" || $this->request->param('Action') == 'AddForm');
	}
	
	function sourceID() { 
		$idField = $this->form->dataFieldByName('ID'); 

		// disabled as it conflicts with scaffolded formfields, and not strictly necessary
		// if(!$idField) user_error("ComplexTableField needs a formfield named 'ID' to be present", E_USER_ERROR); 

		// because action_callfieldmethod never actually loads data into the form,
		// we can't rely on $idField being populated, and fall back to the request-params.
		// this is a workaround for a bug where each subsequent popup-call didn't have ID
		// of the parent set, and so didn't properly save the relation
		return ($idField) ? $idField->Value() : (isset($_REQUEST['ctf']['ID']) ? $_REQUEST['ctf']['ID'] : null); 
 	} 
	 


	function AddLink() {
		return Controller::join_links($this->Link(), 'add');
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

	function setController($controller) {
		$this->controller = $controller;
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
		} elseif($this->form && $this->form->getRecord()) {
			return $this->form->getRecord()->ClassName;
		}
	}

	/**
	 * Return the record in which the CTF resides, if it exists.
	 */
	function getParentRecord() {
		if($this->form && $record = $this->form->getRecord()) {
			return $record;
		} else {
			$parentID = (int)$this->sourceID();
			$parentClass = $this->getParentClass();
			
			if($parentClass) {
				if($parentID) return DataObject::get_by_id($parentClass, $parentID);
				else return singleton($parentClass);
			}
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
	function getParentIdName($parentClass, $childClass) {
		return $this->getParentIdNameRelation($childClass, $parentClass, 'has_one');
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
	 * Note: constructed resolve ambiguous cases in the same manner as
	 * DataObject::getComponentJoinField()
	 */
	function getParentIdNameRelation($parentClass, $childClass, $relation) {
		if($this->parentIdName) return $this->parentIdName;
		
		$relations = array_flip(singleton($parentClass)->$relation());
		
		$classes = array_reverse(ClassInfo::ancestry($childClass));
		foreach($classes as $class) {
			if(isset($relations[$class])) return $relations[$class] . 'ID';
		}
		return false;
	}

	function setTemplatePopup($template) {
		$this->templatePopup = $template;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Return the object-specific fields for the given record, to be shown in the detail pop-up
	 * 
	 * This won't include all the CTF-specific 'plumbing; this method is called by self::getFieldsFor()
	 * and the result is then processed further to get the actual FieldSet for the form.
	 *
	 * The default implementation of this processes the value of $this->detailFormFields; consequently, if you want to 
	 * set the value of the fields to something that $this->detailFormFields doesn't allow, you can do so by overloading
	 * this method.
	 */
	function getCustomFieldsFor($childData) {
		if($this->detailFormFields instanceof FieldSet) {
			return $this->detailFormFields;
		}
		
		$fieldsMethod = $this->detailFormFields;

		if(!is_string($fieldsMethod)) {
			$this->detailFormFields = 'getCMSFields';
			$fieldsMethod = 'getCMSFields';
		}
		
		if(!$childData->hasMethod($fieldsMethod)) {
			$fieldsMethod = 'getCMSFields';
		}
		
		return $childData->$fieldsMethod();
	}
		
	function getFieldsFor($childData) {
		$hasManyRelationName = null;
		$manyManyRelationName = null;
	
		// See if our parent class has any many_many relations by this source class
		if($parentClass = $this->getParentRecord()) {
			$manyManyRelations = $parentClass->many_many();
			$manyManyRelationName = null;
			$manyManyComponentSet = null;

			$hasManyRelations = $parentClass->has_many();
			$hasManyRelationName = null;
			$hasManyComponentSet = null;

			if($manyManyRelations) foreach($manyManyRelations as $relation => $class) {
				if($class == $this->sourceClass()) {
					$manyManyRelationName = $relation;
				}
			}

			if($hasManyRelations) foreach($hasManyRelations as $relation => $class) {
				if($class == $this->sourceClass()) {
					$hasManyRelationName = $relation;
				}
			}
		}
		
		// Add the relation value to related records
		if(!$childData->ID && $this->getParentClass()) {
			// make sure the relation-link is existing, even if we just add the sourceClass and didn't save it
			$parentIDName = $this->getParentIdName($this->getParentClass(), $this->sourceClass());
			$childData->$parentIDName = $this->sourceID();
		}
		
		$detailFields = $this->getCustomFieldsFor($childData);

		if($this->getParentClass() && $hasManyRelationName && $childData->ID) {
			$hasManyComponentSet = $parentClass->getComponents($hasManyRelationName);
		}

		// the ID field confuses the Controller-logic in finding the right view for ReferencedField
		$detailFields->removeByName('ID');
		
		// only add childID if we're not adding a record		
		if($childData->ID) {
			$detailFields->push(new HiddenField('ctf[childID]', '', $childData->ID));
		}
		
		// add a namespaced ID instead thats "converted" by saveComplexTableField()
		$detailFields->push(new HiddenField('ctf[ClassName]', '', $this->sourceClass()));

		if($this->getParentClass()) {
			$detailFields->push(new HiddenField('ctf[parentClass]', '', $this->getParentClass()));

			if($manyManyRelationName && $this->relationAutoSetting) {
				$detailFields->push(new HiddenField('ctf[manyManyRelation]', '', $manyManyRelationName));
			}
			
			if($hasManyRelationName && $this->relationAutoSetting) {
				$detailFields->push(new HiddenField('ctf[hasManyRelation]', '', $hasManyRelationName));
			}
			
			if($manyManyRelationName || $hasManyRelationName) {
				$detailFields->push(new HiddenField('ctf[sourceID]', '', $this->sourceID()));
			}
			
			$parentIdName = $this->getParentIdName($this->getParentClass(), $this->sourceClass());
			
			if($parentIdName) {
				if($this->relationAutoSetting) {
					// Hack for model admin: model admin will have included a dropdown for the relation itself
					$detailFields->removeByName($parentIdName);
					$detailFields->push(new HiddenField($parentIdName, '', $this->sourceID()));
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

		$form = new $this->popupClass(
			$this,
			'AddForm',
			$fields,
			$validator,
			false,
			$childData
		);

		$form->loadDataFrom($childData);

		return $form;
	}
	
	/**
	 * By default, a ComplexTableField will assume that the field name is the name of a has-many relation on the object being
	 * edited.  It will identify the foreign key in the object being listed, and filter on that column, as well as auto-setting
	 * that column for newly created records.
	 * 
	 * Calling $this->setRelationAutoSetting(false) will disable this functionality.
	 *
	 * @param boolean $value Should the relation auto-setting functionality be enabled?
	 */
	function setRelationAutoSetting($value) {
		$this->relationAutoSetting = $value;
	}
	
	/**
	 * Use the URL-Parameter "action_saveComplexTableField"
	 * to provide a clue to the main controller if the main form has to be rendered,
	 * even if there is no action relevant for the main controller (to provide the instance of ComplexTableField
	 * which in turn saves the record.
	 *
	 * This is for adding new item records. {@link ComplexTableField_ItemRequest::saveComplexTableField()}
	 *
	 * @see Form::ReferencedField
	 */
	function saveComplexTableField($data, $form, $params) {
		$className = $this->sourceClass();
		$childData = new $className();
		$form->saveInto($childData);

		try {
			$childData->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}

		// Save the many many relationship if it's available
		if(isset($data['ctf']['manyManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['manyManyRelation'];
			$componentSet = $parentRecord ? $parentRecord->getManyManyComponents($relationName) : null;
			if($componentSet) $componentSet->add($childData);
		}
		
		if(isset($data['ctf']['hasManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['hasManyRelation'];
			
			$componentSet = $parentRecord ? $parentRecord->getComponents($relationName) : null;
			if($componentSet) $componentSet->add($childData);
		}
		
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		
		$closeLink = sprintf(
			'<small><a href="%s" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			$referrer,
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		
		$editLink = Controller::join_links($this->Link(), 'item/' . $childData->ID . '/edit');
		
		$message = sprintf(
			_t('ComplexTableField.SUCCESSADD', 'Added %s %s %s'),
			$childData->singular_name(),
			'<a href="' . $editLink . '">' . $childData->Title . '</a>',
			$closeLink
		);
		
		$form->sessionMessage($message, 'good');

		Director::redirectBack();
	}
}

/**
 * @todo Tie this into ComplexTableField_Item better.
 * @package forms
 * @subpackage fields-relational
 */
class ComplexTableField_ItemRequest extends TableListField_ItemRequest {
	protected $ctf;
	protected $itemID;
	protected $methodName;
	
	static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);
	
	function Link($action = null) {
		return Controller::join_links($this->ctf->Link(), '/item/', $this->itemID, $action);
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
		return $this->renderWith($this->ctf->templatePopup);
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

		return $this->renderWith($this->ctf->templatePopup);
	}

	function delete($request) {
		// Protect against CSRF on destructive action
		$token = $this->ctf->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError(400);
		
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
 			return DataObject::get_by_id(ClassInfo::baseDataClass(Object::getCustomClass($this->ctf->sourceClass())), $this->itemID); 
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

		$form = new $this->ctf->popupClass(
			$this,
			"DetailForm", 
			$fields,
			$validator,
			$readonly,
			$childData
		);
	
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
	 * This is for editing existing item records. {@link ComplexTableField::saveComplexTableField()}
	 *
	 * @see Form::ReferencedField
	 */
	function saveComplexTableField($data, $form, $request) {
		$dataObject = $this->dataObj();

		try {
			$form->saveInto($dataObject);
			$dataObject->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}
		
		// Save the many many relationship if it's available
		if(isset($data['ctf']['manyManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['manyManyRelation'];
			$componentSet = $parentRecord->getManyManyComponents($relationName);
			$componentSet->add($dataObject);
		}
		
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		
		$closeLink = sprintf(
			'<small><a href="%s" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			$referrer,
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		$message = sprintf(
			_t('ComplexTableField.SUCCESSEDIT', 'Saved %s %s %s'),
			$dataObject->singular_name(),
			'<a href="' . $this->Link() . '">"' . htmlspecialchars($dataObject->Title, ENT_QUOTES) . '"</a>',
			$closeLink
		);
		
		$form->sessionMessage($message, 'good');

		Director::redirectBack();
	}
	
	function PopupCurrentItem() {
		return $_REQUEST['ctf']['start']+1;
	}
	
	function PopupFirstLink() {
		$this->ctf->LinkToItem();
		
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == 0) {
			return null;
		}

		$start = 0;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
	}

	function PopupLastLink() {
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == $this->totalCount-1) {
			return null;
		}
		
		$start = $this->totalCount - 1;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
	}

	function PopupNextLink() {
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == $this->totalCount-1) {
			return null;
		}

		$start = $_REQUEST['ctf']['start'] + 1;
		return Controller::join_links($this->Link(), "$this->methodName?ctf[start]={$start}");
	}

	function PopupPrevLink() {
		if(!isset($_REQUEST['ctf']['start']) || !is_numeric($_REQUEST['ctf']['start']) || $_REQUEST['ctf']['start'] == 0) {
			return null;
		}

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
		$parents = isset($_ALL_CLASSES['parents'][$this->class]) ? $_ALL_CLASSES['parents'][$this->class] : null;
		
		if($parents) {
			foreach($parents as $parent) {
				if(!in_array($parent, $_ALL_CLASSES['parents']['TableListField'])) {
					$items[] = $parent . '_Popup';
				}
			}
		}
		
		$items[] = $this->class . '_Popup';

		return implode(' ', $items);
	}


	/**
	 * Returns the db-fieldname of the currently used has_one-relationship.
	 */
	function getParentIdName($parentClass, $childClass) {
		return $this->getParentIdNameRelation($childClass, $parentClass, 'has_one');
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
		if($relations) {
			foreach($relations as $k => $v) {
				if(array_key_exists($v, $classes)) return $k . 'ID';
			}
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
	function Link($action = null) {
		return Controller::join_links($this->parent->Link(), '/item/', $this->item->ID, $action);
	}

	function EditLink() {
		return Controller::join_links($this->Link(), "edit");
	}

	function ShowLink() {
		return Controller::join_links($this->Link(), "show");
	}

	function DeleteLink() {
		return Controller::join_links($this->Link(), "delete");
	}
	
	/**
	 * @param String $action
	 * @return boolean
	 */
	function IsDefaultAction($action) {
		return ($action == $this->parent->defaultAction);
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
		
		Requirements::clear();
		Requirements::unblock_all();
		
		$actions = new FieldSet();	
		if(!$readonly) {
			$actions->push(
				$saveAction = new FormAction(
					"saveComplexTableField", 
					_t('CMSMain.SAVE', 'Save')
				)
			);	
			$saveAction->addExtraClass('save');
		}
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
		
		if(!$this->dataObject->canEdit()) $this->makeReadonly();
	}

	function forTemplate() {
		$ret = parent::forTemplate();
		
		/**
		 * WARNING: DO NOT CHANGE THE ORDER OF THESE JS FILES
		 * Some have special requirements.
		 */
		Requirements::css(SAPPHIRE_DIR . '/css/Form.css');
		Requirements::css(SAPPHIRE_DIR . '/css/ComplexTableField_popup.css');
		Requirements::css(CMS_DIR . '/css/typography.css');
		Requirements::css(CMS_DIR . '/css/cms_right.css');
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/scriptaculous.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/controls.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/layout_helpers.js");
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ComplexTableField_popup.js");

		// Append requirements from instance callbacks
		$parent = $this->getParentController();
		if($parent instanceof ComplexTableField) {
			$callback = $parent->requirementsForPopupCallback;
		} else {
			$callback = $parent->getParentController()->requirementsForPopupCallback;
		}
		if($callback) call_user_func($callback, $this);

		// Append requirements from DataObject
		// DEPRECATED 2.4 Use ComplexTableField->requirementsForPopupCallback
 		if($this->dataObject->hasMethod('getRequirementsForPopup')) {
			$this->dataObject->getRequirementsForPopup();
		}
		
		return $ret;
	}
	
	/**
	 * @return ComplexTableField_ItemRequest
	 */
	function getParentController() {
		return $this->controller;
	}
}

?>
