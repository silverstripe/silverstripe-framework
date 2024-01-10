<?php

namespace SilverStripe\ORM;

use BadMethodCallException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\FieldType\DBComposite;
use InvalidArgumentException;
use Exception;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Subclass of {@link DataList} representing a many_many relation.
 *
 * @template T of DataObject
 * @extends RelationList<T>
 */
class ManyManyList extends RelationList
{
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
    protected $_compositeExtraFields = [];

    /**
     * Create a new ManyManyList object.
     *
     * A ManyManyList object represents a list of {@link DataObject} records
     * that correspond to a many-many relationship.
     *
     * Generation of the appropriate record set is left up to the caller, using
     * the normal {@link DataList} methods. Addition arguments are used to
     * support {@link add()} and {@link remove()} methods.
     *
     * @param class-string<T> $dataClass The class of the DataObjects that this will list.
     * @param string $joinTable The name of the table whose entries define the content of this many_many relation.
     * @param string $localKey The key in the join table that maps to the dataClass' PK.
     * @param string $foreignKey The key in the join table that maps to joined class' PK.
     * @param array $extraFields A map of field => fieldtype of extra fields on the join table.
     *
     * @example new ManyManyList('Group','Group_Members', 'GroupID', 'MemberID');
     */
    public function __construct($dataClass, $joinTable, $localKey, $foreignKey, $extraFields = [])
    {
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
    protected function linkJoinTable()
    {
        // Join to the many-many join table
        $dataClassIDColumn = DataObject::getSchema()->sqlColumnForField($this->dataClass(), 'ID');
        $this->dataQuery->innerJoin(
            $this->joinTable,
            "\"{$this->joinTable}\".\"{$this->localKey}\" = {$dataClassIDColumn}"
        );

        // Add the extra fields to the query
        if ($this->extraFields) {
            $this->appendExtraFieldsToQuery();
        }
    }

    /**
     * Adds the many_many_extraFields to the select of the underlying
     * {@link DataQuery}.
     *
     * @return void
     */
    protected function appendExtraFieldsToQuery()
    {
        $finalized = [];

        foreach ($this->extraFields as $field => $spec) {
            $obj = Injector::inst()->create($spec);

            if ($obj instanceof DBComposite) {
                $this->_compositeExtraFields[$field] = [];

                // append the composite field names to the select
                foreach ($obj->compositeDatabaseFields() as $subField => $subSpec) {
                    $col = $field . $subField;
                    $finalized[] = $col;

                    // cache
                    $this->_compositeExtraFields[$field][] = $subField;
                }
            } else {
                $finalized[] = $field;
            }
        }

        $this->dataQuery->addSelectFromTable($this->joinTable, $finalized);
    }

    public function createDataObject($row)
    {
        // remove any composed fields
        $add = [];

        if ($this->_compositeExtraFields) {
            foreach ($this->_compositeExtraFields as $fieldName => $composed) {
                // convert joined extra fields into their composite field types.
                $value = [];

                foreach ($composed as $subField) {
                    if (isset($row[$fieldName . $subField])) {
                        $value[$subField] = $row[$fieldName . $subField];

                        // don't duplicate data in the record
                        unset($row[$fieldName . $subField]);
                    }
                }

                $obj = Injector::inst()->create($this->extraFields[$fieldName], $fieldName);
                $obj->setValue($value, null, false);
                $add[$fieldName] = $obj;
            }
        }

        $dataObject = parent::createDataObject($row);

        foreach ($add as $fieldName => $obj) {
            $dataObject->$fieldName = $obj;
        }

        return $dataObject;
    }

    /**
     * Return a filter expression for when getting the contents of the
     * relationship for some foreign ID
     *
     * @param int|null|string|array $id
     */
    protected function foreignIDFilter($id = null)
    {
        if ($id === null) {
            $id = $this->getForeignID();
        }

        // Apply relation filter
        $key = "\"{$this->joinTable}\".\"{$this->foreignKey}\"";
        if (is_array($id)) {
            $in = $this->prepareForeignIDsForWhereInClause($id);
            $vals = str_contains($in, '?') ? $id : [];
            return ["$key IN ($in)" => $vals];
        }
        if ($id !== null) {
            return [$key => $id];
        }
        return null;
    }

    /**
     * Return a filter expression for the join table when writing to the join table
     *
     * When writing (add, remove, removeByID), we need to filter the join table to just the relevant
     * entries. However some subclasses of ManyManyList (Member_GroupSet) modify foreignIDFilter to
     * include additional calculated entries, so we need different filters when reading and when writing
     *
     * @param array|int|null $id (optional) An ID or an array of IDs - if not provided, will use the current ids
     * as per getForeignID
     * @return array Condition In array(SQL => parameters format)
     */
    protected function foreignIDWriteFilter($id = null)
    {
        return $this->foreignIDFilter($id);
    }

    /**
     * Add an item to this many_many relationship
     * Does so by adding an entry to the joinTable.
     *
     * Can also be used to update an already existing joinTable entry:
     *
     *     $manyManyList->add($recordID,["ExtraField" => "value"]);
     *
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * @param DataObject|int $item
     * @param array $extraFields A map of additional columns to insert into the joinTable.
     * Column names should be ANSI quoted.
     * @throws Exception
     */
    public function add($item, $extraFields = [])
    {
        // Ensure nulls or empty strings are correctly treated as empty arrays
        if (empty($extraFields)) {
            $extraFields = [];
        }

        // Determine ID of new record
        $itemID = null;
        if (is_numeric($item)) {
            $itemID = $item;
        } elseif ($item instanceof $this->dataClass) {
            // Ensure record is saved
            if (!$item->isInDB()) {
                $item->write();
            }
            $itemID = $item->ID;
        } else {
            throw new InvalidArgumentException(
                "ManyManyList::add() expecting a $this->dataClass object, or ID value"
            );
        }
        if (empty($itemID)) {
            throw new InvalidArgumentException("ManyManyList::add() couldn't add this record");
        }

        // Validate foreignID
        $foreignIDs = $this->getForeignID();
        if (empty($foreignIDs)) {
            throw new BadMethodCallException("ManyManyList::add() can't be called until a foreign ID is set");
        }

        // Apply this item to each given foreign ID record
        if (!is_array($foreignIDs)) {
            $foreignIDs = [$foreignIDs];
        }
        foreach ($foreignIDs as $foreignID) {
            // Check for existing records for this item
            if ($foreignFilter = $this->foreignIDWriteFilter($foreignID)) {
                // With the current query, simply add the foreign and local conditions
                // The query can be a bit odd, especially if custom relation classes
                // don't join expected tables (@see Member_GroupSet for example).
                $query = SQLSelect::create("*", "\"{$this->joinTable}\"");
                $query->addWhere($foreignFilter);
                $query->addWhere([
                    "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
                ]);
                $hasExisting = ($query->count() > 0);
            } else {
                $hasExisting = false;
            }

            // Blank manipulation
            $manipulation = [
                $this->joinTable => [
                    'command' => $hasExisting ? 'update' : 'insert',
                    'fields' => [],
                ],
            ];
            if ($hasExisting) {
                $manipulation[$this->joinTable]['where'] = [
                    "\"{$this->joinTable}\".\"{$this->foreignKey}\"" => $foreignID,
                    "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
                ];
            }

            $fieldObjects = [];
            if ($extraFields && $this->extraFields) {
                // Write extra field to manipluation in the same way
                // that DataObject::prepareManipulationTable writes fields
                foreach ($this->extraFields as $fieldName => $fieldSpec) {
                    // Skip fields without an assignment
                    if (array_key_exists($fieldName, $extraFields ?? [])) {
                        /** @var DBField $fieldObject */
                        $fieldObject = Injector::inst()->create($fieldSpec, $fieldName);
                        $fieldObject->setValue($extraFields[$fieldName]);
                        $fieldObject->writeToManipulation($manipulation[$this->joinTable]);
                        $fieldObjects[$fieldName] = $fieldObject;
                    }
                }
            }

            $manipulation[$this->joinTable]['fields'][$this->localKey] = $itemID;
            $manipulation[$this->joinTable]['fields'][$this->foreignKey] = $foreignID;

            // Make sure none of our field assignments are arrays
            foreach ($manipulation as $tableManipulation) {
                if (!isset($tableManipulation['fields'])) {
                    continue;
                }
                foreach ($tableManipulation['fields'] as $fieldName => $fieldValue) {
                    if (is_array($fieldValue)) {
                        // If the field allows non-scalar values we'll let it do dynamic assignments
                        if (isset($fieldObjects[$fieldName]) && $fieldObjects[$fieldName]->scalarValueOnly()) {
                            throw new InvalidArgumentException(
                                'ManyManyList::add: parameterised field assignments are disallowed'
                            );
                        }
                    }
                }
            }

            DB::manipulate($manipulation);
        }

        if ($this->addCallbacks) {
            $this->addCallbacks->call($this, $item, $extraFields);
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
    public function remove($item)
    {
        if (!($item instanceof $this->dataClass)) {
            throw new InvalidArgumentException("ManyManyList::remove() expecting a $this->dataClass object");
        }

        $result = $this->removeByID($item->ID);

        return $result;
    }

    /**
     * Remove the given item from this list.
     *
     * Note that for a ManyManyList, the item is never actually deleted, only
     * the join table is affected
     *
     * @param int $itemID The item ID
     */
    public function removeByID($itemID)
    {
        if (!is_numeric($itemID)) {
            throw new InvalidArgumentException("ManyManyList::removeById() expecting an ID");
        }

        $query = SQLDelete::create("\"{$this->joinTable}\"");

        if ($filter = $this->foreignIDWriteFilter($this->getForeignID())) {
            $query->setWhere($filter);
        } else {
            user_error("Can't call ManyManyList::remove() until a foreign ID is set");
        }

        $query->addWhere([
            "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
        ]);

        // Perform the deletion
        $query->execute();

        if ($this->removeCallbacks) {
            $this->removeCallbacks->call($this, [$itemID]);
        }
    }

    /**
     * Remove all items from this many-many join.  To remove a subset of items,
     * filter it first.
     *
     * @return void
     */
    public function removeAll()
    {
        // Remove the join to the join table to avoid MySQL row locking issues.
        $query = $this->dataQuery();
        $foreignFilter = $query->getQueryParam('Foreign.Filter');
        $query->removeFilterOn($foreignFilter);

        // Select ID column
        $selectQuery = $query->query();
        $dataClassIDColumn = DataObject::getSchema()->sqlColumnForField($this->dataClass(), 'ID');
        $selectQuery->setSelect($dataClassIDColumn);

        $from = $selectQuery->getFrom();
        unset($from[$this->joinTable]);
        $selectQuery->setFrom($from);
        $selectQuery->setOrderBy(); // ORDER BY in subselects breaks MS SQL Server and is not necessary here
        $selectQuery->setLimit(null); // LIMIT in subselects breaks MariaDB (https://mariadb.com/kb/en/subquery-limitations/#limit) and is not necessary here
        $selectQuery->setDistinct(false);

        // Use a sub-query as SQLite does not support setting delete targets in
        // joined queries.
        $delete = SQLDelete::create();
        $delete->setFrom("\"{$this->joinTable}\"");
        $delete->addWhere($this->foreignIDFilter());
        $subSelect = $selectQuery->sql($parameters);
        $delete->addWhere([
            "\"{$this->joinTable}\".\"{$this->localKey}\" IN ($subSelect)" => $parameters
        ]);

        $affectedIds = [];
        if ($this->removeCallbacks) {
            $affectedIds = $delete
                ->toSelect()
                ->setSelect("\"{$this->joinTable}\".\"{$this->localKey}\"")
                ->execute()
                ->column();
        }

        // Perform the deletion
        $delete->execute();

        if ($this->removeCallbacks && $affectedIds) {
            $this->removeCallbacks->call($this, $affectedIds);
        }
    }

    /**
     * Set the extra field data for a single row of the relationship join
     * table, given the known child ID.
     *
     * @param int $itemID The ID of the child for the relationship
     * @param array $data The data to set, with field names as keys and values as values
     * @throws InvalidArgumentException if the $data array is invalid
     */
    public function setExtraData(int $itemID, array $data): void
    {
        // Don't bother doing anything if we aren't given any data
        if (empty($data)) {
            return;
        }

        // Prepare db manipulation
        $foreignID = $this->getForeignID();
        $manipulation = [
            $this->joinTable => [
                'command' => 'update',
                'fields' => [],
                'where' => [
                    "\"{$this->joinTable}\".\"{$this->foreignKey}\"" => $foreignID,
                    "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
                ],
            ],
        ];

        $fieldObjects = [];
        // Write extra field to manipluation in the same way
        // that DataObject::prepareManipulationTable writes fields
        foreach ($data as $fieldName => $value) {
            if (!array_key_exists($fieldName, $this->extraFields)) {
                throw new InvalidArgumentException("Field '$fieldName' is not defined in many_many_extraFields for this relationship");
            }
            /** @var DBField $fieldObject */
            $fieldObject = Injector::inst()->create($this->extraFields[$fieldName], $fieldName);
            // Make sure the field assignment is not an array unless the field allows non-scalar values
            if (is_array($value) && $fieldObject->scalarValueOnly()) {
                throw new InvalidArgumentException(
                    'ManyManyList::setExtraData: parameterised field assignments are disallowed'
                );
            }
            // Set the value into the manipulation
            $fieldObject->setValue($value);
            $fieldObject->writeToManipulation($manipulation[$this->joinTable]);
            $fieldObjects[$fieldName] = $fieldObject;
        }

        DB::manipulate($manipulation);
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
    public function getExtraData($componentName, $itemID)
    {
        $result = [];

        // Skip if no extrafields or unsaved record
        if (empty($this->extraFields) || empty($itemID)) {
            return $result;
        }

        if (!is_numeric($itemID)) {
            throw new InvalidArgumentException('ManyManyList::getExtraData() passed a non-numeric child ID');
        }

        $cleanExtraFields = [];
        foreach ($this->extraFields as $fieldName => $dbFieldSpec) {
            $cleanExtraFields[] = "\"{$fieldName}\"";
        }
        $query = SQLSelect::create($cleanExtraFields, "\"{$this->joinTable}\"");
        $filter = $this->foreignIDWriteFilter($this->getForeignID());
        if ($filter) {
            $query->setWhere($filter);
        } else {
            throw new BadMethodCallException("Can't call ManyManyList::getExtraData() until a foreign ID is set");
        }
        $query->addWhere([
            "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID
        ]);
        $queryResult = $query->execute()->record();
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
    public function getJoinTable()
    {
        return $this->joinTable;
    }

    /**
     * Gets the key used to store the ID of the local/parent object.
     *
     * @return string the field name
     */
    public function getLocalKey()
    {
        return $this->localKey;
    }

    /**
     * Gets the key used to store the ID of the foreign/child object.
     *
     * @return string the field name
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Gets the extra fields included in the relationship.
     *
     * @return array a map of field names to types
     */
    public function getExtraFields()
    {
        return $this->extraFields;
    }
}
