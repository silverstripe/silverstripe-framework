<?php
/**
 * @package sapphire
 * @subpackage control
 */
abstract class CollectionController extends Controller {

	public $parentController;

	/**
	 * @var string $modelClass Subclass of {@link DataObject} that should be processed.
	 * You can influence the selection of records through {@link getRecords()}.
	 */
	protected $modelClass;
	
	/**
	 * @var string|boolean $recordControllerClass Use a {@link RecordController} subclass
	 * to customize the detail viewing/editing behaviour.
	 */
	protected $recordControllerClass = 'RecordController';

	static $url_handlers = array(
		'' => 'index',
		'$Action' => 'handleActionOrID',
	);
	
	public static $page_size = 20;
	
	static $allowed_actions = array('index','search','add','AddForm','SearchForm','ResultsForm');

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

	/**
	 * Appends the model class to the URL.
	 *
	 * @return unknown
	 */
	function Link() {
		if($this->parentController) {
			return Controller::join_links($this->parentController->Link(), "$this->modelClass");
		} else {
			return Controller::join_links("$this->modelClass");
		}
	}
	
	/**
	 * Delegate to different control flow, depending on whether the
	 * URL parameter is a number (record id) or string (action).
	 * 
	 * @param unknown_type $request
	 * @return unknown
	 */
	function handleActionOrID($request) {
		if (is_numeric($request->param('Action'))) {
			return $this->handleID($request);
		} else {
			return $this->handleAction($request);
		}
	}
	
	/**
	 * Delegate to the RecordController if a valid numeric ID appears in the URL
	 * segment.
	 *
	 * @param HTTPRequest $request
	 * @return RecordController
	 */
	function handleID($request) {
		$class = $this->recordControllerClass;
		return new $class($this, $request);
	}

	/**
	 * Return the class name of the model being managed.
	 *
	 * @return unknown
	 */
	function getModelClass() {
		return $this->modelClass;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	function index($request) {
		return $this->render(array(
			'Results' => $this->getRecords()
		));
	}
	
	function getRecords($searchCriteria = array()) {
		$start = ($this->request->getVar('start')) ? (int)$this->request->getVar('start') : 0;
		$limit = $this->stat('page_size');
		
		$context = singleton($this->modelClass)->getDefaultSearchContext();
		$query = $context->getQuery($searchCriteria, null, array('start'=>$start,'limit'=>$limit));
		$records = $context->getResults($searchCriteria, null, array('start'=>$start,'limit'=>$limit));
		if($records) {
			$records->setPageLimits($start, $limit, $query->unlimitedRowCount());
		}
		
		return $records;
	}

	/**
	 * Get a search form for a single {@link DataObject} subclass.
	 * 
	 * @return Form
	 */
	public function SearchForm() {
		$context = singleton($this->modelClass)->getDefaultSearchContext();
		$fields = $context->getSearchFields();
		$form = new Form($this, "SearchForm",
			$fields,
			new FieldSet(
				new FormAction('search', _t('MemberTableField.SEARCH'))
			)
		);
		$form->setFormMethod('get');
		
		return $form;
	}

	/**
	 * Action to render a data object collection, using the model context to provide filters
	 * and paging.
	 * 
	 * @return string
	 */
	function search($data, $form, $request) {
		return $this->render(array(
			'Results' => $this->getRecords($form->getData()),
			'SearchForm' => $form
		));
	}

	/**
	 * Gets the search query generated on the SearchContext from
	 * {@link DataObject::getDefaultSearchContext()},
	 * and the current GET parameters on the request.
	 *
	 * @return SQLQuery
	 */
	function getSearchQuery($searchCriteria) {
		$context = singleton($this->modelClass)->getDefaultSearchContext();
		return $context->getQuery($searchCriteria);
	}


	/////////////////////////////////////////////////////////////////////////////////////////////////////////


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
			'Form' => $this->AddForm(),
			'SearchForm' => false
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
		
		/*
		$form->sessionMessage(
			_t('RecordController.SAVESUCCESS','Saved record'),
			'good'
		);
		*/

		if($this->canDetailView()) {
			Director::redirect(Controller::join_links($this->Link(), $model->ID , 'edit'));
		} else {
			Director::redirectBack();
		}
		
	}


	/////////////////////////////////////////////////////////////////////////////////////////////////////////

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
	 * Use this to control permissions or completely disable
	 * links to detail records.
	 * @return boolean (Default: true)
	 */
	public function canDetailView() {
		return true;
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