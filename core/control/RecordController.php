<?php
/**
 * @package sapphire
 * @subpackage control
 */
class RecordController extends Controller {
	protected $parentController;
	protected $currentRecord;
	
	static $allowed_actions = array('edit','view','delete','EditForm','ViewForm','DeleteForm');
	
	function __construct($parentController, $request) {
		$this->parentController = $parentController;
		$modelName = $parentController->getModelClass();
		
		if(is_numeric($request->latestParam('Action'))) {
			$this->currentRecord = DataObject::get_by_id($this->modelClass, $request->latestParam('Action'));
		}
		
		parent::__construct();
	}
	
	function init() {
		parent::init();

		Requirements::themedCSS('layout');
		Requirements::themedCSS('typography');
		Requirements::themedCSS('form');
	}
	
	/**
	 * Link fragment - appends the current record ID to the URL.
	 *
	 */
	function Link() {
		return Controller::join_links($this->parentController->Link(), "/{$this->currentRecord->ID}");
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function index($request) {
		return $this->view($request);
	}
	
	/**
	 * Edit action - shows a form for editing this record
	 */
	function edit($request) {
		if(!$this->currentRecord) {
			return $this->httpError(404);
		}
		if(!$this->currentRecord->canEdit(Member::currentUser())) {
			return $this->httpError(403);
		}
		
		return $this->render(array(
			'Form' => $this->EditForm(),
			'ExtraForm' => $this->DeleteForm()
		));
	}

	/**
	 * Returns a form for editing the attached model
	 */
	public function EditForm() {
		$fields = $this->currentRecord->getFormFields();
		$fields->push(new HiddenField("ID"));
		
		$validator = ($this->currentRecord->hasMethod('getValidator')) ? $this->currentRecord->getValidator() : null;
		
		$actions = new FieldSet(
			new FormAction("doEdit", "Save")
		);
		
		$form = new Form($this, "EditForm", $fields, $actions, $validator);
		$form->loadDataFrom($this->currentRecord);

		return $form;
	}
	
	public function DeleteForm() {
		if(!$this->currentRecord->canDelete(Member::currentUser())) {
			return false;
		}
		
		$form = new Form($this, 
			"DeleteForm", 
			new FieldSet(), 
			new FieldSet(new ConfirmedFormAction('doDelete', 'Delete')) 
		);
		
		return $form;
	}

	/**
	 * Postback action to save a record
	 *
	 * @param array $data
	 * @param Form $form
	 * @param HTTPRequest $request
	 * @return mixed
	 */
	function doEdit($data, $form, $request) {
		if(!$this->currentRecord->canEdit(Member::currentUser())) {
			return $this->httpError(403);
		}
		
		$form->saveInto($this->currentRecord);
		$this->currentRecord->write();
		
		$form->sessionMessage(
			_t('RecordController.SAVESUCCESS','Saved record'),
			'good'
		);
		
		Director::redirectBack();
	}	
	
	/**
	 * Delete the current record
	 */
	public function doDelete($data, $form, $request) {
		if(!$this->currentRecord->canDelete(Member::currentUser())) {
			return $this->httpError(403);
		}
			
		$this->currentRecord->delete();
		$form->sessionMessage(
			_t('RecordController.DELETESUCCESS','Successfully deleted record'), 
			'good'
		);
		
		Director::redirectBack();
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renders the record view template.
	 * 
	 * @param HTTPRequest $request
	 * @return mixed
	 */
	function view($request) {
		if(!$this->currentRecord) {
			return $this->httpError(404);
		}
		if(!$this->currentRecord->canView(Member::currentUser())) {
			return $this->httpError(403);
		}

		return $this->render(array(
			'Form' => $this->ViewForm()
		));
	}

	/**
	 * Returns a form for viewing the attached model
	 * 
	 * @return Form
	 */
	public function ViewForm() {
		$fields = $this->currentRecord->getFormFields();
		$form = new Form($this, "EditForm", $fields, new FieldSet());
		$form->loadDataFrom($this->currentRecord);
		$form->makeReadonly();
		return $form;
	}
	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * @return string
	 */
	public function ModelNameSingular() {
		return singleton($this->modelClass)->i18n_singular_name();
	}
	
	/**
	 * @return string
	 */
	public function ModelNamePlural() {
		return singleton($this->modelClass)->i18n_plural_name();
	}
	
	/**
	 * If a parentcontroller exists, use its main template,
	 * and mix in specific collectioncontroller subtemplates.
	 */
	function getViewer($action) {
		if($this->parentController) {
			$viewer = $this->parentController->getViewer($action);
			$parentClass = $this->class;
			$layoutTemplate = null;
			while($parentClass != "Controller" && !$layoutTemplate) {
				$templates[] = strtok($parentClass,'_') . '_' . $action;
				$parentClass = get_parent_class($parentClass);
				$layoutTemplate = SSViewer::getTemplateFileByType($parentClass, 'Layout');
			}

			if($layoutTemplate)	$viewer->setTemplateFile('Layout', $layoutTemplate);

			return $viewer;
		} else {
			return parent::getViewer($action);
		}
	}
}
?>