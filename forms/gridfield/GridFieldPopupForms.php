<?php

/**
 * Provides view and edit forms at GridField-specific URLs.  
 * These can be placed into pop-ups by an appropriate front-end.
 * Usually added to a grid field alongside of {@link GridFieldAction_Edit}
 * which takes care of linking the individual rows to their edit view.
 * 
 * The URLs provided will be off the following form:
 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>
 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>/edit
 */
class GridFieldPopupForms implements GridField_URLHandler {



	/**
	 * @var String
	 */
	protected $template = 'GridFieldPopupForms';

	/**
	 *
	 * @var string
	 */
	protected $name;

	function getURLHandlers($gridField) {
		return array(
			'item/$ID' => 'handleItem',
			'autocomplete' => 'handleAutocomplete',
		);
	}
	
	/**
	 * Create a popup component. The two arguments will specify how the popup form's HTML and
	 * behaviour is created.  The given controller will be customised, putting the edit form into the
	 * template with the given name.
	 *
	 * The arguments are experimental API's to support partial content to be passed back to whatever
	 * controller who wants to display the getCMSFields
	 * 
	 * @param string $name The name of the edit form to place into the pop-up form
	 */
	public function __construct($name = 'DetailForm') {
		$this->name = $name;
	}
	
	/**
	 *
	 * @param type $gridField
	 * @param type $request
	 * @return GridFieldPopupForm_ItemRequest 
	 */
	public function handleItem($gridField, $request) {
		$controller = $gridField->getForm()->Controller();

		if(is_numeric($request->param('ID'))) {
			$record = $gridField->getList()->byId($request->param("ID"));
		} else {
			$record = Object::create($gridField->getModelClass());	
		}

		if(!$class = ClassInfo::exists(get_class($this) . "_ItemRequest")) {
			$class = 'GridFieldPopupForm_ItemRequest';
		}

		$handler = Object::create($class, $gridField, $this, $record, $controller, $this->name);
		$handler->setTemplate($this->template);

		return $handler->handleRequest($request, $gridField);
	}

	/**
	 * @param String
	 */
	function setTemplate($template) {
		$this->template = $template;
	}

	/**
	 * @return String
	 */
	function getTemplate() {
		return $this->template;
	}

	/**
	 * @param String
	 */
	function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return String
	 */
	function getName() {
		return $this->name;
	}
}

class GridFieldPopupForm_ItemRequest extends RequestHandler {
	
	/**
	 *
	 * @var GridField 
	 */
	protected $gridField;
	
	/**
	 *
	 * @var GridField_URLHandler
	 */
	protected $component;
	
	/**
	 *
	 * @var DataObject
	 */
	protected $record;

	/**
	 *
	 * @var Controller
	 */
	protected $popupController;
	
	/**
	 *
	 * @var string
	 */
	protected $popupFormName;
	
	/**
	 * @var String
	 */
	protected $template = 'GridFieldItemEditView';

	static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'edit',
	);
	
	/**
	 *
	 * @param GridFIeld $gridField
	 * @param GridField_URLHandler $component
	 * @param DataObject $record
	 * @param Controller $popupController
	 * @param string $popupFormName 
	 */
	public function __construct($gridField, $component, $record, $popupController, $popupFormName) {
		$this->gridField = $gridField;
		$this->component = $gridField;
		$this->record = $record;
		$this->popupController = $popupController;
		$this->popupFormName = $popupFormName;
		parent::__construct();
	}

	public function Link($action = null) {
		return Controller::join_links($this->gridField->Link('item'), $this->record->ID ? $this->record->ID : 'new', $action);
	}
	
	function edit($request) {
		$controller = $this->popupController;
		$form = $this->ItemEditForm($this->gridField, $request);

		// TODO Coupling with CMS
		if($controller instanceof LeftAndMain) {
			$form->addExtraClass('cms-edit-form');
			$form->setTemplate($controller->getTemplatesWithSuffix('_EditForm'));
			$form->addExtraClass('cms-content center ss-tabset ' . $controller->BaseCSSClasses());
			if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
			// TODO Link back to controller action (and edited root record) rather than index,
			// which requires more URL knowledge than the current link to this field gives us.
			// The current root record is held in session only, 
			// e.g. page/edit/show/6/ vs. page/edit/EditForm/field/MyGridField/....
			$form->Backlink = $controller->Link();
		}

		$return = $this->customise(array(
				'Backlink' => $controller->Link(),
				'ItemEditForm' => $form,
			))->renderWith($this->template);

		if($controller->isAjax()) {
			return $return;	
		} else {
			
			// If not requested by ajax, we need to render it within the controller context+template
			return $controller->customise(array(
				// TODO Allow customization
				'Content' => $return,
			));	
		}
	}

	/**
	 * Builds an item edit form.  The arguments to getCMSFields() are the popupController and
	 * popupFormName, however this is an experimental API and may change.
	 * 
	 * @todo In the future, we will probably need to come up with a tigher object representing a partially
	 * complete controller with gaps for extra functionality.  This, for example, would be a better way
	 * of letting Security/login put its log-in form inside a UI specified elsewhere.
	 * 
	 * @return Form 
	 */
	function ItemEditForm() {
		$form = new Form(
			$this,
			'ItemEditForm',
			// WARNING: The arguments passed here are a little arbitrary.  This API will need cleanup
			$this->record->getCMSFields($this->popupController, $this->popupFormName),
			new FieldList(
				FormAction::create('doSave', _t('GridFieldDetailsForm.Save', 'Save'))
					->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept'),
				FormAction::create('doDelete', _t('GridFieldDetailsForm.Delete', 'Delete'))
					->setUseButtonTag(true)->addExtraClass('ss-ui-action-destructive')
			)
		);
		$form->loadDataFrom($this->record);
		return $form;
	}

	function doSave($data, $form) {
		$new_record = $this->record->ID == 0;

		try {
			$form->saveInto($this->record);
			$this->record->write();
			if($new_record)
				$this->gridField->getList()->add($this->record);
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Controller::curr()->redirectBack();
		}

		// TODO Save this item into the given relationship

		$message = sprintf(
			_t('ComplexTableField.SUCCESSEDIT2', 'Saved %s %s'),
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);
		
		$form->sessionMessage($message, 'good');

		return $this->popupController->redirectBack();
	}

	function doDelete($data, $form) {
		try {
			$toDelete = $this->record;
			if (!$toDelete->canDelete()) {
				throw new ValidationException(_t('GridFieldDetailsForm.DeletePermissionsFailure',"No delete permissions"),0);
			}

			$toDelete->delete();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}

		$message = sprintf(
			_t('ComplexTableField.SUCCESSEDIT2', 'Deleted %s %s'),
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);

		$form->sessionMessage($message, 'good');

		//when an item is deleted, redirect to the revelant admin section without the action parameter
		$controller = Controller::curr();
		$noActionURL = $controller->removeAction($data['url']);

		return Director::redirect($noActionURL, 302); //redirect back to admin section
	}

	/**
	 * @param String
	 */
	function setTemplate($template) {
		$this->template = $template;
	}

	/**
	 * @return String
	 */
	function getTemplate() {
		return $this->template;
	}

	/**
	 * CMS-specific functionality: Passes through navigation breadcrumbs
	 * to the template, and includes the currently edited record (if any).
	 * see {@link LeftAndMain->Breadcrumbs()} for details.
	 * 
	 * @param boolean $unlinked 
	 * @return ArrayData
	 */
	function Breadcrumbs($unlinked = false) {
		if(!($this->popupController instanceof LeftAndMain)) return false;

		$items = $this->popupController->Breadcrumbs($unlinked);
		if($this->record) {
			$items->push(new ArrayData(array(
				'Title' => $this->record->Title,
				'Link' => false
			)));	
		}
		
		return $items;
	}
}