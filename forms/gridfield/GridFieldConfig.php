<?php
/**
 * Encapsulates a collection of components following the
 * {@link GridFieldComponent} interface. While the {@link GridField} itself
 * has some configuration in the form of setters, most of the details are
 * dealt with through components.
 *
 * For example, you would add a {@link GridFieldPaginator} component to enable
 * pagination on the listed records, and configure it through
 * {@link GridFieldPaginator->setItemsPerPage()}.
 *
 * In order to reduce the amount of custom code required, the framework
 * provides some default configurations for common use cases:
 *
 * - {@link GridFieldConfig_Base} (added by default to GridField)
 * - {@link GridFieldConfig_RecordEditor}
 * - {@link GridFieldConfig_RelationEditor}
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig extends Object {

	/**
	 * @var ArrayList
	 */
	protected $components = null;


	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->components = new ArrayList();
	}

	/**
	 * @param GridFieldComponent $component
	 * @param string $insertBefore The class of the component to insert this one before
	 */
	public function addComponent(GridFieldComponent $component, $insertBefore = null) {
		if($insertBefore) {
			$existingItems = $this->getComponents();
			$this->components = new ArrayList;
			$inserted = false;
			foreach($existingItems as $existingItem) {
				if(!$inserted && $existingItem instanceof $insertBefore) {
					$this->components->push($component);
					$inserted = true;
				}
				$this->components->push($existingItem);
			}
			if(!$inserted) $this->components->push($component);
		} else {
			$this->getComponents()->push($component);
		}
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
 * A simple readonly, paginated view of records, with sortable and searchable
 * headers.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig_Base extends GridFieldConfig {

	/**
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		parent::__construct();
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());
		$this->addComponent(new GridFieldDataColumns());
		$this->addComponent(new GridFieldPageCount('toolbar-header-right'));
		$this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));

		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		$pagination->setThrowExceptionOnBadDataType(false);

		$this->extend('updateConfig');
	}
}

/**
 * Allows viewing readonly details of individual records.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig_RecordViewer extends GridFieldConfig_Base {

	public function __construct($itemsPerPage = null) {
		parent::__construct($itemsPerPage);

		$this->addComponent(new GridFieldViewButton());
		$this->addComponent(new GridFieldDetailForm());

		$this->extend('updateConfig');
	}

}

/**
 * Allows editing of records contained within the GridField, instead of only allowing the ability to view records in
 * the GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig_RecordEditor extends GridFieldConfig {
	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		parent::__construct();

		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());
		$this->addComponent(new GridFieldDataColumns());
		$this->addComponent(new GridFieldEditButton());
		$this->addComponent(new GridFieldDeleteAction());
		$this->addComponent(new GridFieldPageCount('toolbar-header-right'));
		$this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
		$this->addComponent(new GridFieldDetailForm());

		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		$pagination->setThrowExceptionOnBadDataType(false);

		$this->extend('updateConfig');
	}
}


/**
 * Similar to {@link GridFieldConfig_RecordEditor}, but adds features to work
 * on has-many or many-many relationships.
 *
 * Allows to search for existing records to add to the relationship, detach
 * listed records from the relationship (rather than removing them from the
 * database), and automatically add newly created records to it.
 *
 * To further configure the field, use {@link getComponentByType()}, for
 * example to change the field to search.
 *
 * <code>
 * GridFieldConfig_RelationEditor::create()
 * 	->getComponentByType('GridFieldAddExistingAutocompleter')
 * 	->setSearchFields('MyField');
 * </code>
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig_RelationEditor extends GridFieldConfig {

	/**
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		parent::__construct();

		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
		$this->addComponent(new GridFieldAddExistingAutocompleter('buttons-before-right'));
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());
		$this->addComponent(new GridFieldDataColumns());
		$this->addComponent(new GridFieldEditButton());
		$this->addComponent(new GridFieldDeleteAction(true));
		$this->addComponent(new GridFieldPageCount('toolbar-header-right'));
		$this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
		$this->addComponent(new GridFieldDetailForm());

		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		$pagination->setThrowExceptionOnBadDataType(false);

		$this->extend('updateConfig');
	}
}
