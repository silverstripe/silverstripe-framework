<?php
/**
 * The GridFieldPresenter is responsible for rendering and attach user behaviour
 * to a GridField.
 * 
 * You can create a GridFieldPresenter and inject that into a GridField to 
 * customise look and feel of GridField.
 * 
 * It also have the possibility to let extensions to modify the look and feel of
 * the GridField if you dont want to make a fully blown GridFieldPresenter.
 * 
 * In the following example we configure the GridField to sort the DataList in 
 * the GridField by Title. This will override the sorting on the DataList.
 * 
 * <code>
 * $presenter = new GridFieldPresenter();
 * $presenter->sort('Title', 'desc');
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'),null, $presenter);
 * </code>
 * 
 * Another example is to change the template for the rendering 
 *
 * <code>
 * $presenter = new GridFieldPresenter();
 * $presenter->setTemplate('MyNiftyGridTemplate');
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'),null, $presenter);
 * </code>
 * 
 * There is also a possibility to add extensions to the GridPresenter. An 
 * example is the DataGridPagination that decorates the GridField with 
 * pagination. Look in the GridFieldPresenter::Items() and the filterList extend
 * and GridFieldPresenter::Footers()
 * 
 * <code>
 * GridFieldPresenter::add_extension('GridFieldPaginator_Extension');
 * $presenter = new GridFieldPresenter();
 * // This is actually calling GridFieldPaginator_Extension::paginationLimit()
 * $presenter->paginationLimit(3);
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'),null, $presenter);
 * </code>
 * 
 * @see GridField
 * @see GridFieldPaginator
 * @package sapphire
 */
class GridFieldPresenter extends ViewableData {
	
	/**
	 * Template override
	 * 
	 * @var string $template 
	 */
	protected $template = 'GridFieldPresenter';
	
	/**
	 * Class name for each item/row
	 * 
	 * @var string $itemClass
	 */
	protected $itemClass = 'GridFieldPresenter_Item';
	
	/**
	 * @var GridField
	 */
	protected $GridField = null;
	
	/**
	 * @var array
	 */
	public $fieldCasting = array();
	
	/**
	 * @var array
	 */
	public $fieldFormatting = array();
	
	/**
	 * List of columns and direction that the {@link GridFieldPresenter} is
	 * sorted in.
	 *
	 * @var array
	 */
	protected $sorting = array();
	
	/**
	 * @param string $template 
	 */
	public function setTemplate($template){
		$this->template = $template;
	}
	
	/**
	 * The name of the Field
	 *
	 * @return string
	 */
	public function getName() {
		return $this->getGridField()->getName();
	}
	
	/**
	 * @param GridField $GridField 
	 */
	public function setGridField(GridField $grid){
		$this->GridField = $grid;
	}
	
	/**
	 * @return GridField
	 */
	public function getGridField(){
		return $this->GridField;
	}
	
	/**
	 *
	 * @param type $extension 
	 */
	public static function add_extension($extension) {
		parent::add_extension(__CLASS__, $extension);
	}
	
	/**
	 * Sort the grid by columns
	 *
	 * @param string $column
	 * @param string $direction 
	 */
	public function sort($column, $direction = 'asc') {
		$this->sorting[$column] = $direction;
		
		return $this;
	}
	
	/**
	 * Return an {@link ArrayList} of {@link GridField_Item} objects, suitable for display in the template.
	 * 
	 * @return ArrayList
	 */
	public function Items() {
	$items = new ArrayList();
		
		if($this->sorting) {
			$this->setSortingOnList($this->sorting);
		}
		//empty for now
		$list = $this->getGridField()->getList();
		
		$parameters = new stdClass();
		$parameters->Controller = Controller::curr();
		$parameters->Request = Controller::curr()->getRequest();
		
		$this->extend('filterList', $list, $parameters);
	
		if($list) {
			$numberOfRows = $list->count();
			$counter = 0;
			foreach($list as $item) {
				$itemPresenter = new $this->itemClass($item, $this);
				$itemPresenter->iteratorProperties($counter++, $numberOfRows);
				$items->push($itemPresenter);
			}
		}
		return $items;
	}
	
	/**
	 * Get the headers or column names for this grid
	 *
	 * The returning array will have the format of
	 * 
	 * <code>
	 * 	array(
	 *		'FirstName' => 'First name',
	 *  	'Description' => 'A nice description'
	 *	)
	 * </code>
	 *
	 * @return ArrayList
	 * @throws Exception
	 */
	public function Headers() {
		if(!$this->getList()) {
			throw new LogicException(sprintf(
				'%s needs an data source to be able to render the form', get_class($this->getGridField())
			));
		}
		return $this->summaryFieldsToList($this->FieldList());
	}
	
	/**
	 *
	 * @return ArrayList 
	 */
	public function Footers() {
		$arrayList = new ArrayList();
		$footers = $this->extend('Footer');
		foreach($footers as $footer) {
			$arrayList->push($footer);
		}
		return $arrayList;
	}
	
	/**
	 * @return SS_List
	 */
	public function getList() {
		return $this->getGridField()->getList();
	}
	
	/**
	 * @return string - name of model
	 */
	protected function getModelClass() {
		return $this->getGridField()->getModelClass();
	}
	
	/**
	 * Add the combined sorting on the datasource
	 * 
	 * If the sorting isn't set in the datasource, only the latest sort
	 * will be executed.
	 *
	 * @param array $sortColumns 
	 */
	protected function setSortingOnList(array $sortColumns) {
		$resultColumns = array();
		
		foreach($sortColumns as $column => $sortOrder) {
			$resultColumns[] = sprintf("%s %s", $column ,$sortOrder);
		}
		
		$sort = implode(', ', $resultColumns);
		$this->getList()->sort($sort);
	}
	
	/**
	 * @return array
	 */
	public function FieldList() {
		return singleton($this->getModelClass())->summaryFields();
	}
	
	/**
	 * Translate the summaryFields from a model into a format that is understood
	 * by the Form renderer
	 *
	 * @param array $summaryFields
	 *
	 * @return ArrayList 
	 */
	protected function summaryFieldsToList($summaryFields) {
		$headers = new ArrayList();
		
		if(is_array($summaryFields)) {
			$counter = 0;
			
			foreach ($summaryFields as $name => $title) {
				$data = array(
					'Name' => $name,
					'Title' => $title,
					'IsSortable' => true,
					'IsSorted' => false,
					'SortedDirection' => 'asc'
				);
				
				if(array_key_exists($name, $this->sorting)) {
					$data['IsSorted'] = true;
					$data['SortedDirection'] = $this->sorting[$name];
				}
				
				$result = new ArrayData($data);
				$result->iteratorProperties($counter++, count($summaryFields));
				
				$headers->push($result);
			}
		}
		
		return $headers;
	} 
	
	/**
	 * @param array $casting 
	 */
	function setFieldCasting($casting) {
		$this->fieldCasting = $casting;
	}
	
	/**
	 *
	 * @param type $formatting 
	 */
	function setFieldFormatting($formatting) {
		$this->fieldFormatting = $formatting;
	}
	
	/**
	 * @return string - html
	 */
	function render(){
		return $this->renderWith(array($this->template));
	}
}

/**
 * A single record in a GridField.
 *
 * @package sapphire
 * @see GridField
 */
class GridFieldPresenter_Item extends ViewableData {
	
	/**
	 * @var Object The underlying record, usually an element of 
	 * {@link GridField->datasource()}.
	 */
	protected $item;
	
	/**
	 * @var GridFieldPresenter
	 */
	protected $parent;
	
	/**
	 * @param Object $item
	 * @param GridFieldPresenter $parent 
	 */
	public function __construct($item, $parent) {
		$this->failover = $this->item = $item;
		$this->parent = $parent;
		
		parent::__construct();
	}
	
	/**
	 * @return int
	 */
	public function ID() {
		return $this->item->ID;
	}
	
	/**
	 * @return type 
	 */
	public function Parent() {
		return $this->parent;
	}
	

	/**
	 * @param bool $xmlSafe
	 *
	 * @return ArrayList 
	 */
	public function Fields($xmlSafe = true) {
		$list = $this->parent->FieldList();
		$counter = 0;
		
		foreach($list as $fieldName => $fieldTitle) {
			$value = "";

			// TODO Delegates that to DataList
			// This supports simple FieldName syntax
			if(strpos($fieldName,'.') === false) {
				$value = ($this->item->XML_val($fieldName) && $xmlSafe) ? $this->item->XML_val($fieldName) : $this->item->RAW_val($fieldName);
				
			// This support the syntax fieldName = Relation.RelatedField
			} else {					
				$fieldNameParts = explode('.', $fieldName)	;
				$tmpItem = $this->item;
				
				for($j=0;$j<sizeof($fieldNameParts);$j++) {
					$relationMethod = $fieldNameParts[$j];
					$idField = $relationMethod . 'ID';
					if($j == sizeof($fieldNameParts)-1) {
						if($tmpItem) $value = $tmpItem->$relationMethod;
					} else {
						if($tmpItem) $tmpItem = $tmpItem->$relationMethod();
					}
				}
			}
			
			// casting
			if(array_key_exists($fieldName, $this->parent->fieldCasting)) {
				$value = $this->parent->getCastedValue($value, $this->parent->fieldCasting[$fieldName]);
			} elseif(is_object($value) && method_exists($value, 'Nice')) {
				$value = $value->Nice();
			}
			
			// formatting
			$item = $this->item;
			if(array_key_exists($fieldName, $this->parent->fieldFormatting)) {
				$format = str_replace('$value', "__VAL__", $this->parent->fieldFormatting[$fieldName]);
				$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$item->$1', $format);
				$format = str_replace('__VAL__', '$value', $format);
				eval('$value = "' . $format . '";');
			}
			
			//escape
			if($escape = $this->parent->getGridField()->fieldEscape){
				foreach($escape as $search => $replace){
					$value = str_replace($search, $replace, $value);
				}
			}
			
			$arrayData = new ArrayData(array(
				"Name" => $fieldName, 
				"Title" => $fieldTitle,
				"Value" => $value
			));
			$arrayData->iteratorProperties($counter++, count($list));
			$fields[] = $arrayData;
		}
		
		return new ArrayList($fields);
	}
}
