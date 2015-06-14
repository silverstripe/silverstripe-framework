<?php

/**
 * Subclass of {@link DataList} representing a many_many relation.
 *
 * @package framework
 * @subpackage model
 */
class ManyManyList extends RelationList {

	/**
	 * @var string $joinTable
	 */
	protected $joinTable;

	/**
	 * @var string $localKey
	 */
	protected $localKey;

	/**
	 * @var string $foreignKey
	 */
	protected $foreignKey;

	/**
	 * @var array $extraFields
	 */
	protected $extraFields;

	/**
	 * @var array $_compositeExtraFields
	 */
	protected $_compositeExtraFields = array();

	/**
	 * Create a new ManyManyList object.
	 *
	 * A ManyManyList object represents a list of {@link DataObject} records
	 * that correspond to a many-many relationship.
	 *
	 * Generation of the appropriate record set is left up to the caller, using
	 * the normal {@link DataList} methods. Addition arguments are used to
	 * support {@@link add()} and {@link remove()} methods.
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

		$this->linkJoinTable();
	}

	/**
	 * Setup the join between this dataobject and the necessary mapping table
	 */
	protected function linkJoinTable() {
		// Join to the many-many join table
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		$this->dataQuery->innerJoin($this->joinTable, "\"{$this->joinTable}\".\"{$this->localKey}\" = \"{$baseClass}\".\"ID\"");

		// Add the extra fields to the query
		if($this->extraFields) {
			$this->appendExtraFieldsToQuery();
		}
	}

	/**
	 * Adds the many_many_extraFields to the select of the underlying
	 * {@link DataQuery}.
	 *
	 * @return void
	 */
	protected function appendExtraFieldsToQuery() {
		$finalized = array();

		foreach($this->extraFields as $field => $spec) {
			$obj = Object::create_from_string($spec);

			if($obj instanceof CompositeDBField) {
				$this->_compositeExtraFields[$field] = array();

				// append the composite field names to the select
				foreach($obj->compositeDatabaseFields() as $subField => $subSpec) {
					$col = $field . $subField;
					$finalized[] = $col;

					// cache
					$this->_compositeExtraFields[$field][] = $subField;
				}
			} else {
				$finalized[] = $field;
			}
		}

		$this->dataQuery->selectFromTable($this->joinTable, $finalized);
	}

	/**
	 * Create a DataObject from the given SQL row.
	 *
	 * @param array $row
	 * @return DataObject
	 */
	protected function createDataObject($row) {
		// remove any composed fields
		$add = array();

		if($this->_compositeExtraFields) {
			foreach($this->_compositeExtraFields as $fieldName => $composed) {
				// convert joined extra fields into their composite field types.
				$value = array();

				foreach($composed as $subField => $subSpec) {
					if(isset($row[$fieldName . $subSpec])) {
						$value[$subSpec] = $row[$fieldName . $subSpec];

						// don't duplicate data in the record
						unset($row[$fieldName . $subSpec]);
					}
				}

				$obj = Object::create_from_string($this->extraFields[$fieldName], $fieldName);
				$obj->setValue($value, null, false);

				$add[$fieldName] = $obj;
			}
		}

		$dataObject = parent::createDataObject($row);

		foreach($add as $fieldName => $obj) {
			$dataObject->$fieldName = $obj;
		}

		return $dataObject;
	}

	/**
	 * Return a filter expression for when getting the contents of the
	 * relationship for some foreign ID
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	protected function foreignIDFilter($id = null) {
		if ($id === null) {
			$id = $this->getForeignID();
		}

		// Apply relation filter
		$key = "\"{$this->joinTable}\".\"{$this->foreignKey}\"";
		if(is_array($id)) {
			return array("$key IN (".DB::placeholders($id).")"  => $id);
		} else if($id !== null){
			return array($key => $id);
		}
	}

	/**
	 * Return a filter expression for the join table when writing to the join table
	 *
	 * When writing (add, remove, removeByID), we need to filter the join table to just the relevant
	 * entries. However some subclasses of ManyManyList (Member_GroupSet) modify foreignIDFilter to
	 * include additional calculated entries, so we need different filters when reading and when writing
	 *
	 * @param array|integer $id (optional) An ID or an array of IDs - if not provided, will use the current ids
	 * as per getForeignID
	 * @return array Condition In array(SQL => parameters format)
	 */
	protected function foreignIDWriteFilter($id = null) {
		return $this->foreignIDFilter($id);
	}

	/**
	 * Add an item to this many_many relationship
	 * Does so by adding an entry to the joinTable.
	 *
	 * @param mixed $item
	 * @param array $extraFields A map of additional columns to insert into the joinTable.
	 * Column names should be ANSI quoted.
	 */
	public function add($item, $extraFields = array()) {
		// Ensure nulls or empty strings are correctly treated as empty arrays
		if(empty($extraFields)) $extraFields = array();

		// Determine ID of new record
		if(is_numeric($item)) {
			$itemID = $item;
		} elseif($item instanceof $this->dataClass) {
			$itemID = $item->ID;
		} else {
			throw new InvalidArgumentException("ManyManyList::add() expecting a $this->dataClass object, or ID value",
				E_USER_ERROR);
		}

		// Validate foreignID
		$foreignIDs = $this->getForeignID();
		if(empty($foreignIDs)) {
			throw new Exception("ManyManyList::add() can't be called until a foreign ID is set", E_USER_WARNING);
		}

		// Apply this item to each given foreign ID record
		if(!is_array($foreignIDs)) $foreignIDs = array($foreignIDs);
		foreach($foreignIDs as $foreignID) {
			// Check for existing records for this item
			if($foreignFilter = $this->foreignIDWriteFilter($foreignID)) {
				// With the current query, simply add the foreign and local conditions
				// The query can be a bit odd, especially if custom relation classes
				// don't join expected tables (@see Member_GroupSet for example).
				$query = new SQLQuery("*", "\"{$this->joinTable}\"");
				$query->addWhere($foreignFilter);
				$query->addWhere(array(
					"\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
				));
				$hasExisting = ($query->count() > 0);
			} else {
				$hasExisting = false;
			}

			// Blank manipulation
			$manipulation = array(
				$this->joinTable => array(
					'command' => $hasExisting ? 'update' : 'insert',
					'fields' => array()
				)
			);
			if($hasExisting) {
				$manipulation[$this->joinTable]['where'] = array(
					"\"{$this->joinTable}\".\"{$this->foreignKey}\"" => $foreignID,
					"\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
				);
			}

			if($extraFields && $this->extraFields) {
				// Write extra field to manipluation in the same way
				// that DataObject::prepareManipulationTable writes fields
				foreach($this->extraFields as $fieldName => $fieldSpec) {
					// Skip fields without an assignment
					if(array_key_exists($fieldName, $extraFields)) {
						$fieldObject = Object::create_from_string($fieldSpec, $fieldName);
						$fieldObject->setValue($extraFields[$fieldName]);
						$fieldObject->writeToManipulation($manipulation[$this->joinTable]);
					}
				}
			}

			$manipulation[$this->joinTable]['fields'][$this->localKey] = $itemID;
			$manipulation[$this->joinTable]['fields'][$this->foreignKey] = $foreignID;

			DB::manipulate($manipulation);
		}
	}

	/**
	 * Remove the given item from this list.
	 *
	 * Note that for a ManyManyList, the item is never actually deleted, only
	 * the join table is affected.
	 *
	 * @param DataObject $item
	 */
	public function remove($item) {
		if(!($item instanceof $this->dataClass)) {
			throw new InvalidArgumentException("ManyManyList::remove() expecting a $this->dataClass object");
		}

		return $this->removeByID($item->ID);
	}

	/**
	 * Remove the given item from this list.
	 *
	 * Note that for a ManyManyList, the item is never actually deleted, only
	 * the join table is affected
	 *
	 * @param int $itemID The item ID
	 */
	public function removeByID($itemID) {
		if(!is_numeric($itemID)) throw new InvalidArgumentException("ManyManyList::removeById() expecting an ID");

		$query = new SQLDelete("\"{$this->joinTable}\"");

		if($filter = $this->foreignIDWriteFilter($this->getForeignID())) {
			$query->setWhere($filter);
		} else {
			user_error("Can't call ManyManyList::remove() until a foreign ID is set", E_USER_WARNING);
		}

		$query->addWhere(array("\"{$this->localKey}\"" => $itemID));
		$query->execute();
	}

	/**
	 * Remove all items from this many-many join.  To remove a subset of items,
	 * filter it first.
	 *
	 * @return void
	 */
	public function removeAll() {
		$base = ClassInfo::baseDataClass($this->dataClass());

		// Remove the join to the join table to avoid MySQL row locking issues.
		$query = $this->dataQuery();
		$foreignFilter = $query->getQueryParam('Foreign.Filter');
		$query->removeFilterOn($foreignFilter);

		$selectQuery = $query->query();
		$selectQuery->setSelect("\"{$base}\".\"ID\"");

		$from = $selectQuery->getFrom();
		unset($from[$this->joinTable]);
		$selectQuery->setFrom($from);
		$selectQuery->setOrderBy(); // ORDER BY in subselects breaks MS SQL Server and is not necessary here
		$selectQuery->setDistinct(false);

		// Use a sub-query as SQLite does not support setting delete targets in
		// joined queries.
		$delete = new SQLDelete();
		$delete->setFrom("\"{$this->joinTable}\"");
		$delete->addWhere($this->foreignIDFilter());
		$subSelect = $selectQuery->sql($parameters);
		$delete->addWhere(array(
			"\"{$this->joinTable}\".\"{$this->localKey}\" IN ($subSelect)" => $parameters
		));
		$delete->execute();
	}

	/**
	 * Find the extra field data for a single row of the relationship join
	 * table, given the known child ID.
	 *
	 * @param string $componentName The name of the component
	 * @param int $itemID The ID of the child for the relationship
	 *
	 * @return array Map of fieldName => fieldValue
	 */
	public function getExtraData($componentName, $itemID) {
		$result = array();
		
		// Skip if no extrafields or unsaved record
		if(empty($this->extraFields) || empty($itemID)) {
			return $result;
		}

		if(!is_numeric($itemID)) {
			user_error('ComponentSet::getExtraData() passed a non-numeric child ID', E_USER_ERROR);
		}

		$cleanExtraFields = array();
		foreach ($this->extraFields as $fieldName => $dbFieldSpec) {
			$cleanExtraFields[] = "\"{$fieldName}\"";
		}
		$query = new SQLQuery($cleanExtraFields, "\"{$this->joinTable}\"");
		$filter = $this->foreignIDWriteFilter($this->getForeignID());
		if($filter) {
			$query->setWhere($filter);
		} else {
			user_error("Can't call ManyManyList::getExtraData() until a foreign ID is set", E_USER_WARNING);
		}
		$query->addWhere(array(
			"\"{$this->localKey}\"" => $itemID
		));
		$queryResult = $query->execute()->current();
		if ($queryResult) {
			foreach ($queryResult as $fieldName => $value) {
				$result[$fieldName] = $value;
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
