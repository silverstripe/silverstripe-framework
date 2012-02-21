<?php

/**
 * Provides view and edit forms at GridField-specific URLs.  These can be placed into pop-ups by an appropriate front-end.
 * 
 * The URLs provided will be off the following form:
 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>
 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>/edit
 */
class GridFieldPopupForms implements GridField_URLHandler {

	/**
	 * @var String
	 */
	protected $template = 'GridFieldItemEditView';

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
	 * @param Controller $popupController The controller object that will be used to render the pop-up forms
	 * @param string $popupFormName The name of the edit form to place into the pop-up form
	 */
	public function __construct($popupController, $popupFormName) {
		$this->popupController = $popupController;
		$this->popupFormName = $popupFormName;
	}
	
	/**
	 *
	 * @param type $gridField
	 * @param type $request
	 * @return GridFieldPopupForm_ItemRequest 
	 */
	public function handleItem($gridField, $request) {
		$record = $gridField->getList()->byId($request->param("ID"));
		$handler = new GridFieldPopupForm_ItemRequest($gridField, $this, $record, $this->popupController, $this->popupFormName);
		$handler->setTemplate($this->template);
		return $handler;
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
		return Controller::join_links($this->gridField->Link('item'), $this->record->ID, $action);
	}
	
	function edit($request) {
		$controller = $this->popupController;
		
		$return = $this->customise(array(
				'Backlink' => $this->gridField->getForm()->Controller()->Link(),
				'ItemEditForm' => $this->ItemEditForm($this->gridField, $request),
			))->renderWith($this->template);

		if($controller->isAjax()) {
			return $return;	
		} else {
			
			// If not requested by ajax, we need to render it within the controller context+template
			return $controller->customise(array(
				$this->popupFormName => $return,
			));	
		}
	}

	/**
	 * Builds an item edit form.  The arguments to getCMSFields() are the popupController and
	 * popupFormName, however this is an experimental API and may change.
	 * 
	 * In the future, we will probably need to come up with a tigher object representing a partially
	 * complete controller with gaps for extra functionality.  This, for example, would be a better way
	 * of letting Security/login put its log-in form inside a UI specified elsewhere.
	 * 
	 * @return Form 
	 */
	function ItemEditForm() {
		$request = $this->popupController->getRequest();
		$form = new Form(
			$this,
			'ItemEditForm',
			// WARNING: The arguments passed here are a little arbitrary.  This API will need cleanup
			$this->record->getCMSFields($this->popupController, $this->popupFormName),
			new FieldList(
				$saveAction = new FormAction('doSave', _t('GridFieldDetailsForm.Save', 'Save'))
			)
		);
		$saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
		$form->loadDataFrom($this->record);
		return $form;
	}

	function doSave($data, $form) {
		try {
			$form->saveInto($this->record);
			$this->record->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
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
}