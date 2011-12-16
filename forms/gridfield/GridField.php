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
	
	static $allowed_actions = array(
		'gridFieldAlterAction'
	);

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
	public function __construct($name, $title = null, SS_List $dataList = null, Form $form = null) {
		parent::__construct($name, $title, null, $form);
		$this->addExtraClass('ss-gridfield');
		Requirements::css('sapphire/css/GridField.css');
		
		CompositeField::__construct();
		FormField::__construct($name);

		if ($dataList) {
			$this->setList($dataList);
		}

		$this->state = new GridState($this);
		
		$this->push(new GridFieldSortableHeader($this));
		$this->push(new GridFieldBody($this));
		$this->push(new GridFieldPaginator($this));
		$this->push(new GridFieldFilter($this));
		$this->push($this->state);
		
	}

	function hasData() { return false; }

	function saveInto(DataObject $record) {}
	
	public function getExtraColumnsCount() {
		$max = 0;
		foreach($this->FieldList() as $field ){
			$extras = Object::get_static(get_class($field), 'extra_columns');
			$max = $extras+$max;
		}
		return $max;
	}
	
	public function getColumnCount() {
		return count($this->getDisplayFields())+$this->getExtraColumnsCount();
	}

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
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Returns the whole gridfield rendered with all the attached Elements
	 *
	 * @return string
	 */
	public function FieldHolder() {
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
	
	/**
	 *
	 * @param type $vars
	 * @return type 
	 */
	public function gridFieldAlterAction($vars, $form, $request) {
		$id = $vars['StateID'];
		$stateChange = Session::get($id);
		
		$gridName = $stateChange['grid'];
		
		$grid = $form->Fields()->fieldByName($gridName);
		if ($grid) {
			$state = $grid->getState();
		
			$values = $stateChange['values'];
			$fields = $stateChange['fields'];
		
			$data = $form->getData();
			if ($fields) {
				foreach ($fields as $name => $fieldNames) {
					foreach ($fieldNames as $fieldName) {
						if ($data[$fieldName]) {
							$values[$name][$fieldName] = $vars[$fieldName];	
						} 		
					}
				}
			}
			$state->update($values);
		}
		
		// Make the form re-load it's values from the Session after redirect
		// so the changes we just made above survive the page reload
		// TODO: Form really needs refactoring so we dont have to do this
		if (Director::is_ajax()) {
			return $form->forTemplate();
		}
		else {
			$data = $this->getData();
			Session::set("FormInfo.{$form->FormName()}.errors", array());
			Session::set("FormInfo.{$form->FormName()}.data", $data);

			Controller::curr()->redirectBack();
		}
	}

}

/**
 * This class is the base class when you want to have an action that alters the state of the gridfield
 * 
 * @package sapphire
 * @subpackage forms
 * 
 */
class GridField_AlterAction extends FormAction_WithoutLabel {

	/**
	 *
	 * @var GridField
	 */
	protected $gridField;
	
	/**
	 *
	 * @var string
	 */
	protected $buttonLabel;
	
	/**
	 *
	 * @var array 
	 */
	protected $stateValues;
	
	/**
	 *
	 * @var array
	 */
	protected $stateFields = array();

	/**
	 *
	 * @param GridField $gridField
	 * @param string $name
	 * @param string $label 
	 */
	public function __construct(GridField $gridField, $name, $label) {
		$this->gridField = $gridField;
		$this->buttonLabel = $label;
		parent::__construct($name);
	}

	/**
	 *
	 * @param array $stateValues 
	 */
	public function stateChangeOnTrigger($stateValues) {
		$this->stateValues = $stateValues;
	}

	/**
	 *
	 * @param string $state (Filter.Criteria)
	 * @param array $fields 
	 */
	public function applyStateFromFieldsOnTrigger($state, $fields) {
		$this->stateFields[$state] = array();
		foreach ($fields as $field) {
			$this->stateFields[$state][] = $field->getName();
		}
	}

	/**
	 * urlencode encodes less characters in percent form than we need - we need everything that isn't a \w
	 * 
	 * @param string $val
	 */
	public function nameEncode($val) {
		return preg_replace_callback('/[^\w]/', array($this, '_nameEncode'), $val);
	}

	/**
	 * The callback for nameEncode
	 * 
	 * @param string $val
	 */
	public function _nameEncode($match) {
		return '%'.dechex(ord($match[0]));
	}

	/**
	 * Default method used by Templates to render the form
	 *
	 * @return string HTML tag
	 */
	public function Field() {
		// Store state in session, and pass ID to client side
		$state = array(
			'grid' => $this->getNameFromParent($this->gridField),
			'values' => $this->stateValues,
			'fields' => $this->stateFields
		);
		
		$id = preg_replace('/[^\w]+/', '_', uniqid('', true));
		Session::set($id, $state);

		// And generate field
		$attributes = array(
			'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'type' => 'submit',
			// Note:  This field needs to be less than 65 chars, otherwise Suhosin security patch 
			// will strip it from the requests 
			'name' => 'action_gridFieldAlterAction'. '?' . 'StateID='.$id,
			'tabindex' => $this->getTabIndex(),
		);

		if($this->isReadonly()) {
			$attributes['disabled'] = 'disabled';
			$attributes['class'] = $attributes['class'] . ' disabled';
		}

		return $this->createTag('button', $attributes, $this->buttonLabel);
	}

	/**
	 * Calculate the name of the gridfield relative to the Form
	 *
	 * @param GridField $base
	 * @return string
	 */
	protected function getNameFromParent(GridField $base ) {
		$name = array();
		do {
			array_unshift($name, $base->getName());
			$base = $base->getForm();
		} while ($base && !($base instanceof Form));
		return implode('.', $name);
	}
}