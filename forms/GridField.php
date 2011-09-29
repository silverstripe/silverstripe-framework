<?php
/**
 * Displays a {@link SS_List} in a grid format.
 *
 * @package sapphire
 * @subpackage forms
 */
class GridField extends FormField {

	/**
	 * @var SS_List
	 */
	protected $dataSource = null;
	
	/**
	 * @var string
	 */
	protected $presenterClassName = "GridFieldPresenter";
	
	/**
	 * @var GridFieldPresenter
	 */
	protected $presenter = null;
	
	/**
	 * @var string - the classname of the DataObject that the GridField will display
	 */
	protected $modelClassName = '';

	/**
	 * Creates a new GridField field
	 *
	 * @param string $name
	 * @param string $title
	 * @param SS_List $datasource
	 * @param Form $form 
	 * @param string $dataPresenterClassName
	 */
	public function __construct($name, $title = null, SS_List $datasource = null, Form $form = null, $dataPresenterClassName = 'GridFieldPresenter') {
		parent::__construct($name, $title, null, $form);
		
		if ($datasource) {
			$this->setDatasource($datasource);
		}
		
		$this->setPresenter($dataPresenterClassName);
	}
	
	/**
	 * @param string $modelClassName 
	 */
	public function setModelClass($modelClassName) {
		$this->modelClassName = $modelClassName;
		
		return $this;
	}
	
	/**
	 * @throws Exception
	 * @return string
	 */
	public function getModelClass() {
		if ($this->modelClassName) {
			return $this->modelClassName;
		}
		if ($this->datasource->dataClass) {
			return $this->datasource->dataClass;
		}
		
		throw new Exception(get_class($this).' does not have a modelClassName');
	}
	
	/**
	 * @param string|GridFieldPresenter
	 *
	 * @throws Exception
	 */
	public function setPresenter($presenter) {
		if(!$presenter){
			throw new Exception('setPresenter() for GridField must be set with a class');
		}
		
		if(is_object($presenter)) {
			$this->presenter = $presenter;
			$this->presenter->setGridField($this);
			
			return;
		}
		
		if(!class_exists($presenter)){
			throw new Exception('DataPresenter for GridField must be set with an existing class, '.$presenter.' does not exists.');
		}
		
		if($presenter !='GridFieldPresenter' && !ClassInfo::is_subclass_of($presenter, 'GridFieldPresenter')) {
			throw new Exception(sprintf(
				'DataPresenter "%s" must subclass GridFieldPresenter', $presenter
			));
		}
		
		$this->presenter = new $presenter;
		$this->presenter->setGridField($this);

		return $this;
	}
	
	/**
	 * @return GridFieldPresenter
	 */
	public function getPresenter(){
		return $this->presenter;
	}
	
	/**
	 * Set the datasource
	 *
	 * @param SS_List $datasource
	 */
	public function setDataSource(SS_List $datasource) {
		$this->datasource = $datasource;
		
		return $this;
	}

	/**
	 * Get the datasource
	 *
	 * @return SS_List
	 */
	public function getDataSource() {
		return $this->datasource;
	}
	
	/**
	 * @return string - html for the form 
	 */
	function FieldHolder() {
		return $this->getPresenter()->render();
	}	
}
	