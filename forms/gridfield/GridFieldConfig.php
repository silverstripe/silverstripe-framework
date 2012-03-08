<?php
/**
 * Encapsulates a collection of components following the {@link GridFieldComponent} interface.
 * While the {@link GridField} itself has some configuration in the form of setters,
 * most of the details are dealt with through components.
 * 
 * For example, you would add a {@link GridFieldPaginator} component to enable
 * pagination on the listed records, and configure it through {@link GridFieldPaginator->setItemsPerPage()}.
 * 
 * In order to reduce the amount of custom code required, the framework provides
 * some default configurations for common use cases:
 * - {@link GridFieldConfig_Base} (added by default to GridField)
 * - {@link GridFieldConfig_RecordEditor}
 * - {@link GridFieldConfig_RelationEditor}
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
		$this->components = new ArrayList();
	}
	
	/**
	 * @param GridFieldComponent $component 
	 */
	public function addComponent(GridFieldComponent $component) {
		$this->getComponents()->push($component);
		return $this;
	}

	/**
	 * @param GridFieldComponent One or more components
	 */
	public function addComponents() {
		$components = func_get_args();
		foreach($components as $component) $this->addComponent($component);
		return $this;
	}
	
	/**
	 * @param GridFieldComponent $component 
	 * @return GridFieldConfig $this
	 */
	public function removeComponent(GridFieldComponent $component) {
		$this->getComponents()->remove($component);
		return $this;	
	}
	
	/**
	 * @param String Class name or interface
	 * @return GridFieldConfig $this
	 */
	public function removeComponentsByType($type) {
		$components = $this->getComponentsByType($type);
		foreach($components as $component) {
			$this->removeComponent($component);
		}
		return $this;
	}
	
	/**
	 * @return ArrayList Of GridFieldComponent
	 */
	public function getComponents() {
		if(!$this->components) {
			$this->components = new ArrayList();
		}
		return $this->components;
	}

	/**
	 * Returns all components extending a certain class, or implementing a certain interface.
	 * 
	 * @param String Class name or interface
	 * @return ArrayList Of GridFieldComponent
	 */
	public function getComponentsByType($type) {
		$components = new ArrayList();
		foreach($this->components as $component) {
			if($component instanceof $type) $components->push($component);
		}
		return $components;
	}

	/**
	 * Returns the first available component with the given class or interface.
	 * 
	 * @param String ClassName
	 * @return GridFieldComponent
	 */
	public function getComponentByType($type) {
		foreach($this->components as $component) {
			if($component instanceof $type) return $component;
		}
	}
}

/**
 * A simple readonly, paginated view of records,
 * with sortable and searchable headers.
 */
class GridFieldConfig_Base extends GridFieldConfig {

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up per page
	 * @return GridFieldConfig_Base
	 */
	public static function create($itemsPerPage=null){
		return new GridFieldConfig_Base($itemsPerPage);
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		$this->addComponent(new GridFieldTitle());
		$this->addComponent(new GridFieldSortableHeader());
		$this->addComponent(new GridFieldFilter());
		$this->addComponent(new GridFieldDefaultColumns());
		$this->addComponent(new GridFieldPaginator($itemsPerPage));
	}
}

/**
 * 
 */
class GridFieldConfig_RecordEditor extends GridFieldConfig {

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 * @return GridFieldConfig_RecordEditor
	 */
	public static function create($itemsPerPage=null){
		return new GridFieldConfig_RecordEditor($itemsPerPage);
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		$this->addComponent(new GridFieldTitle());
		$this->addComponent(new GridFieldSortableHeader());
		$this->addComponent(new GridFieldFilter());
		$this->addComponent(new GridFieldDefaultColumns());
		$this->addComponent(new GridFieldEditAction());
		$this->addComponent(new GridFieldDeleteAction());
		$this->addComponent(new GridFieldPaginator($itemsPerPage));
		$this->addComponent(new GridFieldPopupForms());
	}
}


/**
 * Similar to {@link GridFieldConfig_RecordEditor}, but adds features
 * to work on has-many or many-many relationships. 
 * Allows to search for existing records to add to the relationship,
 * detach listed records from the relationship (rather than removing them from the database),
 * and automatically add newly created records to it.
 * 
 * To further configure the field, use {@link getComponentByType()},
 * for example to change the field to search.
 * <code>
 * GridFieldConfig_RelationEditor::create()
 * 	->getComponentByType('GridFieldRelationAdd')->setSearchFields('MyField');
 * </code>
 */
class GridFieldConfig_RelationEditor extends GridFieldConfig {

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 * @return GridFieldConfig_RelationEditor
	 */
	public static function create($itemsPerPage=null){
		return new GridFieldConfig_RelationEditor($itemsPerPage);
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		$this->addComponent(new GridFieldTitle());
		$this->addComponent(new GridFieldRelationAdd());
		$this->addComponent(new GridFieldSortableHeader());
		$this->addComponent(new GridFieldFilter());
		$this->addComponent(new GridFieldDefaultColumns());
		$this->addComponent(new GridFieldEditAction());
		$this->addComponent(new GridFieldRelationDelete());
		$this->addComponent(new GridFieldPaginator($itemsPerPage));
		$this->addComponent(new GridFieldPopupForms());
	}
}
