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
}

class GridFieldConfig_Base extends GridFieldConfig {

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up per page
	 * @return GridFieldConfig_Base
	 */
	public static function create($itemsPerPage=25){
		return new GridFieldConfig_Base($itemsPerPage=25);
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=25) {
		$this->addComponent(new GridFieldSortableHeader());
		$this->addComponent(new GridFieldDefaultColumns());
		$this->addComponent(new GridFieldAction_Edit());
		$this->addComponent(new GridFieldPaginator($itemsPerPage));
	}
}

/**
 * This GridFieldConfig bundles a common set of componentes  used for displaying
 * a gridfield with:
 * 
 * - Relation adding
 * - Sortable header
 * - Default columns
 * - Edit links on every item
 * - Action for removing relationship
 * - Paginator
 * 
 */
class GridFieldConfig_ManyManyEditor extends GridFieldConfig {

	/**
	 *
	 * @param string $fieldToSearch - Which field on the object should be searched for
	 * @param bool $autoSuggest - Show a jquery.ui.autosuggest dropdown field
	 * @param int $itemsPerPage - How many items per page should show up
	 * @return GridFieldConfig_ManyManyEditor
	 */
	public static function create($fieldToSearch, $autoSuggest=true, $itemsPerPage=25){
		return new GridFieldConfig_ManyManyEditor($fieldToSearch, $autoSuggest=true, $itemsPerPage=25);
	}

	/**
	 *
	 * @param string $fieldToSearch - Which field on the object should be searched for
	 * @param bool $autoSuggest - Show a jquery.ui.autosuggest dropdown field
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($fieldToSearch, $autoSuggest=true, $itemsPerPage=25) {
		$this->addComponent(new GridFieldFilter());
		$this->addComponent(new GridFieldRelationAdd($fieldToSearch, $autoSuggest));
		$this->addComponent(new GridFieldSortableHeader());
		$this->addComponent(new GridFieldDefaultColumns());
		$this->addComponent(new GridFieldAction_Edit());
		$this->addComponent(new GridFieldRelationDelete());
		$this->addComponent(new GridFieldPaginator($itemsPerPage));
	}
}
