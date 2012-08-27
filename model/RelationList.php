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
	 *
	 * This is the mutatable version of this function, and will be protected only
	 * from 3.1. Use forForeignID instead
	 *
	 * @param $id A single ID, or an array of IDs
	 */
	function setForeignID($id) {
		// If already filtered on foreign ID, remove that first
		if($this->foreignID !== null) {
			$oldFilter = $this->foreignIDFilter();
			try {
				$this->dataQuery->removeFilterOn($oldFilter);	
			}
			catch(InvalidArgumentException $e) { /* NOP */ }
		}

		// Turn a 1-element array into a simple value
		if(is_array($id) && sizeof($id) == 1) $id = reset($id);
		$this->foreignID = $id;
		
		$this->dataQuery->where($this->foreignIDFilter());

		return $this;
	}
	
	/**
	 * Returns a copy of this list with the ManyMany relationship linked to the given foreign ID.
	 * @param $id An ID or an array of IDs.
	 */
	function forForeignID($id) {
		return $this->alterDataQuery_30(function($query, $list) use ($id){
			$list->setForeignID($id);
		});
	}
	
	abstract protected function foreignIDFilter();
}
