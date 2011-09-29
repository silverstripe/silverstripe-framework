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
	 * @var string
	 */
	protected $dataPresenterClassName = "DatagridPresenter";
	
	/**
	 *
	 * @var DatagridPresenter
	 */
	protected $datagridPresenter = null;
	
	/**
	 * @var string - the name of the DataObject that the Datagrid will display
	 */
	protected $modelClassName = '';

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
	 * @param string $modelClassName 
	 */
	function setModelClass($modelClassName) {
		$this->modelClassName = $modelClassName;
		return $this;
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
		return $this;
	}
	
	/**
	 *
	 * @return type 
	 */
	function getModelClass() {
		if ($this->modelClassName) {
			return $this->modelClassName;
		}
		if ($this->datasource->dataClass) {
			return $this->datasource->dataClass;
		}
		throw new Exception(get_class($this).' does not have a modelClassName');
	}

	/**
	 * Set the datasource
	 *
	 * @param SS_List $datasource
	 */
	public function setDatasource(SS_List $datasource) {
		$this->datasource = $datasource;
		return $this;
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
	 *
	 * @return DatagridPresenter
	 */
	public function getDatagridPresenter(){
		if(!$this->datagridPresenter) {
			$this->datagridPresenter = new $this->dataPresenterClassName();
			$this->datagridPresenter->setDatagrid($this);
		}
		return $this->datagridPresenter;
	}
	
	/**
	 *
	 * @return string - html for the form 
	 */
	function FieldHolder() {
		return $this->getDatagridPresenter()->render();
	}	
}