<?php

/**
 * Subclass of {@link DataList} representing a has_many relation
 */
class HasManyList extends RelationList {
	protected $foreignKey;
	
	/**
	 * Create a new HasManyList object.
	 * Generation of the appropriate record set is left up to the caller, using the normal
	 * {@link DataList} methods.  Addition arguments are used to support {@@link add()}
	 * and {@link remove()} methods.
	 * 
	 * @param $dataClass The class of the DataObjects that this will list.
	 * @param $relationFilters A map of key => value filters that define which records
	 * in the $dataClass table actually belong to this relationship.
	 */
	function __construct($dataClass, $foreignKey) {
		parent::__construct($dataClass);
		$this->foreignKey = $foreignKey;
	}
	
	protected function foreignIDFilter() {
		// Apply relation filter
		if(is_array($this->foreignID)) {
			return "\"$this->foreignKey\" IN ('" . 
				implode("', '", array_map('Convert::raw2sql', $this->foreignID)) . "')";
		} else if($this->foreignID !== null){
			return "\"$this->foreignKey\" = '" . 
				Convert::raw2sql($this->foreignID) . "'";
		}
	}

	/**
	 * Adds the item to this relation.
	 * It does so by setting the relationFilters.
	 * @param $item The DataObject to be added, or its ID 
	 */
	function add($item) {
		if(is_numeric($item)) $item = DataObject::get_by_id($this->dataClass, $item);
		else if(!($item instanceof $this->dataClass)) user_error("HasManyList::add() expecting a $this->dataClass object, or ID value", E_USER_ERROR);

		// Validate foreignID
		if(!$this->foreignID) {
			user_error("ManyManyList::add() can't be called until a foreign ID is set", E_USER_WARNING);
			return;
		}
		if(is_array($this->foreignID)) {
			user_error("ManyManyList::add() can't be called on a list linked to mulitple foreign IDs", E_USER_WARNING);
			return;
		}

		$fk = $this->foreignKey;
		$item->$fk = $this->foreignID;

		$item->write();
	}

	/**
	 * Remove an item from this relation.
	 * Doesn't actually remove the item, it just clears the foreign key value.
	 * @param $itemID The ID of the item to be removed
	 */
	function removeByID($itemID) {
        $item = $this->byID($itemID);
        return $this->remove($item);
    }
    
	/**
	 * Remove an item from this relation.
	 * Doesn't actually remove the item, it just clears the foreign key value.
	 * @param $item The DataObject to be removed
	 * @todo Maybe we should delete the object instead? 
	 */
	function remove($item) {
        if(!($item instanceof $this->dataClass)) throw new InvalidArgumentException("HasManyList::remove() expecting a $this->dataClass object, or ID value", E_USER_ERROR);

		$fk = $this->foreignKey;
		$item->$fk = null;

		$item->write();
	}
	
}
