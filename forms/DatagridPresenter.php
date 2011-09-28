<?php

/**
 * Description of DatagridPresenter
 *
 */
class DatagridPresenter extends ViewableData {
	
	/**
	 * Template override
	 * 
	 * @var string $template 
	 */
	protected $template = 'DatagridPresenter';
	
	/**
	 * Class name for each item/row
	 * 
	 * @var string $itemClass
	 */
	protected $itemClass = 'DatagridPresenter_Item';
	
	/**
	 *
	 * @var Datagrid
	 */
	protected $datagrid = null;
	
	/**
	 *
	 * @var array
	 */
	public $fieldCasting = array();
	
	/**
	 *
	 * @var array
	 */
	public $fieldFormatting = array();
	
	/**
	 *
	 * @param string $template 
	 */
	function setTemplate($template){
		$this->template = $template;
	}
	
	/**
	 *
	 * @param Datagrid $datagrid 
	 */
	public function setDatagrid(Datagrid $datagrid){
		$this->datagrid = $datagrid;
	}
	
	/**
	 *
	 * @return Datagrid
	 */
	public function getDatagrid(){
		return $this->datagrid;
	}
	
	/**
	 * Return a ArrayList of Datagrid_Item objects, suitable for display in the template.
	 * 
	 * @return ArrayList
	 */
	function Items() {
		$fieldItems = new ArrayList();
		if($items = $this->datagrid->datasource) {
			foreach($items as $item) {
				if(!$item) {
					continue;
				}
				$fieldItems->push(new $this->itemClass($item, $this));
			}
		}
		return $fieldItems;
	}
	
	/**
	 * Get the headers or column names for this grid
	 *
	 * The returning array will have the format of
	 * array(
	 *     'FirstName' => 'First name',
	 *     'Description' => 'A nice description'
	 * )
	 *
	 * @return ArrayList
	 */
	public function Headers() {
		if($this->datagrid->datasource instanceof DataList ) {
			return $this->getHeadersFromDatalist();
		} else {
			if(!$this->datasource) {
				return array();
			}
			$firstItem = $this->datasource->first();
			if(!$firstItem) {
				return array();
			}
			return array_combine(array_keys($firstItem),array_keys($firstItem));
		}
	}
	
	/**
	 *
	 * @return ArrayList
	 */
	protected function getHeadersFromDatalist(){
		$fieldHeaders = new ArrayList();
		$fieldHeadersSummaryFields = singleton($this->datagrid->datasource->dataClass)->summaryFields();
		if (is_array($fieldHeadersSummaryFields)){
			foreach ($fieldHeadersSummaryFields as $name=>$title){
				$fieldHeaders->push(new ArrayData(array('Name'=>$name, 'Title'=>$title)));
			}
		}
		return $fieldHeaders;
	}
	
	/**
	 *
	 * @param type $casting 
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
	 *
	 * @return string - html
	 */
	function render(){
		return $this->renderWith(array($this->template));
	}
}

/**
 * A single record in a Datagrid.
 * @package forms
 * @see Datagrid
 */
class DatagridPresenter_Item extends ViewableData {
	
	/**
	 * @var DataObject The underlying data record,
	 * usually an element of {@link Datagrid->datasource()}.
	 */
	protected $item;
	
	/**
	 * @var Datagrid
	 */
	protected $parent;
	
	/**
	 *
	 * @param type $item
	 * @param type $parent 
	 */
	public function __construct($item, $parent) {
		$this->failover = $this->item = $item;
		$this->parent = $parent;
		parent::__construct();
	}
	
	/**
	 *
	 * @return int
	 */
	public function ID() {
		return $this->item->ID;
	}
	
	/**
	 *
	 * @return type 
	 */
	public function Parent() {
		return $this->parent;
	}

	/**
	 *
	 * @param bool $xmlSafe
	 * @return ArrayList 
	 */
	public function Fields($xmlSafe = true) {
		$list = $this->parent->getDatagrid()->FieldList();
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
			if($escape = $this->parent->getDatagrid()->fieldEscape){
				foreach($escape as $search => $replace){
					$value = str_replace($search, $replace, $value);
				}
			}
			
			$fields[] = new ArrayData(array(
				"Name" => $fieldName, 
				"Title" => $fieldTitle,
				"Value" => $value
			));
		}
		return new ArrayList($fields);
	}
}
