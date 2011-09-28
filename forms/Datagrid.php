<?php

/**
 * Description of Datagrid
 *
 */
class Datagrid extends FormField {

	/**
	 *
	 * @var SS_list
	 */
	protected $datasource = null;
	
	protected $fieldList;
	
	
	protected $dataPresenterClassName = "DatagridPresenter";
	

	/**
	 * Creates a new datagrid field
	 *
	 * @param string $name
	 * @param string $title
	 * @param SS_list $source
	 * @param Form $form 
	 */
	function __construct($name, $title = null, SS_list $source = null, Form $form = null, $dataPresenterClassName = null) {
		parent::__construct($name, $title, null, $form);
		if ($source) $this->setDatasource($source);
		if ($dataPresenterClassName) $this->dataPresenterClassName = $dataPresenterClassName;
		$this->setDataPresenter($this->dataPresenterClassName);
	}
	
	function setItemClass($itemClass){
		$this->itemClass = $itemClass;
	}
	
	function setDataclass($dataClass){
		$this->dataClass = $dataClass;
	}
	
	function setDataPresenter($dataPresenterClassName){
		$this->dataPresenter = $dataPresenterClassName;
	}
	
	function getDataclass(){
		if ($this->dataClass) return $this->dataClass;
		if ($this->datasource->dataClass) return $this->datasource->dataClass;
		throw new Exception();
	}

	/**
	 * Set the datasource
	 *
	 * @param SS_List $datasource
	 */
	public function setDatasource(SS_List $datasource ) {
		$this->datasource = $datasource;
		$this->fieldList = singleton($datasource->dataClass)->summaryFields();
	}

	/**
	 * Get the datasource
	 *
	 * @return SS_list
	 */
	public function getDatasource() {
		return $this->datasource;
	}
	
	function FieldList() {
		return $this->fieldList;
	}
	
	
	function FieldHolder() {
		$dataPresenter = new $this->dataPresenter();
		$dataPresenter->setDatagrid($this);
		return $dataPresenter->render();
	}	
}

class DatagridPresenter extends ViewableData {
	
	/**
	 * @var $template string Template-Overrides
	 */
	protected $template = 'DatagridPresenter';
	/**
	 * @var $itemClass string Class name for each item/row
	 */
	protected $itemClass = 'DatagridPresenter_Item';
	
	protected $datagrid = null;
	
	public $fieldCasting = array();
	
	public $fieldFormatting = array();
	
	
	function setTemplate($template){
		$this->template = $template;
	}
	
	public function setDatagrid(Datagrid $datagrid){
		$this->datagrid = $datagrid;
	}
	
	public function getDatagrid(){
		return $this->datagrid;
	}
	
	/**
	 * Return a DataObjectSet of Datagrid_Item objects, suitable for display in the template.
	 */
	function Items() {
		$fieldItems = new ArrayList();
		if($items = $this->datagrid->datasource) {
			foreach($items as $item) {
				if($item) $fieldItems->push(new $this->itemClass($item, $this));
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
	 * @return array 
	 */
	public function Headers() {
		if($this->datagrid->datasource instanceof DataList ) {
			$fieldHeaders = new ArrayList();
			$fieldHeadersSummaryFields = singleton($this->datagrid->datasource->dataClass)->summaryFields();
			if (is_array($fieldHeadersSummaryFields)){
				foreach ($fieldHeadersSummaryFields as $name=>$title){
					$fieldHeaders->push(new ArrayData(array('Name'=>$name, 'Title'=>$title)));
				}
			}
			return $fieldHeaders;
		} else {
			$firstItem = $this->datasource->first();
			if(!$firstItem) {
				return array();
			}
			return array_combine(array_keys($firstItem),array_keys($firstItem));
		}
	}
	
	function setFieldCasting($casting) {
		$this->fieldCasting = $casting;
	}
	
	function setFieldFormatting($formatting) {
		$this->fieldFormatting = $formatting;
	}
	
	
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
	
	function ID() {
		return $this->item->ID;
	}
	
	function Parent() {
		return $this->parent;
	}
	
	function __construct($item, $parent) {
		$this->failover = $this->item = $item;
		$this->parent = $parent;
		parent::__construct();
	}
	
	function Fields($xmlSafe = true) {
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
