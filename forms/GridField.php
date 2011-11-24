<?php
/**
 * Displays a {@link SS_List} in a grid format.
 * 
 * GridFIeld is a field that takes an SS_List and displays it in an table with rows 
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
class GridField extends FormField {

	/**
	 * @var SS_List
	 */
	protected $list = null;
	
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
	 * Url handlers
	 *
	 * @var array
	 */
	public static $url_handlers = array(
		'$Action' => '$Action',
	);
	
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
		
		if ($dataList) {
			$this->setList($dataList);
		}
		
		$this->setPresenter($dataPresenterClassName);
	}
	
	/**
	 *
	 * @return string - HTML
	 */
	public function index() {
		return $this->FieldHolder();
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
		if ($this->list->dataClass) {
			return $this->list->dataClass;
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
	 * @return string - html for the form 
	 */
	function FieldHolder() {
		return $this->getPresenter()->render();
	}	
}
	