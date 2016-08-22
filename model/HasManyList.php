<?php

/**
 * Subclass of {@link DataList} representing a has_many relation.
 *
 * @package framework
 * @subpackage model
 */
class HasManyList extends RelationList {

	/**
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * Create a new HasManyList object.
	 * Generation of the appropriate record set is left up to the caller, using the normal
	 * {@link DataList} methods.  Addition arguments are used to support {@@link add()}
	 * and {@link remove()} methods.
	 *
	 * @param string $dataClass The class of the DataObjects that this will list.
	 * @param string $foreignKey The name of the foreign key field to set the ID filter against.
	 */
	public function __construct($dataClass, $foreignKey) {
		parent::__construct($dataClass);

		$this->foreignKey = $foreignKey;
	}

	/**
	 * Gets the field name which holds the related object ID.
	 *
	 * @return string
	 */
	public function getForeignKey() {
		return $this->foreignKey;
	}

	/**
	 * @param null|int $id
	 * @return array
	 */
	protected function foreignIDFilter($id = null) {
		if ($id === null) $id = $this->getForeignID();

		// Apply relation filter
		$key = "\"$this->foreignKey\"";
		if(is_array($id)) {
			return array("$key IN (".DB::placeholders($id).")"  => $id);
		} else if($id !== null){
			return array($key => $id);
		}
	}

	/**
	 * Adds the item to this relation.
	 *
	 * It does so by setting the relationFilters.
	 *
	 * @param DataObject|int $item The DataObject to be added, or its ID
	 */
	public function add($item) {
		if(is_numeric($item)) {
			$item = DataObject::get_by_id($this->dataClass, $item);
		} else if(!($item instanceof $this->dataClass)) {
			user_error("HasManyList::add() expecting a $this->dataClass object, or ID value", E_USER_ERROR);
		}

		$foreignID = $this->getForeignID();

		// Validate foreignID
		if(!$foreignID) {
			user_error("ManyManyList::add() can't be called until a foreign ID is set", E_USER_WARNING);
			return;
		}
		if(is_array($foreignID)) {
			user_error("ManyManyList::add() can't be called on a list linked to mulitple foreign IDs", E_USER_WARNING);
			return;
		}

		$foreignKey = $this->foreignKey;
		$item->$foreignKey = $foreignID;

		$item->write();
	}

	/**
	 * Remove an item from this relation.
	 *
	 * Doesn't actually remove the item, it just clears the foreign key value.
	 *
	 * @param int $itemID The ID of the item to be removed.
	 */
	public function removeByID($itemID) {
		$item = $this->byID($itemID);

		return $this->remove($item);
	}

	/**
	 * Remove an item from this relation.
	 * Doesn't actually remove the item, it just clears the foreign key value.
	 *
	 * @param DataObject $item The DataObject to be removed
	 * @todo Maybe we should delete the object instead?
	 */
	public function remove($item) {
		if(!($item instanceof $this->dataClass)) {
			throw new InvalidArgumentException("HasManyList::remove() expecting a $this->dataClass object, or ID",
				E_USER_ERROR);
		}

		// Don't remove item which doesn't belong to this list
		$foreignID = $this->getForeignID();
		$foreignKey = $this->getForeignKey();

		if(	empty($foreignID)
			|| (is_array($foreignID) && in_array($item->$foreignKey, $foreignID))
			|| $foreignID == $item->$foreignKey
		) {
			$item->$foreignKey = null;
			$item->write();
		}

	}
}
