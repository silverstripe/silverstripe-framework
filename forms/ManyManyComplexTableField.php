<?php

class ManyManyComplexTableField extends HasManyComplexTableField {
	
	protected $itemClass = 'ManyManyComplexTableField_Item';
	
	function __construct( $controller, $name, $sourceClass, $fieldList, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {

		parent::__construct( $controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin );
		
		$parent = $this->controller->ClassName;
		$manyManyTable = $parent . '_' . $this->name;
		$source = $this->sourceClass;
		$parentID = $this->controller->ID;
		
		$this->sourceJoin .= " LEFT JOIN `$manyManyTable` ON ( `$source`.`ID` = `{$source}ID` AND `{$parent}ID` = '$parentID' )";
		
		$this->joinField = 'Checked';
	}
		
	function getQuery( $limitClause = null ) {
		if( $this->customQuery ) {
			$query = $this->customQuery;
			$query->select[] = "{$this->sourceClass}.ID AS ID";
			$query->select[] = "{$this->sourceClass}.ClassName AS ClassName";
			$query->select[] = "{$this->sourceClass}.ClassName AS RecordClassName";
		}
		else {
			$query = singleton( $this->sourceClass )->extendedSQL( $this->sourceFilter, $this->sourceSort, $limitClause, $this->sourceJoin );
			
			// Add more selected fields if they are from joined table.

			$SNG = singleton( $this->sourceClass );
			foreach( $this->FieldList() as $k => $title ) {
				if( ! $SNG->hasField( $k ) && ! $SNG->hasMethod( 'get' . $k ) )
					$query->select[] = $k;
			}
			$parent = $this->controller->ClassName;
			$query->select[] = "IF(`{$parent}ID` IS NULL, '0', '1') AS $this->joinField";
		}
		return clone $query;
	}
		
	function getParentIdName( $parentClass, $childClass ) {
		return $this->getParentIdNameRelation( $parentClass, $childClass, 'many_many' );
	}
			
	function ExtraData() {
		$items = array();
		foreach( $this->unpagedSourceItems as $item ) {
			if( $item->{$this->joinField} )
				$items[] = $item->ID;
		}
		$list = implode( ',', $items );
		$inputId = $this->id() . '_' . $this->htmlListEndName;
		return <<<HTML
		<input id="$inputId" name="{$this->name}[{$this->htmlListField}]" type="hidden" value="$list"/>
HTML;
	}
}

class ManyManyComplexTableField_Item extends ComplexTableField_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->Name() . '[]';
		
		if( $this->parent->IsReadOnly || ! $this->Can( 'edit' ) )
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if( $this->item->{$this->parent->joinField} )
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}

?>