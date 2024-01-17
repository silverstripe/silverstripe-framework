<?php

namespace SilverStripe\ORM;

use InvalidArgumentException;

/**
 * Subclass of {@link DataList} representing a has_many relation.
 *
 * @template T of DataObject
 * @extends RelationList<T>
 */
class HasManyList extends RelationList
{

    /**
     * @var string
     */
    protected $foreignKey;

    /**
     * Create a new HasManyList object.
     * Generation of the appropriate record set is left up to the caller, using the normal
     * {@link DataList} methods.  Addition arguments are used to support {@link add()}
     * and {@link remove()} methods.
     *
     * @param class-string<T> $dataClass The class of the DataObjects that this will list.
     * @param string $foreignKey The name of the foreign key field to set the ID filter against.
     */
    public function __construct($dataClass, $foreignKey)
    {
        parent::__construct($dataClass);

        $this->foreignKey = $foreignKey;
    }

    /**
     * Gets the field name which holds the related object ID.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @param null|int|array|string $id
     * @return array|null
     */
    protected function foreignIDFilter($id = null)
    {
        if ($id === null) {
            $id = $this->getForeignID();
        }
        // Apply relation filter
        $key = DataObject::getSchema()->sqlColumnForField($this->dataClass(), $this->getForeignKey());
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
     * Adds the item to this relation.
     *
     * It does so by setting the relationFilters.
     *
     * @param DataObject|int $item The DataObject to be added, or its ID
     */
    public function add($item)
    {
        if (is_numeric($item)) {
            $item = DataObject::get_by_id($this->dataClass, $item);
        } elseif (!($item instanceof $this->dataClass)) {
            throw new InvalidArgumentException("HasManyList::add() expecting a $this->dataClass object, or ID value");
        }

        $foreignID = $this->getForeignID();

        // Validate foreignID
        if (!$foreignID) {
            user_error("HasManyList::add() can't be called until a foreign ID is set", E_USER_WARNING);
            return;
        }
        if (is_array($foreignID)) {
            user_error("HasManyList::add() can't be called on a list linked to multiple foreign IDs", E_USER_WARNING);
            return;
        }

        $foreignKey = $this->foreignKey;
        $item->$foreignKey = $foreignID;

        $item->write();

        if ($this->addCallbacks) {
            $this->addCallbacks->call($this, $item, []);
        }
    }

    /**
     * Remove an item from this relation.
     *
     * Doesn't actually remove the item, it just clears the foreign key value.
     *
     * @param int $itemID The ID of the item to be removed.
     */
    public function removeByID($itemID)
    {
        $item = $this->byID($itemID);

        return $this->remove($item);
    }

    /**
     * Remove an item from this relation.
     * Doesn't actually remove the item, it just clears the foreign key value.
     *
     * @param DataObject $item The DataObject to be removed
     */
    public function remove($item)
    {
        if (!($item instanceof $this->dataClass)) {
            throw new InvalidArgumentException("HasManyList::remove() expecting a $this->dataClass object, or ID");
        }

        // Don't remove item which doesn't belong to this list
        $foreignID = $this->getForeignID();
        $foreignKey = $this->getForeignKey();

        if (empty($foreignID)
            || (is_array($foreignID) && in_array($item->$foreignKey, $foreignID ?? []))
            || $foreignID == $item->$foreignKey
        ) {
            $item->$foreignKey = null;
            $item->write();
        }

        if ($this->removeCallbacks) {
            $this->removeCallbacks->call($this, [$item->ID]);
        }
    }
}
