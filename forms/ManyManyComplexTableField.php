<?php
/**
 * Special ComplexTableField for editing a many_many relation.
 * 
 * This field  allows you to show a **many-to-many** relation with a group of 
 * DataObjects as a (readonly) tabular list (similiar to {@link ComplexTableField}). 
 * Its most useful when you want to manage the relationship itself 
 * thanks to the check boxes present on each line of the table.
 * 
 * See {@link ComplexTableField} for more documentation on the base-class.
 * See {@link HasManyComplexTableField} for more documentation on the relation table base-class.
 * 
 * Note: This class relies on the fact that both sides of the relation have database tables. 
 * If you are only creating a class as a logical extension (that is, it doesn't have any database fields), 
 * then you will need to create a dummy static $db array because SilverStripe won't create a database 
 * table unless needed.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * $tablefield = new ManyManyComplexTableField(
 *     $this,
 *     'MyFruits',
 *     'Fruit',
 *     array(
 * 	'Name' => 'Name',
 * 	'Color' => 'Color'
 *     ),
 *     'getCMSFields_forPopup'
 * );
 * </code>
 * 
 * @deprecated 3.0 Use GridField with GridFieldConfig_RelationEditor
 * 
 * @package forms
 * @subpackage fields-relational
 */
class ManyManyComplexTableField extends HasManyComplexTableField {
	
	private $manyManyParentClass;
	
	public $itemClass = 'ManyManyComplexTableField_Item';
		
	function __construct($controller, $name, $sourceClass, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {

		Deprecation::notice('3.0', 'Use GridField with GridFieldConfig_RelationEditor', Deprecation::SCOPE_CLASS);

		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);
		
		$classes = array_reverse(ClassInfo::ancestry($this->controllerClass()));
		foreach($classes as $class) {
			$singleton = singleton($class);
			$manyManyRelations = $singleton->uninherited('many_many', true);
			if(isset($manyManyRelations) && array_key_exists($this->name, $manyManyRelations)) {
				$this->manyManyParentClass = $class;
				$manyManyTable = $class . '_' . $this->name;
				break;
			}
			$belongsManyManyRelations = $singleton->uninherited( 'belongs_many_many', true );
			 if( isset( $belongsManyManyRelations ) && array_key_exists( $this->name, $belongsManyManyRelations ) ) {
				$this->manyManyParentClass = $class;
				$manyManyTable = $belongsManyManyRelations[$this->name] . '_' . $this->name;
				break;
			}
		}
		$tableClasses = ClassInfo::dataClassesFor($this->sourceClass);
		$source = array_shift($tableClasses);
		$sourceField = $this->sourceClass;
		if($this->manyManyParentClass == $sourceField)
			$sourceField = 'Child';
		$parentID = $this->controller->ID;
		
		$this->sourceJoin .= " LEFT JOIN \"$manyManyTable\" ON (\"$source\".\"ID\" = \"$manyManyTable\".\"{$sourceField}ID\" AND \"{$this->manyManyParentClass}ID\" = '$parentID')";
		
		$this->joinField = 'Checked';
	}
		
	function getQuery() {
		$query = parent::getQuery();
		$query->selectField("CASE WHEN \"{$this->manyManyParentClass}ID\" IS NULL THEN '0' ELSE '1' END", "Checked");
		$query->groupby[] = "\"{$this->manyManyParentClass}ID\""; // necessary for Postgres

		return $query;
	}
		
	function getParentIdName($parentClass, $childClass) {
		return $this->getParentIdNameRelation($parentClass, $childClass, 'many_many');
	}
}

/**
 * One record in a {@link ManyManyComplexTableField}.
 * @package forms
 * @subpackage fields-relational
 */
class ManyManyComplexTableField_Item extends ComplexTableField_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->getName() . '[]';
		
		if($this->parent->IsReadOnly)
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if($this->item->{$this->parent->joinField})
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}


