<?php

/**
 * Subclass of {@link DataList} representing a many_many relation.
 *
 * @package framework
 * @subpackage model
 */
class ManyManyList extends RelationList {
	
	protected $joinTable;
	
	protected $localKey;
	
	protected $foreignKey;

	protected $extraFields;

	/**
	 * Create a new ManyManyList object.
	 * 
	 * A ManyManyList object represents a list of DataObject records that correspond to a many-many
	 * relationship.  In addition to, 
	 * 
	 * Generation of the appropriate record set is left up to the caller, using the normal
	 * {@link DataList} methods.  Addition arguments are used to support {@@link add()}
	 * and {@link remove()} methods.
	 * 
	 * @param string $dataClass The class of the DataObjects that this will list.
	 * @param string $joinTable The name of the table whose entries define the content of this many_many relation.
	 * @param string $localKey The key in the join table that maps to the dataClass' PK.
	 * @param string $foreignKey The key in the join table that maps to joined class' PK.
	 * @param string $extraFields A map of field => fieldtype of extra fields on the join table.
	 * 
	 * @example new ManyManyList('Group','Group_Members', 'GroupID', 'MemberID');
	 */
	public function __construct($dataClass, $joinTable, $localKey, $foreignKey, $extraFields = array()) {
		parent::__construct($dataClass);
		$this->joinTable = $joinTable;
		$this->localKey = $localKey;
		$this->foreignKey = $foreignKey;
		$this->extraFields = $extraFields;

		$baseClass = ClassInfo::baseDataClass($dataClass);

		// Join to the many-many join table
		$this->dataQuery->innerJoin($joinTable, "\"$joinTable\".\"$this->localKey\" = \"$baseClass\".\"ID\"");

		// Query the extra fields from the join table
		if($extraFields) $this->dataQuery->selectFromTable($joinTable, array_keys($extraFields));
	}

	/**
	 * Return a filter expression for when getting the contents of the relationship for some foreign ID
	 * @return string
	 */
	protected function foreignIDFilter($id = null) {
		if ($id === null) $id = $this->getForeignID();

		// Apply relation filter
		if(is_array($id)) {
			return "\"$this->joinTable\".\"$this->foreignKey\" IN ('" . 
				implode("', '", array_map('Convert::raw2sql', $id)) . "')";
		} else if($id !== null){
			return "\"$this->joinTable\".\"$this->foreignKey\" = '" . 
				Convert::raw2sql($id) . "'";
		}
	}

	/**
	 * Return a filter expression for the join table when writing to the join table
	 *
	 * When writing (add, remove, removeByID), we need to filter the join table to just the relevant
	 * entries. However some subclasses of ManyManyList (Member_GroupSet) modify foreignIDFilter to
	 * include additional calculated entries, so we need different filters when reading and when writing
	 *
	 * @return string
	 */
	protected function foreignIDWriteFilter($id = null) {
		return $this->foreignIDFilter($id);
	}

	/**
	 * Add an item to this many_many relationship
	 * Does so by adding an entry to the joinTable.
	 * @param $extraFields A map of additional columns to insert into the joinTable
	 */
	public function add($item, $extraFields = null) {
		if(is_numeric($item)) $itemID = $item;
		else if($item instanceof $this->dataClass) $itemID = $item->ID;
		else {
			throw new InvalidArgumentException("ManyManyList::add() expecting a $this->dataClass object, or ID value",
				E_USER_ERROR);
		}

		$foreignIDs = $this->getForeignID();
		$foreignFilter = $this->foreignIDWriteFilter();

		// Validate foreignID
		if(!$foreignIDs) {
			throw new Exception("ManyManyList::add() can't be called until a foreign ID is set", E_USER_WARNING);
		}

		if($foreignFilter) {
			$query = new SQLQuery("*", array("\"$this->joinTable\""));
			$query->setWhere($foreignFilter);
			$hasExisting = ($query->count() > 0);
		} else {
			$hasExisting = false;	
		}

		// Insert or update
		foreach((array)$foreignIDs as $foreignID) {
			$manipulation = array();
			if($hasExisting) {
				$manipulation[$this->joinTable]['command'] = 'update';	
				$manipulation[$this->joinTable]['where'] = "\"$this->joinTable\".\"$this->foreignKey\" = " . 
					"'" . Convert::raw2sql($foreignID) . "'" .
					" AND \"$this->localKey\" = {$itemID}";
			} else {
				$manipulation[$this->joinTable]['command'] = 'insert';	
			}

			if($extraFields) foreach($extraFields as $k => $v) {
				if(is_null($v)) $manipulation[$this->joinTable]['fields'][$k] = 'NULL';
				else $manipulation[$this->joinTable]['fields'][$k] =  "'" . Convert::raw2sql($v) . "'";
			}

			$manipulation[$this->joinTable]['fields'][$this->localKey] = $itemID;
			$manipulation[$this->joinTable]['fields'][$this->foreignKey] = $foreignID;

			DB::manipulate($manipulation);
		}
	}

	/**
	 * Remove the given item from this list.
	 * Note that for a ManyManyList, the item is never actually deleted, only the join table is affected
	 * @param $itemID The ID of the item to remove.
	 */
	public function remove($item) {
		if(!($item instanceof $this->dataClass)) {
			throw new InvalidArgumentException("ManyManyList::remove() expecting a $this->dataClass object");
		}
		
		return $this->removeByID($item->ID);
	}

	/**
	 * Remove the given item from this list.
	 * Note that for a ManyManyList, the item is never actually deleted, only the join table is affected
	 * @param $itemID The item it
	 */
	public function removeByID($itemID) {
		if(!is_numeric($itemID)) throw new InvalidArgumentException("ManyManyList::removeById() expecting an ID");

		$query = new SQLQuery("*", array("\"$this->joinTable\""));
		$query->setDelete(true);

		if($filter = $this->foreignIDWriteFilter($this->getForeignID())) {
			$query->setWhere($filter);
		} else {
			user_error("Can't call ManyManyList::remove() until a foreign ID is set", E_USER_WARNING);
		}
		
		$query->addWhere("\"$this->localKey\" = {$itemID}");
		$query->execute();
	}

	/**
	 * Remove all items from this many-many join.  To remove a subset of items, filter it first.
	 */
	public function removeAll() {
		$base = ClassInfo::baseDataClass($this->dataClass());

		// Remove the join to the join table to avoid MySQL row locking issues.
		$query = $this->dataQuery();
		$query->removeFilterOn($query->getQueryParam('Foreign.Filter'));

		$query = $query->query();
		$query->setSelect("\"$base\".\"ID\"");

		$from = $query->getFrom();
		unset($from[$this->joinTable]);
		$query->setFrom($from);
		$query->setDistinct(false);
		$query->setOrderBy(null, null); // ensure any default sorting is removed, ORDER BY can break DELETE clauses

		// Use a sub-query as SQLite does not support setting delete targets in
		// joined queries.
		$delete = new SQLQuery();
		$delete->setDelete(true);
		$delete->setFrom("\"$this->joinTable\"");
		$delete->addWhere($this->foreignIDFilter());
		$delete->addWhere("\"$this->joinTable\".\"$this->localKey\" IN ({$query->sql()})");
		$delete->execute();
	}

	/**
	 * Find the extra field data for a single row of the relationship
	 * join table, given the known child ID.
	 *
	 * @todo Add tests for this / refactor it / something
	 *	
	 * @param string $componentName The name of the component
	 * @param int $itemID The ID of the child for the relationship
	 * @return array Map of fieldName => fieldValue
	 */
	function getExtraData($componentName, $itemID) {
		$result = array();

		if(!is_numeric($itemID)) {
			user_error('ComponentSet::getExtraData() passed a non-numeric child ID', E_USER_ERROR);
		}

		// @todo Optimize into a single query instead of one per extra field
		if($this->extraFields) {
			foreach($this->extraFields as $fieldName => $dbFieldSpec) {
				$query = new SQLQuery("\"$fieldName\"", array("\"$this->joinTable\""));
				if($filter = $this->foreignIDWriteFilter($this->getForeignID())) {
					$query->setWhere($filter);
				} else {
					user_error("Can't call ManyManyList::getExtraData() until a foreign ID is set", E_USER_WARNING);
				}
				$query->addWhere("\"$this->localKey\" = {$itemID}");
				$result[$fieldName] = $query->execute()->value();
			}
		}
		
		return $result;
	}

	/**
	 * Gets the join table used for the relationship.
	 *
	 * @return string the name of the table
	 */
	public function getJoinTable() {
		return $this->joinTable;
	}

	/**
	 * Gets the key used to store the ID of the local/parent object.
	 *
	 * @return string the field name
	 */
	public function getLocalKey() {
		return $this->localKey;
	}

	/**
	 * Gets the key used to store the ID of the foreign/child object.
	 *
	 * @return string the field name
	 */
	public function getForeignKey() {
		return $this->foreignKey;
	}

	/**
	 * Gets the extra fields included in the relationship.
	 *
	 * @return array a map of field names to types
	 */
	public function getExtraFields() {
		return $this->extraFields;
	}

}
