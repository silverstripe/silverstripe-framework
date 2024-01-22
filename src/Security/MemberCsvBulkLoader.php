<?php

namespace SilverStripe\Security;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\ORM\DataObject;

/**
 * Imports member records, and checks/updates duplicates based on their
 * 'Email' property.
 */
class MemberCsvBulkLoader extends CsvBulkLoader
{

    /**
     * @var array Array of {@link Group} records. Import into a specific group.
     *  Is overruled by any "Groups" columns in the import.
     */
    protected $groups = [];

    public function __construct($objectClass = null)
    {
        if (!$objectClass) {
            $objectClass = 'SilverStripe\\Security\\Member';
        }

        parent::__construct($objectClass);
    }

    /**
     * @var array
     */
    public $duplicateChecks = [
        'ID' => 'ID',
        'Email' => 'Email',
    ];

    public function processRecord($record, $columnMap, &$results, $preview = false)
    {
        $objID = parent::processRecord($record, $columnMap, $results, $preview);

        $_cache_groupByCode = [];

        // Add to predefined groups
        /** @var Member $member */
        $member = DataObject::get_by_id($this->objectClass, $objID);
        foreach ($this->groups as $group) {
            $member->Groups()->add($group);
        }

        // Add to groups defined in CSV
        if (isset($record['Groups']) && $record['Groups']) {
            $groupCodes = explode(',', $record['Groups'] ?? '');
            foreach ($groupCodes as $groupCode) {
                $groupCode = Convert::raw2url($groupCode);
                if (!isset($_cache_groupByCode[$groupCode])) {
                    $group = Group::get()->filter('Code', $groupCode)->first();
                    if (!$group) {
                        $group = new Group();
                        $group->Code = $groupCode;
                        $group->Title = $groupCode;
                        $group->write();
                    }
                    $member->Groups()->add($group);
                    $_cache_groupByCode[$groupCode] = $group;
                }
            }
        }

        $member->destroy();
        unset($member);

        return $objID;
    }

    /**
     * @param array $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
