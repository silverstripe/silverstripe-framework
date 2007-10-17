<?php

class HasManyComplexTableField extends HasOneComplexTableField {
		
	protected $itemClass = 'HasManyComplexTableField_Item';
		
	function getParentIdName( $parentClass, $childClass ) {
		return $this->getParentIdNameRelation( $childClass, $parentClass, 'has_one' );
	}
		
	function getControllerID() {
		return $this->controller->ID;
	}
	
	function saveInto( DataObject $record ) {
		$fieldName = $this->name;
		$saveDest = $record->$fieldName();
		
		if( ! $saveDest )
			user_error( "HasManyComplexTableField::saveInto() Field '$fieldName' not found on $record->class.$record->ID", E_USER_ERROR );
		
		$items = array();
		
		if($list = $this->value[$this->htmlListField]) {
			if($list != 'undefined') {
				$items = explode(',', $list);
			}
		}
				
		$saveDest->setByIDList( $items );
	}
		
	function ExtraData() {
		$items = array();
		foreach( $this->unpagedSourceItems as $item ) {
			if( $item->{$this->joinField} == $this->controller->ID )
				$items[] = $item->ID;
		}
		$list = implode( ',', $items );
		$inputId = $this->id() . '_' . $this->htmlListEndName;
		return <<<HTML
		<input id="$inputId" name="{$this->name}[{$this->htmlListField}]" type="hidden" value="$list"/>
HTML;
	}
}

class HasManyComplexTableField_Item extends ComplexTableField_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->Name() . '[]';
		
		$joinVal = $this->item->{$this->parent->joinField};
		$parentID = $this->parent->getControllerID();
		
		if( $this->parent->IsReadOnly || ! $this->Can( 'edit' ) || ( $joinVal > 0 && $joinVal != $parentID ) )
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if( $joinVal == $parentID )
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}

?>