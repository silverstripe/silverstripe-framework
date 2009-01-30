<?php
/**
 * Special ComplexTableField for editing a many_many relation.
 * @package forms
 * @subpackage fields-relational
 */
class ManyManyComplexTableField extends HasManyComplexTableField {
	
	private $manyManyParentClass;
	
	public $itemClass = 'ManyManyComplexTableField_Item';
		
	function __construct($controller, $name, $sourceClass, $fieldList, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {

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
		
		$this->sourceJoin .= " LEFT JOIN `$manyManyTable` ON (`$source`.`ID` = `{$sourceField}ID` AND `{$this->manyManyParentClass}ID` = '$parentID')";
		
		$this->joinField = 'Checked';
	}
		
	function getQuery() {
		$query = parent::getQuery();
		$query->select[] = "IF(`{$this->manyManyParentClass}ID` IS NULL, '0', '1') AS Checked";
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
		$name = $this->parent->Name() . '[]';
		
		if($this->parent->IsReadOnly)
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if($this->item->{$this->parent->joinField})
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}

?>