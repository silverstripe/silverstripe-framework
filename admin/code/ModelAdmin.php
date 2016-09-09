<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BulkLoader;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\ResetFormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;
use SilverStripe\View\ArrayData;

/**
 * Generates a three-pane UI for editing model classes, with an
 * automatically generated search panel, tabular results and edit forms.
 *
 * Relies on data such as {@link DataObject::$db} and {@link DataObject::getCMSFields()}
 * to scaffold interfaces "out of the box", while at the same time providing
 * flexibility to customize the default output.
 *
 * @uses SearchContext
 */
abstract class ModelAdmin extends LeftAndMain {

	private static $url_rule = '/$ModelClass/$Action';

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
	 *
	 * @config
	 * @var array|string
	 */
	private static $managed_models = null;

	/**
	 * Override menu_priority so that ModelAdmin CMSMenu objects
	 * are grouped together directly above the Help menu item.
	 * @var float
	 */
	private static $menu_priority = -0.5;

	private static $menu_icon = 'framework/admin/client/src/sprites/menu-icons/16x16/db.png';

	private static $allowed_actions = array(
		'ImportForm',
		'SearchForm',
	);

	private static $url_handlers = array(
		'$ModelClass/$Action' => 'handleAction'
	);

	/**
	 * @var String
	 */
	protected $modelClass;

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
	 * @config
	 * @var array
	 */
	private static $model_importers = null;

	/**
	 * Amount of results showing on a single page.
	 *
	 * @config
	 * @var int
	 */
	private static $page_length = 30;

	/**
	 * Initialize the model admin interface. Sets up embedded jquery libraries and requisite plugins.
	 */
	protected function init() {
		parent::init();

		$models = $this->getManagedModels();

		if($this->getRequest()->param('ModelClass')) {
			$this->modelClass = $this->unsanitiseClassName($this->getRequest()->param('ModelClass'));
		} else {
			reset($models);
			$this->modelClass = key($models);
		}

		// security check for valid models
		if(!array_key_exists($this->modelClass, $models)) {
			user_error('ModelAdmin::init(): Invalid Model class', E_USER_ERROR);
		}

		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/client/dist/js/ModelAdmin.js');
	}

	public function Link($action = null) {
		if(!$action) $action = $this->sanitiseClassName($this->modelClass);
		return parent::Link($action);
	}

	public function getEditForm($id = null, $fields = null) {
		$list = $this->getList();
		$exportButton = new GridFieldExportButton('buttons-before-left');
		$exportButton->setExportColumns($this->getExportFields());
		$listField = GridField::create(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
				->addComponent($exportButton)
				->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldFilterHeader')
				->addComponents(new GridFieldPrintButton('buttons-before-left'))
		);

		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			/** @var GridFieldDetailForm $detailform */
			$detailform = $listField->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDetailForm');
			$detailform->setValidator($detailValidator);
		}

		$form = Form::create(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		)->setHTMLID('Form_EditForm');
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$editFormAction = Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm');
		$form->setFormAction($editFormAction);
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);

		return $form;
	}

	/**
	 * Define which fields are used in the {@link getEditForm} GridField export.
	 * By default, it uses the summary fields from the model definition.
	 *
	 * @return array
	 */
	public function getExportFields() {
		return singleton($this->modelClass)->summaryFields();
	}

	/**
	 * @return SearchContext
	 */
	public function getSearchContext() {
		$context = DataObject::singleton($this->modelClass)->getDefaultSearchContext();

		// Namespace fields, for easier detection if a search is present
		foreach($context->getFields() as $field) {
			$field->setName(sprintf('q[%s]', $field->getName()));
		}
		foreach($context->getFilters() as $filter) {
			$filter->setFullName(sprintf('q[%s]', $filter->getFullName()));
		}

		$this->extend('updateSearchContext', $context);

		return $context;
	}

	/**
	 * @return Form
	 */
	public function SearchForm() {
		$context = $this->getSearchContext();
		/** @skipUpgrade */
		$form = new Form($this, "SearchForm",
			$context->getSearchFields(),
			new FieldList(
				FormAction::create('search', _t('MemberTableField.APPLY_FILTER', 'Apply Filter'))
					->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive'),
				ResetFormAction::create('clearsearch', _t('ModelAdmin.RESET','Reset'))
					->setUseButtonTag(true)
			),
			new RequiredFields()
		);
		$form->setFormMethod('get');
		$form->setFormAction($this->Link($this->sanitiseClassName($this->modelClass)));
		$form->addExtraClass('cms-search-form');
		$form->disableSecurityToken();
		$form->loadDataFrom($this->getRequest()->getVars());

		$this->extend('updateSearchForm', $form);

		return $form;
	}

	public function getList() {
		$context = $this->getSearchContext();
		$params = $this->getRequest()->requestVar('q');

		if(is_array($params)) {
			$params = ArrayLib::array_map_recursive('trim', $params);
		}

		$list = $context->getResults($params);

		$this->extend('updateList', $list);

		return $list;
	}


	/**
	 * Returns managed models' create, search, and import forms
	 * @uses SearchContext
	 * @uses SearchFilter
	 * @return SS_List of forms
	 */
	protected function getManagedModelTabs() {
		$models = $this->getManagedModels();
		$forms  = new ArrayList();

		foreach($models as $class => $options) {
			$forms->push(new ArrayData(array (
				'Title'     => $options['title'],
				'ClassName' => $class,
				'Link' => $this->Link($this->sanitiseClassName($class)),
				'LinkOrCurrent' => ($class == $this->modelClass) ? 'current' : 'link'
			)));
		}

		return $forms;
	}

	/**
	 * Sanitise a model class' name for inclusion in a link
	 *
	 * @param string $class
	 * @return string
	 */
	protected function sanitiseClassName($class) {
		return str_replace('\\', '-', $class);
	}

	/**
	 * Unsanitise a model class' name from a URL param
	 *
	 * @param string $class
	 * @return string
	 */
	protected function unsanitiseClassName($class) {
		return str_replace('-', '\\', $class);
	}

	/**
	 * @return array Map of class name to an array of 'title' (see {@link $managed_models})
	 */
	public function getManagedModels() {
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

		// Normalize models to have their model class in array key
		foreach($models as $k => $v) {
			if(is_numeric($k)) {
				$models[$v] = array('title' => singleton($v)->i18n_plural_name());
				unset($models[$k]);
			}
		}

		return $models;
	}

	/**
	 * Returns all importers defined in {@link self::$model_importers}.
	 * If none are defined, we fall back to {@link self::managed_models}
	 * with a default {@link CsvBulkLoader} class. In this case the column names of the first row
	 * in the CSV file are assumed to have direct mappings to properties on the object.
	 *
	 * @return array Map of model class names to importer instances
	 */
	public function getModelImporters() {
		$importerClasses = $this->stat('model_importers');

		// fallback to all defined models if not explicitly defined
		if(is_null($importerClasses)) {
			$models = $this->getManagedModels();
			foreach($models as $modelName => $options) {
				$importerClasses[$modelName] = 'SilverStripe\\Dev\\CsvBulkLoader';
			}
		}

		$importers = array();
		foreach($importerClasses as $modelClass => $importerClass) {
			$importers[$modelClass] = new $importerClass($modelClass);
		}

		return $importers;
	}

	/**
	 * Generate a CSV import form for a single {@link DataObject} subclass.
	 *
	 * @return Form|false
	 */
	public function ImportForm() {
		$modelSNG = singleton($this->modelClass);
		$modelName = $modelSNG->i18n_singular_name();
		// check if a import form should be generated
		if(!$this->showImportForm ||
			(is_array($this->showImportForm) && !in_array($this->modelClass, $this->showImportForm))
		) {
			return false;
		}

		$importers = $this->getModelImporters();
		if(!$importers || !isset($importers[$this->modelClass])) return false;

		if(!$modelSNG->canCreate(Member::currentUser())) return false;

		$fields = new FieldList(
			new HiddenField('ClassName', _t('ModelAdmin.CLASSTYPE'), $this->modelClass),
			new FileField('_CsvFile', false)
		);

		// get HTML specification for each import (column names etc.)
		$importerClass = $importers[$this->modelClass];
		/** @var BulkLoader $importer */
		$importer = new $importerClass($this->modelClass);
		$spec = $importer->getImportSpec();
		$specFields = new ArrayList();
		foreach($spec['fields'] as $name => $desc) {
			$specFields->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
		}
		$specRelations = new ArrayList();
		foreach($spec['relations'] as $name => $desc) {
			$specRelations->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
		}
		$specHTML = $this->customise(array(
			'ClassName' => $this->sanitiseClassName($this->modelClass),
			'ModelName' => Convert::raw2att($modelName),
			'Fields' => $specFields,
			'Relations' => $specRelations,
		))->renderWith($this->getTemplatesWithSuffix('_ImportSpec'));

		$fields->push(new LiteralField("SpecFor{$modelName}", $specHTML));
		$fields->push(
			new CheckboxField('EmptyBeforeImport', _t('ModelAdmin.EMPTYBEFOREIMPORT', 'Replace data'),
				false)
		);

		$actions = new FieldList(
			new FormAction('import', _t('ModelAdmin.IMPORT', 'Import from CSV'))
		);

		$form = new Form(
			$this,
			"ImportForm",
			$fields,
			$actions
		);
		$form->setFormAction(
			Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'ImportForm')
		);

		$this->extend('updateImportForm', $form);

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
	 * @param HTTPRequest $request
	 * @return bool|HTTPResponse
	 */
	public function import($data, $form, $request) {
		if(!$this->showImportForm || (is_array($this->showImportForm)
				&& !in_array($this->modelClass,$this->showImportForm))) {

			return false;
		}

		$importers = $this->getModelImporters();
		/** @var BulkLoader $loader */
		$loader = $importers[$this->modelClass];

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
		if($results->CreatedCount()) $message .= _t(
			'ModelAdmin.IMPORTEDRECORDS', "Imported {count} records.",
			array('count' => $results->CreatedCount())
		);
		if($results->UpdatedCount()) $message .= _t(
			'ModelAdmin.UPDATEDRECORDS', "Updated {count} records.",
			array('count' => $results->UpdatedCount())
		);
		if($results->DeletedCount()) $message .= _t(
			'ModelAdmin.DELETEDRECORDS', "Deleted {count} records.",
			array('count' => $results->DeletedCount())
		);
		if(!$results->CreatedCount() && !$results->UpdatedCount()) {
			$message .= _t('ModelAdmin.NOIMPORT', "Nothing to import");
		}

		$form->sessionMessage($message, 'good');
		return $this->redirectBack();
	}

	/**
	 * @param bool $unlinked
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// Show the class name rather than ModelAdmin title as root node
		$models = $this->getManagedModels();
		$params = $this->getRequest()->getVars();
		if(isset($params['url'])) unset($params['url']);

		$items[0]->Title = $models[$this->modelClass]['title'];
		$items[0]->Link = Controller::join_links(
			$this->Link($this->sanitiseClassName($this->modelClass)),
			'?' . http_build_query($params)
		);

		return $items;
	}

}
