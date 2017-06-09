<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Represents a set of Groups attached to a member.
 * Handles the hierarchy logic.
 */
class Member_GroupSet extends ManyManyList
{

    protected function linkJoinTable()
    {
        // Do not join the table directly
        if ($this->extraFields) {
            user_error('Member_GroupSet does not support many_many_extraFields', E_USER_ERROR);
        }
    }

    /**
     * Link this group set to a specific member.
     *
     * Recursively selects all groups applied to this member, as well as any
     * parent groups of any applied groups
     *
     * @param array|integer $id (optional) An ID or an array of IDs - if not provided, will use the current
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
        $query = new SQLSelect('"Group_Members"."GroupID"', '"Group_Members"', $manyManyFilter);
        $groupIDs = $query->execute()->column();

        // Get all ancestors, iteratively merging these into the master set
        $allGroupIDs = array();
        while ($groupIDs) {
            $allGroupIDs = array_merge($allGroupIDs, $groupIDs);
            $groupIDs = DataObject::get("SilverStripe\\Security\\Group")->byIDs($groupIDs)->column("ParentID");
            $groupIDs = array_filter($groupIDs);
        }

        // Add a filter to this DataList
        if (!empty($allGroupIDs)) {
            $allGroupIDsPlaceholders = DB::placeholders($allGroupIDs);
            return array("\"Group\".\"ID\" IN ($allGroupIDsPlaceholders)" => $allGroupIDs);
        } else {
            return array('"Group"."ID"' => 0);
        }
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
        if ($this->canAddGroups(array($itemID))) {
            parent::add($item, $extraFields);
        }
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
