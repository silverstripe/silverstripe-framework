<?php

namespace SilverStripe\ORM;

use Exception;
use Sminnee\CallbackList\CallbackList;

/**
 * A DataList that represents a relation.
 *
 * Adds the notion of a foreign ID that can be optionally set.
 */
abstract class RelationList extends DataList implements Relation
{
    /**
     * @var CallbackList|null
     */
    protected $addCallbacks;

    /**
     * @var CallbackList|null
     */
    protected $removeCallbacks;

    /**
     * Manage callbacks which are called after the add() action is completed.
     * Each callback will be passed (RelationList $this, DataObject|int $item, array $extraFields).
     * If a relation methods is manually defined, this can be called to adjust the behaviour
     * when adding records to this list.
     *
     * Needs to be defined through an overloaded relationship getter
     * to ensure it is set consistently. These getters return a new object
     * every time they're called.
     *
     * Note that subclasses of RelationList must implement the callback for it to function
     *
     * @return this
     */
    public function addCallbacks(): CallbackList
    {
        if (!$this->addCallbacks) {
            $this->addCallbacks = new CallbackList();
        }

        return $this->addCallbacks;
    }

    /**
     * Manage callbacks which are called after the remove() action is completed.
     * Each Callback will be passed (RelationList $this, [int] $removedIds).
     *
     * Needs to be defined through an overloaded relationship getter
     * to ensure it is set consistently. These getters return a new object
     * every time they're called. Example:
     *
     * ```php
     * class MyObject extends DataObject()
     * {
     *   private static $many_many = [
     *     'MyRelationship' => '...',
     *   ];
     *   public function MyRelationship()
     *   {
     *     $list = $this->getManyManyComponents('MyRelationship');
     *     $list->removeCallbacks()->add(function ($removedIds) {
     *       // ...
     *     });
     *     return $list;
     *   }
     * }
     * ```
     *
     * If a relation methods is manually defined, this can be called to adjust the behaviour
     * when adding records to this list.
     *
     * Subclasses of RelationList must implement the callback for it to function
     *
     * @return this
     */
    public function removeCallbacks(): CallbackList
    {
        if (!$this->removeCallbacks) {
            $this->removeCallbacks = new CallbackList();
        }

        return $this->removeCallbacks;
    }

    /**
     * Any number of foreign keys to apply to this list
     *
     * @return string|array|null
     */
    public function getForeignID()
    {
        return $this->dataQuery->getQueryParam('Foreign.ID');
    }

    public function getQueryParams()
    {
        $params = parent::getQueryParams();

        // Remove `Foreign.` query parameters for created objects,
        // as this would interfere with relations on those objects.
        foreach (array_keys($params ?? []) as $key) {
            if (stripos($key ?? '', 'Foreign.') === 0) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Returns a copy of this list with the ManyMany relationship linked to
     * the given foreign ID.
     *
     * @param int|array $id An ID or an array of IDs.
     *
     * @return static
     */
    public function forForeignID($id)
    {
        // Turn a 1-element array into a simple value
        if (is_array($id) && sizeof($id ?? []) == 1) {
            $id = reset($id);
        }

        // Calculate the new filter
        $filter = $this->foreignIDFilter($id);

        $list = $this->alterDataQuery(function (DataQuery $query) use ($id, $filter) {
            // Check if there is an existing filter, remove if there is
            $currentFilter = $query->getQueryParam('Foreign.Filter');
            if ($currentFilter) {
                try {
                    $query->removeFilterOn($currentFilter);
                } catch (Exception $e) {
                    /* NOP */
                }
            }

            // Add the new filter
            $query->setQueryParam('Foreign.ID', $id);
            $query->setQueryParam('Foreign.Filter', $filter);
            $query->where($filter);
        });

        return $list;
    }

    /**
     * Returns a where clause that filters the members of this relationship to
     * just the related items.
     *
     *
     * @param array|integer $id (optional) An ID or an array of IDs - if not provided, will use the current ids as
     * per getForeignID
     * @return array Condition In array(SQL => parameters format)
     */
    abstract protected function foreignIDFilter($id = null);
}
