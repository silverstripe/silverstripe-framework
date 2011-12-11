<?php
/**
 * Displays a {@link SS_List} in a grid format.
 * 
 * GridField is a field that takes an SS_List and displays it in an table with rows 
 * and columns. It reminds of the old TableFields but works with SS_List types 
 * and only loads the necessary rows from the list.
 * 
 * The minimum configuration is to pass in name and title of the field and a 
 * SS_List.
 * 
 * <code>
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'));
 * </code>
 * 
 * If you want to modify the output of the grid you can attach a customised 
 * DataGridPresenter that are the actual Renderer of the data. Sapphire provides
 * a default one if you chooses not to.
 * 
 * @see GridFieldPresenter
 * @see SS_List
 * 
 * @package sapphire
 * @subpackage forms
 */
class GridField extends CompositeField {

	/** @var SS_List - the datasource */
	protected $list = null;

	/** @var string - the classname of the DataObject that the GridField will display. Defaults to the value of $this->list->dataClass */
	protected $modelClassName = '';
	
	/** @var array */
	public $fieldCasting = array();

	/** @var array */
	public $fieldFormatting = array();

	/** @var GridState - the current state of the GridField */
	protected $state = null;

	/**
	 * Creates a new GridField field
	 *
	 * @param string $name
	 * @param string $title
	 * @param SS_List $dataList
	 * @param Form $form 
	 * @param string|GridFieldPresenter $dataPresenterClassName - can either pass in a string or an instance of a GridFieldPresenter
	 */
	public function __construct($name, $title = null, SS_List $dataList = null, Form $form = null, $dataPresenterClassName = 'GridFieldPresenter') {
		parent::__construct($name, $title, null, $form);

		CompositeField::__construct();
		FormField::__construct($name);

		if ($dataList) $this->setList($dataList);

		$this->state = new GridState($this);

		$this->push($this->state);
		$this->push(new GridFieldSortableHeader($this));
		$this->push(new GridFieldBody($this));
		$this->push(new GridFieldPaginator($this));
	}

	function hasData() { return false; }

	function saveInto(DataObject $record) {}

	/**
	 * @param string $modelClassName 
	 */
	public function setModelClass($modelClassName) {
		$this->modelClassName = $modelClassName;
		
		return $this;
	}
	
	/**
	 * Returns a dataclass that is a DataObject type that this field should look like.
	 * 
	 * @throws Exception
	 * @return string
	 */
	public function getModelClass() {
		if ($this->modelClassName) return $this->modelClassName;
		if ($this->list->dataClass) return $this->list->dataClass;

		throw new Exception(get_class($this).' does not have a modelClassName');
	}
	

	/**
	 * @return array
	 */
	public function getDisplayFields() {
		return singleton($this->getModelClass())->summaryFields();
	}

	/**
	 * @param array $casting
	 */
	function setFieldCasting($casting) {
		$this->fieldCasting = $casting;
	}

	/**
	 * @param array $casting
	 */
	function getFieldCasting() {
		return $this->fieldCasting;
	}

	/**
	 * @param array $casting
	 */
	function setFieldFormatting($formatting) {
		$this->fieldFormatting = $formatting;
	}

	/**
	 * @param array $casting
	 */
	function getFieldFormatting() {
		return $this->fieldFormatting;
	}

	/**
	 * Set the datasource
	 *
	 * @param SS_List $list
	 */
	public function setList(SS_List $list) {
		$this->list = $list;
		return $this;
	}

	/**
	 * Get the datasource
	 *
	 * @return SS_List
	 */
	public function getList() {
		return $this->list;
	}
	
	/**
	 *
	 * @return GridState 
	 * @todo Get the gridstate change in a nicer way than inspecting the request
	 */
	public function getState() {
		$state = $this->state;

		return $this->state;
	}

	function FieldHolder() {
		$this->getState()->apply();

		$content = array(
			'head' => array(),
			'body' => array(),
			'foot' => array(),
			'misc' => array()
		);

		foreach($this->FieldList() as $subfield) {
			$location = 'misc';
			if ($subfield instanceof GridFieldElement) $location = $subfield->stat('location');

			$content[$location][] = $subfield->forTemplate();
		}

		$attrs = array(
			'id' => isset($this->id) ? $this->id : null,
			'class' => "field CompositeField {$this->extraClass()}"
		);

		$head = $content['head'] ? $this->createTag('thead', array(), implode("\n", $content['head'])) : '';
		$body = $content['body'] ? $this->createTag('tbody', array(), implode("\n", $content['body'])) : '';
		$foot = $content['foot'] ? $this->createTag('tfoot', array(), implode("\n", $content['foot'])) : '';

		return
			implode("\n", $content['misc']).
			$this->createTag('table', $attrs, $head."\n".$body."\n".$foot);
	}

}

class GridField_AlterAction extends FormAction_WithoutLabel {

	protected $gridField;
	protected $buttonLabel;
	protected $stateValues;

	function __construct($gridField, $name, $label) {
		$this->gridField = $gridField;
		$this->buttonLabel = $label;

		parent::__construct($name);
	}

	function stateChangeOnTrigger($stateValues) {
		$this->stateValues = $stateValues;
	}

	/**
	 * urlencode encodes less characters in percent form than we need - we need everything that isn't a \w
	 */
	function nameEncode($val) {
		return preg_replace_callback('/[^\w]/', array($this, '_nameEncode'), $val);
	}

	/**
	 * The callback for nameEncode
	 */
	function _nameEncode($match) {
		return '%'.dechex(ord($match[0]));
	}

	function Field() {
		$values = $this->nameEncode(json_encode($this->stateValues));

		$base = $this->gridField;
		$name = array();

		do {
			array_unshift($name, $base->Name());
			$base = $base->getForm();
		}
		while ($base && !($base instanceof Form));

		$name = implode('.', $name);

		$attributes = array(
			'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'type' => 'submit',
			'name' => 'action_gridFieldAlterAction'. '?' . 'Change_Grid='. $name . '&Change_State=' . $values,
			'tabindex' => $this->getTabIndex()
		);

		if($this->isReadonly()) {
			$attributes['disabled'] = 'disabled';
			$attributes['class'] = $attributes['class'] . ' disabled';
		}

		return $this->createTag('button', $attributes, $this->buttonLabel);
	}

}

class GridFieldForm extends Form {

	function getFormContainerURL() {
		$controller = $this->controller;
		$request = $controller->getRequest();

		if ($request) {
			if ($request->requestVar('BackURL')) {
				$url = $request->requestVar('BackURL');
			}
			else if($request->getHeader('Referer')) {
				$url = $request->getHeader('Referer');
			}
		}

		if(Director::is_site_url($url)) return $url;
	}

	function gridFieldAlterAction($vars) {
		$gridName = $vars['Change_Grid'];
		$change = json_decode($vars['Change_State']);

		Debug::dump($gridName);

		$grid = $this->Fields()->fieldByName($gridName);
		$state = $grid->getState();

		foreach ($change as $field => $val) {
			$parts = explode('.', $field);

			// TODO: Rewrite this to work. This currently only supports one particular type of set, and is insecure
			$base = $state;
			while(count($parts) > 1) $base = $base->getField(array_shift($parts));

			$base->setField($parts[0], $val);
		}

		// Make the form re-load it's values from the Session after redirect
		// so the changes we just made above survive the page reload
		// TODO: Form really needs refactoring so we dont have to do this

		if (Director::is_ajax()) {
			return $grid->forTemplate();
		}
		else {
			$data = $this->getData();
			Session::set("FormInfo.{$this->FormName()}.errors", array());
			Session::set("FormInfo.{$this->FormName()}.data", $data);

			Controller::curr()->redirectBack();
		}
	}

}