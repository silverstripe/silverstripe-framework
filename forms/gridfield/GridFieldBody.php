<?php
/**
 * The GridFieldPresenter is responsible for rendering and attach user behaviour
 * to a GridField.
 * 
 * You can create a GridFieldPresenter and inject that into a GridField to 
 * customise look and feel of GridField.
 * 
 * It also have the possibility to let extensions to modify the look and feel of
 * the GridField if you dont want to make a fully blown GridFieldPresenter.
 * 
 * In the following example we configure the GridField to sort the DataList in 
 * the GridField by Title. This will override the sorting on the DataList.
 * 
 * <code>
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'));
 * $gridField->getState()->Sort = array('Title' => 'desc');
 * </code>
 * 
 * Another example is to change the template for the rendering 
 *
 * <code>
 * $presenter = new GridFieldPresenter();
 * $presenter->setTemplate('MyNiftyGridTemplate');
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'),null, $presenter);
 * </code>
 * 
 * There is also a possibility to add extensions to the GridPresenter. An 
 * example is the DataGridPagination that decorates the GridField with 
 * pagination. Look in the GridFieldPresenter::Items() and the filterList extend
 * and GridFieldPresenter::Footers()
 * 
 * <code>
 * GridFieldPresenter::add_extension('GridFieldPaginator_Extension');
 * $presenter = new GridFieldPresenter();
 * // This is actually calling GridFieldPaginator_Extension::paginationLimit()
 * $presenter->paginationLimit(3);
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'),null, $presenter);
 * </code>
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldBody extends GridFieldRowSet {
	
	/**
	 * Location in the gridfield to renders this RowSet
	 *
	 * @var string
	 */
	public static $location = 'body';

	/**
	 * Template override
	 * 
	 * @var string $template 
	 */
	protected $template = 'GridFieldPresenter';
	
	/**
	 * Class name for each item/row
	 * 
	 * @var string $itemClass
	 */
	protected $itemClass = 'GridFieldBody_Row';

	
	/**
	 *
	 * @param GridField $gridField 
	 */
	public function __construct(GridField $gridField) {
		parent::__construct($gridField, 'GridFieldBody');
	}

	/**
	 * Set the item class that the GridFieldBody will use to render individual
	 *
	 * @param string $name 
	 */
	public function setItemClass($name) {
		$this->itemClass = $name;
	}
	
	/**
	 *
	 * @param string $name 
	 */
	public function getItemClass() {
		return $this->itemClass;
	}
	
	/**
	 * Set up the children field prior to rendering
	 * 
	 * @return void
	 */
	function generateChildren() {
		$list = $this->getGridField()->getList();

		$numberOfRows = $list->count();
		$counter = 0;
		foreach($list as $item) {
			$itemField = new $this->itemClass($item, $this);
			$itemField->iteratorProperties($counter++, $numberOfRows);
			$this->push($itemField);
		}
	}
}

/**
 * A single record in a GridField.
 *
 * @package sapphire
 * @see GridField
 */
class GridFieldBody_Row extends FormField {
	
	/**
	 * @var Object The underlying record, usually an element of {@link GridField->datasource()}.
	 */
	protected $item;
	
	/** 
	 * @var GridFieldBody 
	 */
	protected $parent;
	
	/**
	 * @param Object $item
	 * @param GridFieldPresenter $parent 
	 */
	public function __construct($item, $parent) {
		$this->failover = $this->item = $item;
		$this->parent = $parent;
		
		parent::__construct('Row'.$item->ID);
	}
	
	/**
	 * @return int
	 */
	public function ID() {
		return $this->item->ID;
	}
	
	/**
	 * @return GridFieldBody
	 */
	public function Parent() {
		return $this->parent;
	}

	/**
	 * @return GridField
	 */
	public function getGridField() {
		return $this->parent->getGridField();
	}
	
	/**
	 * Get the item that this class represents
	 *
	 * @return Object
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * 
	 * @return ArrayList
	 * @todo Should be refactored and cleaned up and possibly extracted so overloading is easier
	 */
	public function Fields() {
		$xmlSafe = true;

		$list = $this->GridField->DisplayFields;
		$counter = 0;
		
		foreach($list as $fieldName => $fieldTitle) {
			$value = "";

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
			if(array_key_exists($fieldName, $this->GridField->FieldCasting)) {
				$value = $this->parent->getCastedValue($value, $this->GridField->FieldCasting[$fieldName]);
			} elseif(is_object($value) && method_exists($value, 'Nice')) {
				$value = $value->Nice();
			}
			
			// formatting
			$item = $this->item;
			if(array_key_exists($fieldName, $this->GridField->FieldFormatting)) {
				$format = str_replace('$value', "__VAL__", $this->GridField->FieldFormatting[$fieldName]);
				$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$item->$1', $format);
				$format = str_replace('__VAL__', '$value', $format);
				eval('$value = "' . $format . '";');
			}
			
			//escape
			if($escape = $this->GridField->FieldEscape){
				foreach($escape as $search => $replace){
					$value = str_replace($search, $replace, $value);
				}
			}
			
			$arrayData = new ArrayData(array(
				"Name" => $fieldName, 
				"Title" => $fieldTitle,
				"Value" => $value
			));
			$arrayData->iteratorProperties($counter++, count($list));
			$fields[] = $arrayData;
		}
		
		return new ArrayList($fields);
	}

	/**
	 *
	 * @return string  - string
	 */
	public function forTemplate() {
		return $this->renderWith('GridField_Item');
	}
}
