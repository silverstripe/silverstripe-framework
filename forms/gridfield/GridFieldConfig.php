<?php
/**
 * Description of GridFieldConfig
 *
 */
class GridFieldConfig {
	
	/**
	 *
	 * @return GridFieldConfig 
	 */
	public static function create(){
		return new GridFieldConfig();
	}
	
	/**
	 *
	 * @var ArrayList
	 */
	protected $components = null;
	
	/**
	 *
	 * @var int
	 */
	protected $checkboxes = null;

	/**
	 *
	 * @var array
	 */
	protected $affectors = array();

	/**
	 *
	 * @var array
	 */
	protected $decorators = array();
	
	/**
	 * 
	 */
	public function __construct() {
		;
	}
	
	public function addComponent(GridFieldComponent $component) {
		$this->getComponents()->push($component);
		return $this;
	}
	
	/**
	 *
	 * @return ArrayList
	 */
	public function getComponents() {
		if(!$this->components) {
			$this->components = new ArrayList();
		}
		return $this->components;
	}
	
	public function setCheckboxes($row=0){
		$this->checkboxes = $row;
		return $this;
	}
	
	public function getCheckboxes() {
		return $this->checkboxes;
	}
	
	public function addAffector(GridState_Affector $affector) {
		$this->affectors[] = $affector;
		return $this;
	}
	
	public function getAffectors() {
		return $this->affectors;
	}
	
	public function addDecorator($decorator) {
		$this->decorators[] = $decorator;
	}
	
	public function getDecorators() {
		return $this->decorators;
	}
}
