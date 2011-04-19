<?php

/**
 * A DataList that represents a relation.
 * Adds the notion of a foreign ID that can be optionally set.
 * 
 * @todo Is this additional class really necessary?
 */
abstract class RelationList extends DataList {
	protected $foreignID;
	
	/**
	 * Set the ID of the record that this ManyManyList is linking *from*.
	 * @param $id A single ID, or an array of IDs
	 */
	function setForeignID($id) {
		// Turn a 1-element array into a simple value
		if(is_array($id) && sizeof($id) == 1) $id = reset($id);
		$this->foreignID = $id;
		
		$this->dataQuery->where($this->foreignIDFilter());
	}
	
	/**
	 * Returns this ManyMany relationship linked to the given foreign ID.
	 * @param $id An ID or an array of IDs.
	 */
	function forForeignID($id) {
		$this->setForeignID($id);
		return $this;
	}
	
	abstract protected function foreignIDFilter();
}