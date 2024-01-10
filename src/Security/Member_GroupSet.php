<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Represents a set of Groups attached to a member.
 * Handles the hierarchy logic.
 *
 * @extends ManyManyList<Group>
 */
class Member_GroupSet extends ManyManyList
{

    protected function linkJoinTable()
    {
        // Do not join the table directly
        if ($this->extraFields) {
            throw new \BadMethodCallException('Member_GroupSet does not support many_many_extraFields');
        }
    }

    /**
     * Link this group set to a specific member.
     *
     * Recursively selects all groups applied to this member, as well as any
     * parent groups of any applied groups
     *
     * @param array|int|string|null $id (optional) An ID or an array of IDs - if not provided, will use the current
     * ids as per getForeignID
     * @return array Condition In array(SQL => parameters format)
     */
    public function foreignIDFilter($id = null)
    {
        if ($id === null) {
            $id = $this->getForeignID();
        }

        // Find directly applied groups
        $manyManyFilter = parent::foreignIDFilter($id);
        $query = SQLSelect::create('"Group_Members"."GroupID"', '"Group_Members"', $manyManyFilter);
        $groupIDs = $query->execute()->column();

        // Get all ancestors, iteratively merging these into the master set
        $allGroupIDs = [];
        while ($groupIDs) {
            $allGroupIDs = array_merge($allGroupIDs, $groupIDs);
            $groupIDs = DataObject::get(Group::class)->byIDs($groupIDs)->column("ParentID");
            $groupIDs = array_filter($groupIDs ?? []);
        }

        // Add a filter to this DataList
        if (!empty($allGroupIDs)) {
            $in = $this->prepareForeignIDsForWhereInClause($allGroupIDs);
            $vals = str_contains($in, '?') ? $allGroupIDs : [];
            return ["\"Group\".\"ID\" IN ($in)" => $vals];
        }

        return ['"Group"."ID"' => 0];
    }

    public function foreignIDWriteFilter($id = null)
    {
        // Use the ManyManyList::foreignIDFilter rather than the one
        // in this class, otherwise we end up selecting all inherited groups
        return parent::foreignIDFilter($id);
    }

    public function add($item, $extraFields = null)
    {
        // Get Group.ID
        $itemID = null;
        if (is_numeric($item)) {
            $itemID = $item;
        } else {
            if ($item instanceof Group) {
                $itemID = $item->ID;
            }
        }

        // Check if this group is allowed to be added
        if ($this->canAddGroups([$itemID])) {
            parent::add($item, $extraFields);
        }
    }

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
        $selectQuery->setDistinct(false);

        // Use a sub-query as SQLite does not support setting delete targets in
        // joined queries.
        $delete = SQLDelete::create();
        $delete->setFrom("\"{$this->joinTable}\"");
        $delete->addWhere(parent::foreignIDFilter());
        $subSelect = $selectQuery->sql($parameters);
        $delete->addWhere([
            "\"{$this->joinTable}\".\"{$this->localKey}\" IN ($subSelect)" => $parameters
        ]);
        $delete->execute();
    }

    /**
     * Determine if the following groups IDs can be added
     *
     * @param array $itemIDs
     * @return boolean
     */
    protected function canAddGroups($itemIDs)
    {
        if (empty($itemIDs)) {
            return true;
        }
        $member = $this->getMember();
        return empty($member) || $member->onChangeGroups($itemIDs);
    }

    /**
     * Get foreign member record for this relation
     *
     * @return Member
     */
    protected function getMember()
    {
        $id = $this->getForeignID();
        if ($id) {
            return DataObject::get_by_id(Member::class, $id);
        }
    }
}
