<?php

namespace SilverStripe\ORM;

use InvalidArgumentException;
use ArrayIterator;
use SilverStripe\ORM\FieldType\DBField;
use Traversable;

/**
 * An {@link ArrayList} that represents an unsaved relation.
 *
 * has_many and many_many relations cannot be saved until after the DataObject
 * they're on has been written. This List pretends to be a RelationList and
 * stores the related objects in memory.
 *
 * It can store both saved objects (as IDs) or unsaved objects (as instances
 * of $dataClass). Unsaved objects are then written when the list is saved
 * into an instance of {@link RelationList}.
 *
 * @template T of DataObject
 * @extends ArrayList<T>
 * @implements Relation<T>
 */
class UnsavedRelationList extends ArrayList implements Relation
{

    /**
     * The DataObject class name that this relation is on
     *
     * @var string
     */
    protected $baseClass;

    /**
     * The name of the relation
     *
     * @var string
     */
    protected $relationName;

    /**
     * The DataObject class name that this relation is querying
     *
     * @var class-string<T>
     */
    protected $dataClass;

    /**
     * The extra fields associated with the relation
     *
     * @var array
     */
    protected $extraFields = [];

    /**
     * Create a new UnsavedRelationList
     *
     * @param string $baseClass
     * @param string $relationName
     * @param class-string<T> $dataClass The DataObject class used in the relation
     */
    public function __construct($baseClass, $relationName, $dataClass)
    {
        $this->baseClass = $baseClass;
        $this->relationName = $relationName;
        $this->dataClass = $dataClass;
        parent::__construct();
    }

    /**
     * Add an item to this relationship
     *
     * @param mixed $item
     * @param array $extraFields A map of additional columns to insert into the joinTable in the case of a many_many relation
     */
    public function add($item, $extraFields = null)
    {
        $this->push($item, $extraFields);
    }

    /**
     * Save all the items in this list into the RelationList
     *
     * @param RelationList $list
     */
    public function changeToList(RelationList $list)
    {
        foreach ($this->items as $key => $item) {
            $list->add($item, $this->extraFields[$key]);
        }
    }

    /**
     * Pushes an item onto the end of this list.
     *
     * @param array|object $item
     * @param array $extraFields
     */
    public function push($item, $extraFields = null)
    {
        if ((is_object($item) && !$item instanceof $this->dataClass)
            || (!is_object($item) && !is_numeric($item))
        ) {
            throw new InvalidArgumentException(
                "UnsavedRelationList::add() expecting a $this->dataClass object, or ID value"
            );
        }
        if (is_object($item) && $item->ID) {
            $item = $item->ID;
        }
        $this->extraFields[] = $extraFields;
        parent::push($item);
    }

    /**
     * Get the dataClass name for this relation, ie the DataObject ClassName
     *
     * @return class-string<T>
     */
    public function dataClass()
    {
        return $this->dataClass;
    }

    /**
     * Returns an Iterator for this relation.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Return an array of the actual items that this relation contains at this stage.
     * This is when the query is actually executed.
     */
    public function toArray()
    {
        $items = [];
        foreach ($this->items as $key => $item) {
            if (is_numeric($item)) {
                $item = DataObject::get_by_id($this->dataClass, $item);
            }
            if (!empty($this->extraFields[$key])) {
                $item->update($this->extraFields[$key]);
            }
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Add a number of items to the relation.
     *
     * @param array $items Items to add, as either DataObjects or IDs.
     * @return $this
     */
    public function addMany($items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
        return $this;
    }

    /**
     * Remove all items from this relation.
     */
    public function removeAll()
    {
        $this->items = [];
        $this->extraFields = [];
    }

    /**
     * Remove the items from this list with the given IDs
     *
     * @param array $items
     * @return $this
     */
    public function removeMany($items)
    {
        $this->items = array_diff($this->items ?? [], $items);
        return $this;
    }

    /**
     * Removes items from this list which are equal.
     *
     * @param string $field unused
     */
    public function removeDuplicates($field = 'ID')
    {
        $this->items = array_unique($this->items ?? []);
    }

    /**
     * Sets the Relation to be the given ID list.
     * Records will be added and deleted as appropriate.
     *
     * @param array $idList List of IDs.
     */
    public function setByIDList($idList)
    {
        $this->removeAll();
        $this->addMany($idList);
    }

    /**
     * Returns an array with both the keys and values set to the IDs of the records in this list.
     * Does not respect sort order. Use ->column("ID") to get an ID list with the current sort.
     * Does not return the IDs for unsaved DataObjects.
     */
    public function getIDList()
    {
        // Get a list of IDs of our current items - if it's not a number then object then assume it's a DO.
        $ids = array_map(function ($obj) {
            return is_numeric($obj) ? $obj : $obj->ID;
        }, $this->items ?? []);

        // Strip out duplicates and anything resolving to False.
        $ids = array_filter(array_unique($ids ?? []));

        // Change the array from (1, 2, 3) to (1 => 1, 2 => 2, 3 => 3)
        if ($ids) {
            $ids = array_combine($ids ?? [], $ids ?? []);
        }

        return $ids;
    }

    public function first()
    {
        $item = reset($this->items) ?: null;
        if (is_numeric($item)) {
            $item = DataObject::get_by_id($this->dataClass, $item);
        }
        if ($item && !empty($this->extraFields[key($this->items)])) {
            $item->update($this->extraFields[key($this->items)]);
        }
        return $item;
    }

    public function last()
    {
        $item = end($this->items) ?: null;
        if (is_numeric($item)) {
            $item = DataObject::get_by_id($this->dataClass, $item);
        }
        if ($item && !empty($this->extraFields[key($this->items)])) {
            $item->update($this->extraFields[key($this->items)]);
        }
        return $item;
    }

    /**
     * Returns an array of a single field value for all items in the list.
     *
     * @param string $colName
     * @return array
     */
    public function column($colName = 'ID')
    {
        $list = new ArrayList($this->toArray());
        return $list->column($colName);
    }

    /**
     * Returns a unique array of a single field value for all items in the list.
     *
     * @param  string $colName
     * @return array
     */
    public function columnUnique($colName = "ID")
    {
        $list = new ArrayList($this->toArray());
        return $list->columnUnique($colName);
    }

    /**
     * Returns a copy of this list with the relationship linked to the given foreign ID.
     * @param int|array $id An ID or an array of IDs.
     * @return Relation<T>
     */
    public function forForeignID($id)
    {
        $singleton = DataObject::singleton($this->baseClass);
        /** @var Relation $relation */
        $relation = $singleton->{$this->relationName}($id);
        return $relation;
    }

    /**
     * @param string $relationName
     * @return Relation
     */
    public function relation($relationName)
    {
        $ids = $this->column('ID');
        $singleton = DataObject::singleton($this->dataClass);
        /** @var Relation $relation */
        $relation = $singleton->$relationName($ids);
        return $relation;
    }

    /**
     * Return the DBField object that represents the given field on the related class.
     *
     * @param string $fieldName Name of the field
     * @return DBField The field as a DBField object
     */
    public function dbObject($fieldName)
    {
        return DataObject::singleton($this->dataClass)->dbObject($fieldName);
    }

    protected function extractValue($item, $key)
    {
        if (is_numeric($item)) {
            $item = DataObject::get_by_id($this->dataClass, $item);
        }
        return parent::extractValue($item, $key);
    }
}
