<?php
/**
 * This is a special kind of DataObjectSet used to represent the items linked to in a 1-many or many-many
 * join.  It provides add and remove methods that will update the database.
 * @package sapphire
 * @subpackage model
 */
class ComponentSet extends DataObjectSet {
	/**
	 * Type of relationship (eg '1-1', '1-many').
	 * @var string
	 */
	protected $type;
	
	/**
	 * Object that owns this set.
	 * @var DataObject
	 */
	protected $ownerObj;
	
	/**
	 * Class of object that owns this set.
	 * @var string
	 */
	protected $ownerClass;
	
	/**
	 * Table that holds this relationship.
	 * @var string
	 */
	protected $tableName;
	
	/**
	 * Class of child side of the relationship.
	 * @var string
	 */
	protected $childClass;
	
	/**
	 * Field to join on.
	 * @var string
	 */
	protected $joinField;
	
	/**
	 * Set the ComponentSet specific information.
	 * @param string $type Type of relationship (eg '1-1', '1-many').
	 * @param DataObject $ownerObj Object that owns this set.
	 * @param string $ownerClass Class of object that owns this set.
	 * @param string $tableName Table that holds this relationship.
	 * @param string $childClass Class of child side of the relationship.
	 * @param string $joinField Field to join on.
	 */
	function setComponentInfo($type, $ownerObj, $ownerClass, $tableName, $childClass, $joinField = null) {
		$this->type = $type;
		$this->ownerObj = $ownerObj;
		$this->ownerClass = $ownerClass ? $ownerClass : $ownerObj->class;
		$this->tableName = $tableName;
		$this->childClass = $childClass;
		$this->joinField = $joinField;
	}
	
	/**
	 * Get the ComponentSet specific information
	 * 
	 * Returns an array on the format array( 
	 * 		'type' => <string>, 
	 * 		'ownerObj' => <Object>, 
	 * 		'ownerClass' => <string>, 
	 * 		'tableName' => <string>, 
	 * 		'childClass' => <string>, 
	 * 		'joinField' => <string>|null );
	 * 
	 * @return array
	 */
	public function getComponentInfo() {
		return array( 
			'type' => $this->type,
			'ownerObj' => $this->ownerObj,
			'ownerClass' => $this->ownerClass,
			'tableName' => $this->tableName,
			'childClass' => $this->childClass,
			'joinField' => $this->joinField 
		);
	}
		
	/**
	 * Get an array of all the IDs in this component set, where the keys are the same as the
	 * values.
	 * @return array
	 */
	function getIdList() {
		$list = array();
		foreach($this->items as $item) {
			$list[$item->ID] = $item->ID;
		}
		return $list;
	}
		
	/**
	 * Add an item to this set.
	 * @param DataObject|int|string $item Item to add, either as a DataObject or as the ID.
	 * @param array $extraFields A map of extra fields to add.
	 */
	function add($item, $extraFields = null) {
		if(!isset($item)) {
			user_error("ComponentSet::add() Not passed an object or ID", E_USER_ERROR);
		}
		
		if(is_object($item)) {
			if(!is_a($item, $this->childClass)) {
				user_error("ComponentSet::add() Tried to add an '{$item->class}' object, but a '{$this->childClass}' object expected", E_USER_ERROR);
			}
		} else {
			if(!$this->childClass) {
				user_error("ComponentSet::add() \$this->childClass not set", E_USER_ERROR);
			}
			
			$item = DataObject::get_by_id($this->childClass, $item);
			if(!$item) return;
		}

		// If we've already got a database object, then update the database
		if($this->ownerObj->ID && is_numeric($this->ownerObj->ID)) {
			$this->loadChildIntoDatabase($item, $extraFields);
		}
		
		// In either case, add something to $this->items
		$this->items[] = $item;
	}
	
	/**
	 * Method to save many-many join data into the database for the given $item.
	 * Used by add() and write().
	 * @param DataObject|string|int The item to save, as either a DataObject or the ID.
	 * @param array $extraFields Map of extra fields.
	 */
	protected function loadChildIntoDatabase($item, $extraFields = null) {
		if($this->type == '1-to-many') {
			$child = DataObject::get_by_id($this->childClass,$item->ID);
			if (!$child) $child = $item;
			$joinField = $this->joinField;
			$child->$joinField = $this->ownerObj->ID;
			$child->write();
			
		} else {		
			$parentField = $this->ownerClass . 'ID';
			$childField = ($this->childClass == $this->ownerClass) ? "ChildID" : ($this->childClass . 'ID');
			
			DB::query( "DELETE FROM \"$this->tableName\" WHERE \"$parentField\" = {$this->ownerObj->ID} AND \"$childField\" = {$item->ID}" );
			
			$extraKeys = $extraValues = '';
			if($extraFields) foreach($extraFields as $k => $v) {
				$extraKeys .= ", \"$k\"";
				$extraValues .= ", '" . DB::getConn()->addslashes($v) . "'";
			}

			DB::query("INSERT INTO \"$this->tableName\" (\"$parentField\",\"$childField\" $extraKeys) VALUES ({$this->ownerObj->ID}, {$item->ID} $extraValues)");
		}
	}
    	
	/**
	 * Add a number of items to the component set.
	 * @param array $items Items to add, as either DataObjects or IDs.
	 */
	function addMany($items) {
		foreach($items as $item) {
			$this->add($item);
		}
	}
	
	/**
	 * Sets the ComponentSet to be the given ID list.
	 * Records will be added and deleted as appropriate.
	 * @param array $idList List of IDs.
	 */
	function setByIDList($idList) {
		$has = array();
		// Index current data
		if($this->items) foreach($this->items as $item) {
		   $has[$item->ID] = true;
		}
		
		// Keep track of items to delete
		$itemsToDelete = $has;
		
		// add items in the list
		// $id is the database ID of the record
		if($idList) foreach($idList as $id) {
			$itemsToDelete[$id] = false;
			if($id && !isset($has[$id])) $this->add($id);
		}

		// delete items not in the list
		$removeList = array();
		foreach($itemsToDelete as $id => $actuallyDelete) {
			if($actuallyDelete) $removeList[] = $id;
		}
		$this->removeMany($removeList);
	}
	
	/**
	 * Remove an item from this set.
	 *
	 * @param DataObject|string|int $item Item to remove, either as a DataObject or as the ID.
	 */
	function remove($item) {
		if(is_object($item)) {
			if(!is_a($item, $this->childClass)) {
				user_error("ComponentSet::remove() Tried to remove an '{$item->class}' object, but a '{$this->childClass}' object expected", E_USER_ERROR);
			}
		} else {
			$item = DataObject::get_by_id($this->childClass, $item);
		}

		// Manipulate the database, if it's in there
		if($this->ownerObj->ID && is_numeric($this->ownerObj->ID)) {
			if($this->type == '1-to-many') {
				$child = DataObject::get_by_id($this->childClass,$item->ID);
				$joinField = $this->joinField;
				if($child->$joinField == $this->ownerObj->ID) {
					$child->$joinField = null;
					$child->write();
				}
				
			} else {
				$parentField = $this->ownerClass . 'ID';
				$childField = ($this->childClass == $this->ownerClass) ? "ChildID" : ($this->childClass . 'ID');
				DB::query("DELETE FROM \"$this->tableName\" WHERE \"$parentField\" = {$this->ownerObj->ID} AND \"$childField\" = {$item->ID}");
			}
		}
		
		// Manipulate the in-memory array of items
		if($this->items) foreach($this->items as $i => $candidateItem) {
			if($candidateItem->ID == $item->ID) {
				unset($this->items[$i]);
				break;
			}
		}
	}
	
	/**
	 * Remove many items from this set.
	 * @param array $itemList The items to remove, as a numerical array with IDs or as a DataObjectSet
	 */
	function removeMany($itemList) {
		if(!count($itemList)) return false;
		
		if($this->type == '1-to-many') {
			foreach($itemList as $item) $this->remove($item);
		} else {
			$itemCSV = implode(", ", $itemList);
			$parentField = $this->ownerClass . 'ID';
			$childField = ($this->childClass == $this->ownerClass) ? "ChildID" : ($this->childClass . 'ID');
			DB::query("DELETE FROM \"$this->tableName\" WHERE \"$parentField\" = {$this->ownerObj->ID} AND \"$childField\" IN ($itemCSV)");
		}
	}

	/**
	 * Remove all items in this set.
	 */
	function removeAll() {
		if(!empty($this->tableName)) {
			$parentField = $this->ownerClass . 'ID';
			DB::query("DELETE FROM \"$this->tableName\" WHERE \"$parentField\" = {$this->ownerObj->ID}");
		} else {
			foreach($this->items as $item) {
				$this->remove($item);
			}
		}
	}
	
	/**
	 * Write this set to the database.
	 * Called by DataObject::write().
	 * @param boolean $firstWrite This should be set to true if it the first time the set is being written.
	 */
	function write($firstWrite = false) {
		if($firstWrite) {
			foreach($this->items as $item) {
				$this->loadChildIntoDatabase($item);
			}
		}
	}
	
	/**
	 * Returns information about this set in HTML format for debugging.
	 * 
	 * @return string
	 */
	function debug() {
		$size = count($this->items);
		
		$output = <<<OUT
<h3>ComponentSet</h3>
<ul>
	<li>Type: {$this->type}</li>
	<li>Size: $size</li>
</ul>

OUT;

		return $output;
	}
}

?>
