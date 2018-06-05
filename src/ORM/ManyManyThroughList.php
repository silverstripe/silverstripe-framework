<?php

namespace SilverStripe\ORM;

use BadMethodCallException;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

/**
 * ManyManyList backed by a dataobject join table
 */
class ManyManyThroughList extends RelationList
{
    /**
     * @var ManyManyThroughQueryManipulator
     */
    protected $manipulator;

    /**
     * Create a new ManyManyRelationList object. This relation will utilise an intermediary dataobject
     * as a join table, unlike ManyManyList which scaffolds a table automatically.
     *
     * @param string $dataClass The class of the DataObjects that this will list.
     * @param string $joinClass Class name of the joined dataobject record
     * @param string $localKey The key in the join table that maps to the dataClass' PK.
     * @param string $foreignKey The key in the join table that maps to joined class' PK.
     *
     * @param array $extraFields Ignored for ManyManyThroughList
     * @param string $foreignClass 'from' class
     * @param string $parentClass Parent class (should be subclass of 'from')
     * @example new ManyManyThroughList('Banner', 'PageBanner', 'BannerID', 'PageID');
     */
    public function __construct(
        $dataClass,
        $joinClass,
        $localKey,
        $foreignKey,
        $extraFields = [],
        $foreignClass = null,
        $parentClass = null
    ) {
        parent::__construct($dataClass);

        // Inject manipulator
        $this->manipulator = ManyManyThroughQueryManipulator::create(
            $joinClass,
            $localKey,
            $foreignKey,
            $foreignClass,
            $parentClass
        );
        $this->dataQuery->pushQueryManipulator($this->manipulator);
    }

    /**
     * Don't apply foreign ID filter until getFinalisedQuery()
     *
     * @param array|integer $id (optional) An ID or an array of IDs - if not provided, will use the current ids as
     * per getForeignID
     * @return array Condition In array(SQL => parameters format)
     */
    protected function foreignIDFilter($id = null)
    {
        // foreignIDFilter is applied to the HasManyList via ManyManyThroughQueryManipulator, not here
        return [];
    }

    public function createDataObject($row)
    {
        // Add joined record
        $joinRow = [];
        $joinAlias = $this->manipulator->getJoinAlias();
        $prefix = $joinAlias . '_';
        foreach ($row as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $joinKey = substr($key, strlen($prefix));
                $joinRow[$joinKey] = $value;
                unset($row[$key]);
            }
        }

        // Create parent record
        $record = parent::createDataObject($row);

        // Create joined record
        if ($joinRow) {
            $joinClass = $this->manipulator->getJoinClass();
            $joinQueryParams = $this->manipulator->extractInheritableQueryParameters($this->dataQuery);
            $joinRecord = Injector::inst()->create($joinClass, $joinRow, false, $joinQueryParams);
            $record->setJoin($joinRecord, $joinAlias);
        }

        return $record;
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
            throw new InvalidArgumentException(
                "ManyManyThroughList::remove() expecting a {$this->dataClass} object"
            );
        }

        $this->removeByID($item->ID);
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
            throw new InvalidArgumentException("ManyManyThroughList::removeById() expecting an ID");
        }

        // Find has_many row with a local key matching the given id
        $hasManyList = $this->manipulator->getParentRelationship($this->dataQuery());
        $records = $hasManyList->filter($this->manipulator->getLocalKey(), $itemID);

        // Rather than simple un-associating the record (as in has_many list)
        // Delete the actual mapping row as many_many deletions behave.
        /** @var DataObject $record */
        foreach ($records as $record) {
            $record->delete();
        }
    }

    public function removeAll()
    {
        // Empty has_many table matching the current foreign key
        $hasManyList = $this->manipulator->getParentRelationship($this->dataQuery());
        $hasManyList->removeAll();
    }

    /**
     * @param mixed $item
     * @param array $extraFields
     */
    public function add($item, $extraFields = [])
    {
        // Ensure nulls or empty strings are correctly treated as empty arrays
        if (empty($extraFields)) {
            $extraFields = array();
        }

        // Determine ID of new record
        $itemID = null;
        if (is_numeric($item)) {
            $itemID = $item;
        } elseif ($item instanceof $this->dataClass) {
            /** @var DataObject $item */
            if (!$item->isInDB()) {
                $item->write();
            }
            $itemID = $item->ID;
        } else {
            throw new InvalidArgumentException(
                "ManyManyThroughList::add() expecting a $this->dataClass object, or ID value"
            );
        }
        if (empty($itemID)) {
            throw new InvalidArgumentException("ManyManyThroughList::add() could not add record without ID");
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
        $foreignIDsToAdd = array_combine($foreignIDs, $foreignIDs);

        // Update existing records
        $localKey = $this->manipulator->getLocalKey();
        // Foreign key (or key for ID field if polymorphic)
        $foreignKey = $this->manipulator->getForeignIDKey();
        $hasManyList = $this->manipulator->getParentRelationship($this->dataQuery());
        $records = $hasManyList->filter($localKey, $itemID);
        /** @var DataObject $record */
        foreach ($records as $record) {
            if ($extraFields) {
                foreach ($extraFields as $field => $value) {
                    $record->$field = $value;
                }
                $record->write();
            }
            //
            $foreignID = $record->$foreignKey;
            unset($foreignIDsToAdd[$foreignID]);
        }

        // Check if any records remain to add
        if (empty($foreignIDsToAdd)) {
            return;
        }

        // Add item to relation
        $hasManyList = $hasManyList->forForeignID($foreignIDsToAdd);
        $record = $hasManyList->createDataObject($extraFields ?: []);
        $record->$localKey = $itemID;
        $hasManyList->add($record);
    }

    /**
     * Get extra fields used by this list
     *
     * @return array a map of field names to types
     */
    public function getExtraFields()
    {
        // Inherit config from join table
        $joinClass = $this->manipulator->getJoinClass();
        return Config::inst()->get($joinClass, 'db');
    }

    /**
     * @return string
     */
    public function getJoinTable()
    {
        $joinClass = $this->manipulator->getJoinClass();
        return DataObject::getSchema()->tableName($joinClass);
    }
}
