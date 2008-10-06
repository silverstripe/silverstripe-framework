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
		'$Action' => 'handleAction',
	);
	
	static $page_size = 20;
	
	static $allowed_actions = array('index','search', 'SearchForm', 'ResultsForm');

	/**
	 * @param string $parentController
	 * @param string $modelClass
	 */
	function __construct($parentController = null, $modelClass = null) {
		if($parentController) $this->parentController = $parent;
		if($modelClass) $this->modelClass = $modelClass;
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
		die("not implemented yet");
	}

	/**
	 * Return the class name of the model being managed.
	 *
	 * @return unknown
	 */
	function getModelClass() {
		return $this->modelClass;
	}

	/**
	 * Delegate to the {@link RecordController} if a valid numeric ID appears in the URL
	 * segment.
	 *
	 * @param HTTPRequest $request
	 * @return RecordController
	 */
	function record($request) {
		if(!$this->recordControllerClass) return $this->httpError(403);
		
		$class = $this->recordControllerClass;
		return new $class($this, $this->modelClass);
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
}
?>