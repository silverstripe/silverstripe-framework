<?php
/**
 * Generates a three-pane UI for editing model classes,
 * with an automatically generated search panel, tabular results
 * and edit forms.
 * Relies on data such as {@link DataObject::$db} and {@DataObject::getCMSFields()}
 * to scaffold interfaces "out of the box", while at the same time providing
 * flexibility to customize the default output.
 * 
 * Add a route (note - this doc is not currently in sync with the code, need to update)
 * <code>
 * Director::addRules(50, array('admin/mymodel/$Class/$Action/$ID' => 'MyModelAdmin'));
 * </code>
 *
 * @todo saving logic (should mostly use Form->saveInto() and iterate over relations)
 * @todo ajax form loading and saving
 * @todo ajax result display
 * @todo relation formfield scaffolding (one tab per relation) - relations don't have DBField sublclasses, we do
 * 	we define the scaffold defaults. can be ComplexTableField instances for a start. 
 * @todo has_many/many_many relation autocomplete field (HasManyComplexTableField doesn't work well with larger datasets)
 * 
 * Long term TODOs:
 * @todo Hook into RESTful interface on DataObjects (yet to be developed)
 * @todo Permission control via datamodel and Form class
 * 
 * @uses SearchContext
 * 
 * @package cms
 * @subpackage core
 */
abstract class ModelAdmin extends LeftAndMain {

	static $url_rule = '/$Action';	
	
	/**
	 * List of all managed {@link DataObject}s in this interface.
	 *
	 * Simple notation with class names only:
	 * <code>
	 * array('MyObjectClass','MyOtherObjectClass')
	 * </code>
	 * 
	 * Extended notation with options (e.g. custom titles):
	 * <code>
	 * array(
	 *   'MyObjectClass' => array('title' => "Custom title")
	 * )
	 * </code>
	 * 
	 * Available options:
	 * - 'title': Set custom titles for the tabs or dropdown names
	 * - 'collection_controller': Set a custom class to use as a collection controller for this model
	 * - 'record_controller': Set a custom class to use as a record controller for this model
	 *
	 * @var array|string
	 */
	public static $managed_models = null;
	
	/**
	 * More actions are dynamically added in {@link defineMethods()} below.
	 */
	public static $allowed_actions = array(
		'add',
		'edit',
		'delete',
		'import',
		'renderimportform',
		'handleList',
		'handleItem',
		'ImportForm'
	);
	
	/**
	 * @param string $collection_controller_class Override for controller class
	 */
	public static $collection_controller_class = "ModelAdmin_CollectionController";
	
	/**
	 * @param string $collection_controller_class Override for controller class
	 */
	public static $record_controller_class = "ModelAdmin_RecordController";
	
	/**
	 * Forward control to the default action handler
	 */
	public static $url_handlers = array(
		'$Action' => 'handleAction'
	);
	
	/**
	 * Model object currently in manipulation queue. Used for updating Link to point
	 * to the correct generic data object in generated URLs.
	 *
	 * @var string
	 */
	private $currentModel = false;
	
	/**
	 * Change this variable if you don't want the Import from CSV form to appear. 
	 * This variable can be a boolean or an array.
	 * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo') 
	 */
	public $showImportForm = true;
		
	/**
	 * List of all {@link DataObject}s which can be imported through
	 * a subclass of {@link BulkLoader} (mostly CSV data).
	 * By default {@link CsvBulkLoader} is used, assuming a standard mapping
	 * of column names to {@link DataObject} properties/relations.
	 * 
	 * e.g. "BlogEntry" => "BlogEntryCsvBulkLoader"
	 *
	 * @var array
	 */
	public static $model_importers = null;
	
	/**
	 * Amount of results showing on a single page.
	 *
	 * @var int
	 */
	public static $page_length = 30;
	
	/**
	 * Class name of the form field used for the results list.  Overloading this in subclasses
	 * can let you customise the results table field.
	 */
	protected $resultsTableClassName = 'TableListField';

	/**
	 * Return {@link $this->resultsTableClassName}
	 */
	public function resultsTableClassName() {
		return $this->resultsTableClassName;
	}
	
	/**
	 * Initialize the model admin interface. Sets up embedded jquery libraries and requisite plugins.
	 * 
	 * @todo remove reliance on urlParams
	 */
	public function init() {
		parent::init();
		
		// security check for valid models
		if(isset($this->urlParams['Action']) && !in_array($this->urlParams['Action'], $this->getManagedModels())) {
			//user_error('ModelAdmin::init(): Invalid Model class', E_USER_ERROR);
		}
		
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/ModelAdmin.css'); // standard layout formatting for management UI
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/silverstripe.tabs.css'); // follows the jQuery UI theme conventions
		
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-livequery/jquery.livequery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery/jquery_improvements.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/ModelAdmin.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/ModelAdmin.History.js');
	}
	
	/**
	 * overwrite the static page_length of the admin panel, 
	 * should be called in the project _config file.
	 */
	static function set_page_length($length){
		self::$page_length = $length;
	}
	
	/**
	 * Return the static page_length of the admin, default as 30
	 */
	static function get_page_length(){
		return self::$page_length;
	} 
	
	/**
	 * Return the class name of the collection controller
	 *
	 * @param string $model model name to get the controller for
	 * @return string the collection controller class
	 */
	function getCollectionControllerClass($model) {
		$models = $this->getManagedModels();
		
		if(isset($models[$model]['collection_controller'])) {
			$class = $models[$model]['collection_controller'];
		} else {
			$class = $this->stat('collection_controller_class');
		}
		
		return $class;
	}
	
	/**
	 * Return the class name of the record controller
	 *
	 * @param string $model model name to get the controller for
	 * @return string the record controller class
	 */
	function getRecordControllerClass($model) {
		$models = $this->getManagedModels();
		
		if(isset($models[$model]['record_controller'])) {
			$class = $models[$model]['record_controller'];
		} else {
			$class = $this->stat('record_controller_class');
		}
		
		return $class;
	}
	
	/**
	 * Add mappings for generic form constructors to automatically delegate to a scaffolded form object.
	 */
	function defineMethods() {
		parent::defineMethods();
		foreach($this->getManagedModels() as $class => $options) {
			if(is_numeric($class)) $class = $options;
			$this->addWrapperMethod($class, 'bindModelController');
			self::$allowed_actions[] = $class;
		}
	}
	
	/**
	 * Base scaffolding method for returning a generic model instance.
	 */
	public function bindModelController($model, $request = null) {
		$class = $this->getCollectionControllerClass($model);	
		return new $class($this, $model);
	}
	
	/**
	 * This method can be overloaded to specify the UI by which the search class is chosen.
	 *
	 * It can create a tab strip or a dropdown.  The dropdown is useful when there are a large number of classes.
	 * By default, it will show a tabs for 1-3 classes, and a dropdown for 4 or more classes.
	 *
	 * @return String: 'tabs' or 'dropdown'
	 */
	public function SearchClassSelector() {
		return sizeof($this->getManagedModels()) > 3 ? 'dropdown' : 'tabs';
	}
	
	/**
	 * Returns managed models' create, search, and import forms
	 * @uses SearchContext
	 * @uses SearchFilter
	 * @return DataObjectSet of forms 
	 */
	protected function getModelForms() {
		$models = $this->getManagedModels();
		$forms  = new DataObjectSet();
		
		foreach($models as $class => $options) { 
			if(is_numeric($class)) $class = $options;
			$forms->push(new ArrayData(array (
				'Title'     => (is_array($options) && isset($options['title'])) ? $options['title'] : singleton($class)->i18n_singular_name(),
				'ClassName' => $class,
				'Content'   => $this->$class()->getModelSidebar()
			)));
		}
		
		return $forms;
	}
	
	/**
	 * @return array
	 */
	function getManagedModels() {
		$models = $this->stat('managed_models');
		if(is_string($models)) {
			$models = array($models);
		}
		if(!count($models)) {
			user_error(
				'ModelAdmin::getManagedModels(): 
				You need to specify at least one DataObject subclass in public static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"', 
				E_USER_ERROR
			);
		}
		
		return $models;
	}
	
	/**
	 * Returns all importers defined in {@link self::$model_importers}.
	 * If none are defined, we fall back to {@link self::managed_models}
	 * with a default {@link CsvBulkLoader} class. In this case the column names of the first row
	 * in the CSV file are assumed to have direct mappings to properties on the object.
	 *
	 * @return array
	 */
	 function getModelImporters() {
		$importers = $this->stat('model_importers');

		// fallback to all defined models if not explicitly defined
		if(is_null($importers)) {
			$models = $this->getManagedModels();
			foreach($models as $modelName => $options) {
				if(is_numeric($modelName)) $modelName = $options;
				$importers[$modelName] = 'CsvBulkLoader';
			}
		}
		
		return $importers;
	}
	
}

/**
 * Handles a managed model class and provides default collection filtering behavior.
 *
 * @package cms
 * @subpackage core
 */
class ModelAdmin_CollectionController extends Controller {
	public $parentController;
	protected $modelClass;
	
	public $showImportForm = null;
	
	static $url_handlers = array(
		'$Action' => 'handleActionOrID'
	);

	function __construct($parent, $model) {
		$this->parentController = $parent;
		$this->modelClass = $model;
		
		parent::__construct();
	}
	
	/**
	 * Appends the model class to the URL.
	 *
	 * @param string $action
	 * @return string
	 */
	function Link($action = null) {
		return $this->parentController->Link(Controller::join_links($this->modelClass, $action));
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
	 * @param SS_HTTPRequest $request
	 * @return RecordController
	 */
	public function handleID($request) {
		$class = $this->parentController->getRecordControllerClass($this->getModelClass());
		return new $class($this, $request);
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * Get a combination of the Search, Import and Create forms that can be inserted into a {@link ModelAdmin} sidebar.
	 *
	 * @return string
	 */
	public function getModelSidebar() {
		return $this->renderWith('ModelSidebar');
	}
	
	/**
	 * Get a search form for a single {@link DataObject} subclass.
	 * 
	 * @return Form
	 */
	public function SearchForm() {
		$context = singleton($this->modelClass)->getDefaultSearchContext();
		$fields = $context->getSearchFields();
		$columnSelectionField = $this->ColumnSelectionField();
		$fields->push($columnSelectionField);
		$validator = new RequiredFields();
		$validator->setJavascriptValidationHandler('none');
		
		$form = new Form($this, "SearchForm",
			$fields,
			new FieldSet(
				new FormAction('search', _t('MemberTableField.SEARCH', 'Search')),
				$clearAction = new ResetFormAction('clearsearch', _t('ModelAdmin.CLEAR_SEARCH','Clear Search'))
			),
			$validator
		);
		//$form->setFormAction(Controller::join_links($this->Link(), "search"));
		$form->setFormMethod('get');
		$form->setHTMLID("Form_SearchForm_" . $this->modelClass);
		$form->disableSecurityToken();
		$clearAction->useButtonTag = true;
		$clearAction->addExtraClass('minorAction');

		return $form;
	}
	
	/**
	 * Create a form that consists of one button 
	 * that directs to a give model's Add form
	 */ 
	public function CreateForm() {
		$modelName = $this->modelClass;

		if($this->hasMethod('alternatePermissionCheck')) {
			if(!$this->alternatePermissionCheck()) return false;
		} else {
			if(!singleton($modelName)->canCreate(Member::currentUser())) return false;
		}
		
		$buttonLabel = sprintf(_t('ModelAdmin.CREATEBUTTON', "Create '%s'", PR_MEDIUM, "Create a new instance from a model class"), singleton($modelName)->i18n_singular_name());

		$form = new Form($this, "CreateForm",
						new FieldSet(),
						new FieldSet($createButton = new FormAction('add', $buttonLabel)),
						$validator = new RequiredFields()
				);
	
		$createButton->dontEscape = true;
		$validator->setJavascriptValidationHandler('none');
		$form->setHTMLID("Form_CreateForm_" . $this->modelClass);
		return $form;
	}
	
	/**
	 * Checks if a CSV import form should be generated by a className criteria or in general for ModelAdmin.
	 */
	function showImportForm() {
		if($this->showImportForm === null) return $this->parentController->showImportForm;
		else return $this->showImportForm;
	}
	
	/**
	 * Generate a CSV import form for a single {@link DataObject} subclass.
	 *
	 * @return Form
	 */
	public function ImportForm() {
		$modelName = $this->modelClass;
		// check if a import form should be generated
		if(!$this->showImportForm() || (is_array($this->showImportForm()) && !in_array($modelName,$this->showImportForm()))) return false;
		$importers = $this->parentController->getModelImporters();
		if(!$importers || !isset($importers[$modelName])) return false;
		
		if(!singleton($modelName)->canCreate(Member::currentUser())) return false;

		$fields = new FieldSet(
			new HiddenField('ClassName', _t('ModelAdmin.CLASSTYPE'), $modelName),
			new FileField('_CsvFile', false)
		);
		
		// get HTML specification for each import (column names etc.)
		$importerClass = $importers[$modelName];
		$importer = new $importerClass($modelName);
		$spec = $importer->getImportSpec();
		$specFields = new DataObjectSet();
		foreach($spec['fields'] as $name => $desc) {
			$specFields->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
		}
		$specRelations = new DataObjectSet();
		foreach($spec['relations'] as $name => $desc) {
			$specRelations->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
		}
		$specHTML = $this->customise(array(
			'ModelName' => Convert::raw2att($modelName),
			'Fields' => $specFields,
			'Relations' => $specRelations, 
		))->renderWith('ModelAdmin_ImportSpec');
		
		$fields->push(new LiteralField("SpecFor{$modelName}", $specHTML));
		$fields->push(new CheckboxField('EmptyBeforeImport', 'Clear Database before import', false)); 
		
		$actions = new FieldSet(
			new FormAction('import', _t('ModelAdmin.IMPORT', 'Import from CSV'))
		);
		
		$validator = new RequiredFields();
		$validator->setJavascriptValidationHandler('none');
		
		$form = new Form(
			$this,
			"ImportForm",
			$fields,
			$actions,
			$validator
		);
		$form->setHTMLID("Form_ImportForm_" . $this->modelClass);
		return $form;
	}
	
	/**
	 * Imports the submitted CSV file based on specifications given in
	 * {@link self::model_importers}.
	 * Redirects back with a success/failure message.
	 * 
	 * @todo Figure out ajax submission of files via jQuery.form plugin
	 *
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	function import($data, $form, $request) {

		$modelName = $data['ClassName'];

		if(!$this->showImportForm() || (is_array($this->showImportForm()) && !in_array($modelName,$this->showImportForm()))) return false;
		$importers = $this->parentController->getModelImporters();
		$importerClass = $importers[$modelName];

		$loader = new $importerClass($data['ClassName']);

		// File wasn't properly uploaded, show a reminder to the user
		if(
			empty($_FILES['_CsvFile']['tmp_name']) ||
			file_get_contents($_FILES['_CsvFile']['tmp_name']) == ''
		) {
			$form->sessionMessage(_t('ModelAdmin.NOCSVFILE', 'Please browse for a CSV file to import'), 'good');
			$this->redirectBack();
			return false;
		}

		if (!empty($data['EmptyBeforeImport']) && $data['EmptyBeforeImport']) { //clear database before import
			$loader->deleteExistingRecords = true;
		}
		$results = $loader->load($_FILES['_CsvFile']['tmp_name']);

		$message = '';
		if($results->CreatedCount()) $message .= sprintf(
			_t('ModelAdmin.IMPORTEDRECORDS', "Imported %s records."),
			$results->CreatedCount()
		);
		if($results->UpdatedCount()) $message .= sprintf(
			_t('ModelAdmin.UPDATEDRECORDS', "Updated %s records."),
			$results->UpdatedCount()
		);
		if($results->DeletedCount()) $message .= sprintf(
			_t('ModelAdmin.DELETEDRECORDS', "Deleted %s records."),
			$results->DeletedCount()
		);
		if(!$results->CreatedCount() && !$results->UpdatedCount()) $message .= _t('ModelAdmin.NOIMPORT', "Nothing to import");

		$form->sessionMessage($message, 'good');
		$this->redirectBack();
	}
	
	
	/**
	 * Return the columns available in the column selection field.
	 * Overload this to make other columns available
	 */
	public function columnsAvailable() {
		return singleton($this->modelClass)->summaryFields();
	}

	/**
	 * Return the columns selected by default in the column selection field.
	 * Overload this to make other columns selected by default
	 */
	public function columnsSelectedByDefault() {
		return array_keys(singleton($this->modelClass)->summaryFields());
	}
	
	/**
	 * Give the flexibilility to show variouse combination of columns in the search result table
	 */
	public function ColumnSelectionField() {
		$model = singleton($this->modelClass);
		$source = $this->columnsAvailable();
		
		// select all fields by default
		$value = $this->columnsSelectedByDefault();
		
		// Reorder the source so that you read items down the column and then across
		$columnisedSource = array();
		$keys = array_keys($source);
		$midPoint = ceil(sizeof($source)/2);
		for($i=0;$i<$midPoint;$i++) {
			$key1 = $keys[$i];
			$columnisedSource[$key1] = $model->fieldLabel($source[$key1]);
			// If there are an odd number of items, the last item will be unset
			if(isset($keys[$i+$midPoint])) {
				$key2 = $keys[$i+$midPoint];
				$columnisedSource[$key2] = $model->fieldLabel($source[$key2]);
			}
		}

		$checkboxes = new CheckboxSetField("ResultAssembly", false, $columnisedSource, $value);
		
		$field = new CompositeField(
			new LiteralField(
				"ToggleResultAssemblyLink", 
				sprintf("<a class=\"form_frontend_function toggle_result_assembly\" href=\"#\">%s</a>",
					_t('ModelAdmin.CHOOSE_COLUMNS', 'Select result columns...')
				)
			),
			$checkboxesBlock = new CompositeField(
				$checkboxes,
				new LiteralField("ClearDiv", "<div class=\"clear\"></div>"),
				new LiteralField(
					"TickAllAssemblyLink",
					sprintf(
						"<a class=\"form_frontend_function tick_all_result_assembly\" href=\"#\">%s</a>",
						_t('ModelAdmin.SELECTALL', 'select all')
					)
				),
				new LiteralField(
					"UntickAllAssemblyLink",
					sprintf(
						"<a class=\"form_frontend_function untick_all_result_assembly\" href=\"#\">%s</a>",
						_t('ModelAdmin.SELECTNONE', 'select none')
					)
				)
			)
		);
		
		$field->addExtraClass("ResultAssemblyBlock");
		$checkboxesBlock->addExtraClass("hidden");
		return $field;
	}
	
	/**
	 * Action to render a data object collection, using the model context to provide filters
	 * and paging.
	 * 
	 * @return string
	 */
	function search($request, $form) {
		// Get the results form to be rendered
		$resultsForm = $this->ResultsForm(array_merge($form->getData(), $request));
		// Before rendering, let's get the total number of results returned
		$tableField = $resultsForm->Fields()->dataFieldByName($this->modelClass);
		$tableField->addExtraClass('resultsTable');
		$numResults = $tableField->TotalCount();
		
		if($numResults) {
			$msg = sprintf(
				_t('ModelAdmin.FOUNDRESULTS',"Your search found %s matching items"), 
				$numResults
			);
		} else {
			$msg = _t('ModelAdmin.NORESULTS',"Your search didn't return any matching items");
		}
		return new SS_HTTPResponse(
			$resultsForm->formHtmlContent(), 
			200, 
			$msg
		);
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
	
	/**
	 * Returns all columns used for tabular search results display.
	 * Defaults to all fields specified in {@link DataObject->summaryFields()}.
	 * 
	 * @param array $searchCriteria Limit fields by populating the 'ResultsAssembly' key
	 * @param boolean $selectedOnly Limit by 'ResultsAssempty
	 */
	function getResultColumns($searchCriteria, $selectedOnly = true) {
		$model = singleton($this->modelClass);

		$summaryFields = $this->columnsAvailable();
		
		if($selectedOnly && isset($searchCriteria['ResultAssembly'])) {
			$resultAssembly = $searchCriteria['ResultAssembly'];
			if(!is_array($resultAssembly)) {
				$explodedAssembly = split(' *, *', $resultAssembly);
				$resultAssembly = array();
				foreach($explodedAssembly as $item) $resultAssembly[$item] = true;
			}
			return array_intersect_key($summaryFields, $resultAssembly);
		} else {
			return $summaryFields;
		}
	}
	
	/**
	 * Creates and returns the result table field for resultsForm.
	 * Uses {@link resultsTableClassName()} to initialise the formfield. 
	 * Method is called from {@link ResultsForm}.
	 *
	 * @param array $searchCriteria passed through from ResultsForm 
	 *
	 * @return TableListField 
	 */
	function getResultsTable($searchCriteria) {
		
		$summaryFields = $this->getResultColumns($searchCriteria);

		$className = $this->parentController->resultsTableClassName();
		$tf = new $className(
			$this->modelClass,
			$this->modelClass,
			$summaryFields
		);

		$tf->setCustomQuery($this->getSearchQuery($searchCriteria));
		$tf->setPageSize($this->parentController->stat('page_length'));
		$tf->setShowPagination(true);
		// @todo Remove records that can't be viewed by the current user
		$tf->setPermissions(array_merge(array('view','export'), TableListField::permissions_for_object($this->modelClass)));

		// csv export settings (select all columns regardless of user checkbox settings in 'ResultsAssembly')
		$exportFields = $this->getResultColumns($searchCriteria, false);
		$tf->setFieldListCsv($exportFields);

		$url = '<a href=\"' . $this->Link() . '/$ID/edit\">$value</a>';
		if(count($summaryFields)) {
			$tf->setFieldFormatting(array_combine(
				array_keys($summaryFields), 
				array_fill(0,count($summaryFields), $url)
			));
		}
	
		return $tf;
	}
	
	/**
	 * Shows results from the "search" action in a TableListField. 
	 *
	 * @uses getResultsTable()
	 *
	 * @return Form
	 */
	function ResultsForm($searchCriteria) {
		
		if($searchCriteria instanceof SS_HTTPRequest) $searchCriteria = $searchCriteria->getVars();
		
		$tf = $this->getResultsTable($searchCriteria);
		
		// implemented as a form to enable further actions on the resultset
		// (serverside sorting, export as CSV, etc)
		$form = new Form(
			$this,
			'ResultsForm',
			new FieldSet(
				new TabSet('Root',
					new Tab('SearchResults',
						_t('ModelAdmin.SEARCHRESULTS','Search Results'),
						$tf
					)
				)
			),
			new FieldSet()
		);
		
		// Include the search criteria on the results form URL, but not dodgy variables like those below
		$filteredCriteria = $searchCriteria;
		unset($filteredCriteria['ctf']);
		unset($filteredCriteria['url']);
		unset($filteredCriteria['action_search']);

		$form->setFormAction($this->Link() . '/ResultsForm?' . http_build_query($filteredCriteria));
		return $form;
	}
	

	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Create a new model record.
	 *
	 * @param unknown_type $request
	 * @return unknown
	 */
	function add($request) {
		return new SS_HTTPResponse(
			$this->AddForm()->formHtmlContent(), 
			200, 
			sprintf(
				_t('ModelAdmin.ADDFORM', "Fill out this form to add a %s to the database."),
				$this->modelClass
			)
		);
	}

	/**
	 * Returns a form suitable for adding a new model, falling back on the default edit form.
	 * 
	 * Caution: The add-form shows a DataObject's {@link DataObject->getCMSFields()} method on a record
	 * that doesn't exist in the database yet, hence has no ID. This means the {@link DataObject->getCMSFields()}
	 * implementation has to ensure that no fields are added which would rely on a
	 * record ID being present, e.g. {@link HasManyComplexTableField}.
	 * 
	 * Example:
	 * <code>
	 * function getCMSFields() {
	 *   $fields = parent::getCMSFields();
	 *     if($this->exists()) {
	 *       $ctf = new HasManyComplexTableField($this, 'MyRelations', 'MyRelation');
	 *       $fields->addFieldToTab('Root.Main', $ctf);
	 *     }
	 *   return $fields;
	 * }
	 * </code>
	 *
	 * @return Form
	 */
	public function AddForm() {
		$newRecord = new $this->modelClass();
		
		if($newRecord->canCreate()){
			if($newRecord->hasMethod('getCMSAddFormFields')) {
				$fields = $newRecord->getCMSAddFormFields();
			} else {
				$fields = $newRecord->getCMSFields();
			}
			
			$validator = ($newRecord->hasMethod('getCMSValidator')) ? $newRecord->getCMSValidator() : null;
			if(!$validator) $validator = new RequiredFields();
			$validator->setJavascriptValidationHandler('none');
			
			$actions = new FieldSet (
				new FormAction("doCreate", _t('ModelAdmin.ADDBUTTON', "Add"))
			);
			
			$form = new Form($this, "AddForm", $fields, $actions, $validator);
			$form->loadDataFrom($newRecord);
			
			return $form;
		}
	}
	
	function doCreate($data, $form, $request) {
		$className = $this->getModelClass();
		$model = new $className();
		// We write before saveInto, since this will let us save has-many and many-many relationships :-)
		$model->write();
		$form->saveInto($model);
		$model->write();
		
		if($this->isAjax()) {
			$class = $this->parentController->getRecordControllerClass($this->getModelClass());
			$recordController = new $class($this, $request, $model->ID);
			return new SS_HTTPResponse(
				$recordController->EditForm()->formHtmlContent(), 
				200, 
				sprintf(
					_t('ModelAdmin.LOADEDFOREDITING', "Loaded '%s' for editing."),
					$model->Title
				)
			);
		} else {
			Director::redirect(Controller::join_links($this->Link(), $model->ID , 'edit'));
		}
	}
}

/**
 * Handles operations on a single record from a managed model.
 * 
 * @package cms
 * @subpackage core
 * @todo change the parent controller varname to indicate the model scaffolding functionality in ModelAdmin
 */
class ModelAdmin_RecordController extends Controller {
	protected $parentController;
	protected $currentRecord;
	
	static $allowed_actions = array('edit', 'view', 'EditForm', 'ViewForm');
	
	function __construct($parentController, $request, $recordID = null) {
		$this->parentController = $parentController;
		$modelName = $parentController->getModelClass();
		$recordID = ($recordID) ? $recordID : $request->param('Action');
		$this->currentRecord = DataObject::get_by_id($modelName, $recordID);
		
		parent::__construct();
	}
	
	/**
	 * Link fragment - appends the current record ID to the URL.
	 */
	public function Link($action = null) {
		return $this->parentController->Link(Controller::join_links($this->currentRecord->ID, $action));
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Edit action - shows a form for editing this record
	 */
	function edit($request) {
		if ($this->currentRecord) {
			if($this->isAjax()) {
				$this->response->setBody($this->EditForm()->formHtmlContent());
				$this->response->setStatusCode(
					200, 
					sprintf(
						_t('ModelAdmin.LOADEDFOREDITING', "Loaded '%s' for editing."),
						$this->currentRecord->Title
					)
				);
				return $this->response;
			} else {
				// This is really quite ugly; to fix will require a change in the way that customise() works. :-(
				return $this->parentController->parentController->customise(array(
					'Right' => $this->parentController->parentController->customise(array(
						'EditForm' => $this->EditForm()
					))->renderWith(array("{$this->class}_right",'LeftAndMain_right'))
				))->renderWith(array('ModelAdmin','LeftAndMain'));
			}
		} else {
			return _t('ModelAdmin.ITEMNOTFOUND', "I can't find that item");
		}
	}

	/**
	 * Returns a form for editing the attached model
	 */
	public function EditForm() {
		$fields = $this->currentRecord->getCMSFields();
		$fields->push(new HiddenField("ID"));
		
		$validator = ($this->currentRecord->hasMethod('getCMSValidator')) ? $this->currentRecord->getCMSValidator() : new RequiredFields();
		$validator->setJavascriptValidationHandler('none');
		
		$actions = $this->currentRecord->getCMSActions();
		if($this->currentRecord->canEdit(Member::currentUser())){
			if(!$actions->fieldByName('action_doSave') && !$actions->fieldByName('action_save')) {
				$actions->push(new FormAction("doSave", _t('ModelAdmin.SAVE', "Save")));
			}
		}else{
			$fields = $fields->makeReadonly();
		}
		
		if($this->currentRecord->canDelete(Member::currentUser())) {
			if(!$actions->fieldByName('action_doDelete')) {
				$actions->insertFirst($deleteAction = new FormAction('doDelete', _t('ModelAdmin.DELETE', 'Delete')));
			}
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
	 * @param SS_HTTPRequest $request
	 * @return mixed
	 */
	function doSave($data, $form, $request) {
		$form->saveInto($this->currentRecord);
		
		try {
			$this->currentRecord->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
		}
		
		
		// Behaviour switched on .
		if($this->parentController->isAjax()) {
			return $this->edit($request);
		} else {
			$this->parentController->redirectBack();
		}
	}	
	
	/**
	 * Delete the current record
	 */
	public function doDelete($data, $form, $request) {
		if($this->currentRecord->canDelete(Member::currentUser())) {
			$this->currentRecord->delete();
			Director::redirect($this->parentController->Link('SearchForm?action=search'));
		} else {
			$this->parentController->redirectBack();
		}
		return;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renders the record view template.
	 * 
	 * @param SS_HTTPRequest $request
	 * @return mixed
	 */
	function view($request) {
		if($this->currentRecord) {
			$form = $this->ViewForm();
			return $form->formHtmlContent();
		} else {
			return _t('ModelAdmin.ITEMNOTFOUND');
		}
	}

	/**
	 * Returns a form for viewing the attached model
	 * 
	 * @return Form
	 */
	public function ViewForm() {
		$fields = $this->currentRecord->getCMSFields();
		$form = new Form($this, "EditForm", $fields, new FieldSet());
		$form->loadDataFrom($this->currentRecord);
		$form->makeReadonly();
		return $form;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	function index() {
		Director::redirect(Controller::join_links($this->Link(), 'edit'));
	}
	
	function getCurrentRecord(){
		return $this->currentRecord;
	}
	
}

?>