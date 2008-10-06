<?php
/**
 * @package sapphire
 * @subpackage control
 */
class RecordController extends Controller {

	protected $modelClass;
	
	protected $currentRecord;
	
	static $allowed_actions = array('add','edit', 'view', 'EditForm', 'ViewForm');
	
	static $url_handlers = array(
		'' => 'index',
		'add' => 'add',
		'AddForm' => 'AddForm',
		'$ID/$Action' => 'handleAction',
	);
	
	/**
	 * @param string $parentController
	 * @param string $modelClass
	 */
	function __construct($parentController = null, $modelClass = null) {
		if($parentController) $this->parentController = $parentController;
		if($modelClass) $this->modelClass = $modelClass;
		
		parent::__construct();
	}
	
	function init() {
		parent::init();

		Requirements::themedCSS('layout');
		Requirements::themedCSS('typography');
		Requirements::themedCSS('form');
	}
	
	function handleAction($request) {
		if(is_numeric($request->latestParam('ID'))) {
			$this->currentRecord = DataObject::get_by_id($this->modelClass, $request->latestParam('ID'));
		}
		
		return parent::handleAction($request);
	}
	
	/**
	 * Link fragment - appends the current record ID to the URL.
	 *
	 */
	function Link() {
		die("not implemented yet");
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
			'Form' => $this->EditForm()
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
			new FormAction("doSave", "Save")
		);
		
		if($this->currentRecord->canDelete(Member::currentUser())) {
			$actions->push($deleteAction = new FormAction('doDelete', 'Delete'));
			$deleteAction->addExtraClass('delete');
		}
		
		$form = new Form($this, "EditForm", $fields, $actions, $validator);
		$form->loadDataFrom($this->currentRecord);

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
	function doSave($data, $form, $request) {
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
		if(!$this->currentRecord->canDelete(Member::currentUser())) {
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
	 * Create a new model record.
	 *
	 * @param unknown_type $request
	 * @return unknown
	 */
	function add($request) {
		if(!singleton($this->modelClass)->canCreate(Member::currentUser())) {
			return $this->httpError(403);
		}
		
		return $this->render(array(
			'Form' => $this->AddForm()
		));
	}

	/**
	 * Returns a form for editing the attached model
	 */
	public function AddForm() {
		$newRecord = new $this->modelClass();
		if($newRecord->hasMethod('getAddFormFields')) {
			$fields = $newRecord->getAddFormFields();
		} else {
			$fields = $newRecord->getFormFields();
		}

		$validator = ($newRecord->hasMethod('getValidator')) ? $newRecord->getValidator() : null;

		$actions = new FieldSet(new FormAction("doAdd", "Add"));

		$form = new Form($this, "AddForm", $fields, $actions, $validator);

		return $form;
	}	

	function doAdd($data, $form, $request) {
		if(!singleton($this->modelClass)->canCreate(Member::currentUser())) {
			return $this->httpError(403);
		}
		
		$className = $this->modelClass;
		$model = new $className();
		// We write before saveInto, since this will let us save has-many and many-many relationships :-)
		$model->write();
		$form->saveInto($model);
		$model->write();

		Director::redirect(Controller::join_links($this->Link(), $model->ID , 'edit'));
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
}
?>