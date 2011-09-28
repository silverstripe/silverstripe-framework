<?php
/**
 * Datagrid
 * 
 * This field takes care of displaying a table of a list of data
 *
 */
class Datagrid extends FormField {

	/**
	 *
	 * @var SS_list
	 */
	protected $datasource = null;
	
	/**
	 *
	 * @var array 
	 */
	protected $fieldList = null;
	
	/**
	 *
	 * @var string
	 */
	protected $dataPresenterClassName = "DatagridPresenter";
	
	/**
	 * @var string - the name of the DataObject that the Datagrid will display
	 */
	protected $dataClass = '';

	/**
	 * Creates a new datagrid field
	 *
	 * @param string $name
	 * @param string $title
	 * @param SS_list $datasource
	 * @param Form $form 
	 * @param string $dataPresenterClassName
	 */
	function __construct($name, $title = null, SS_list $datasource = null, Form $form = null, $dataPresenterClassName = null) {
		parent::__construct($name, $title, null, $form);
		if ($datasource) $this->setDatasource($datasource);
		if ($dataPresenterClassName) $this->setDataPresenter($dataPresenterClassName);
	}
	
	/**
	 *
	 * @param string $dataClass 
	 */
	function setDataclass($dataClass) {
		$this->dataClass = $dataClass;
	}
	
	/**
	 *
	 * @param string $dataPresenterClassName 
	 * @throws Exception
	 */
	function setDataPresenter($dataPresenterClassName) {
		if(!$dataPresenterClassName){
			throw new Exception('Datapresenter for Datagrid must be set with a class');
		}
		if(!class_exists($dataPresenterClassName)){
			throw new Exception('Datapresenter for Datagrid must be set with an existing class');
		}
		
		if($dataPresenterClassName !='DatagridPresenter' && !ClassInfo::is_subclass_of($dataPresenterClassName, 'DatagridPresenter')){
			throw new Exception('Datapresenter "$dataPresenterClassName" must inherit DatagridPresenter' );
		}
		$this->dataPresenterClassName = $dataPresenterClassName;
	}
	
	/**
	 *
	 * @return type 
	 */
	function getDataclass() {
		if ($this->dataClass) return $this->dataClass;
		if ($this->datasource->dataClass) return $this->datasource->dataClass;
		throw new Exception(get_class($this).' does not have a dataclass');
	}

	/**
	 * Set the datasource
	 *
	 * @param SS_List $datasource
	 */
	public function setDatasource(SS_List $datasource) {
		$this->datasource = $datasource;
		if($datasource->dataClass){
			$this->fieldList = singleton($datasource->dataClass)->summaryFields();
		}
	}

	/**
	 * Get the datasource
	 *
	 * @return SS_list
	 */
	public function getDatasource() {
		return $this->datasource;
	}
	
	/**
	 * Returns the list of fields, or the 'column header' names of this grid
	 *
	 * @return array - e.g array('ID'=>'ID', 'Name'=>'Name)
	 */
	function FieldList() {
		return $this->fieldList;
	}
	
	/**
	 *
	 * @return string - html for the form 
	 */
	function FieldHolder() {
		$dataPresenter = new $this->dataPresenterClassName();
		$dataPresenter->setDatagrid($this);
		return $dataPresenter->render();
	}	
}