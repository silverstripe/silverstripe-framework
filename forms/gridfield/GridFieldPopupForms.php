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
	
	function getURLHandlers($gridField) {
		return array(
			'item/$ID' => 'handleItem',
		);
	}
	
	function handleItem($gridField, $request) {
		$record = $gridField->getList()->byId($request->param("ID"));
		$handler = new GridFieldPopupForm_ItemRequest($gridField, $this, $record);
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
	
	protected $gridField;
	
	protected $component;
	
	protected $record;

	/**
	 * @var String
	 */
	protected $template = 'GridFieldItemEditView';

	static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);
	
	function __construct($gridField, $component, $record) {
		$this->gridField = $gridField;
		$this->component = $gridField;
		$this->record = $record;
		
		parent::__construct();
	}

	function Link($action = null) {
		return Controller::join_links($this->gridField->Link('item'), $this->record->ID, $action);
	}
	
	function edit($request) {
		$controller = $this->gridField->getForm()->Controller();

		$return = $this->customise(array(
				'Backlink' => $controller->Link(),
				'ItemEditForm' => $this->ItemEditForm($this->gridField, $request),
			))->renderWith($this->template);

		if($controller->isAjax()) {
			return $return;	
		} else {
			// If not requested by ajax, we need to render it within the controller context+template
			return $controller->customise(array(
				$this->gridField->getForm()->Name() => $return,
			));	
		}
	}

	function ItemEditForm() {
		$request = $this->gridField->getForm()->Controller()->getRequest();
		$form = new Form(
			$this,
			'ItemEditForm',
			$this->record->getCMSFields(),
			new FieldList(
				$saveAction = new FormAction('doSave', _t('GridFieldDetailsForm.Save', 'Save'))
			)
		);
		$saveAction->addExtraClass('ss-ui-action-constructive');
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

		return $this->gridField->getForm()->Controller()->redirectBack();
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