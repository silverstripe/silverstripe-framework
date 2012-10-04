<?php
/**
 * Imports member records, and checks/updates duplicates based on their
 * 'Email' property.
 * 
 * @package framework
 * @subpackage security
 */
class MemberCsvBulkLoader extends CsvBulkLoader {
	
	/**
	 * @var array Array of {@link Group} records. Import into a specific group.
	 *  Is overruled by any "Groups" columns in the import.
	 */
	protected $groups = array();
	
	public function __construct($objectClass = null) {
		if(!$objectClass) $objectClass = 'Member';
		
		parent::__construct($objectClass);
	}
	
	public $duplicateChecks = array(
		'Email' => 'Email',
	);
	
	public function processRecord($record, $columnMap, &$results, $preview = false) {
		$objID = parent::processRecord($record, $columnMap, $results, $preview);
		
		$_cache_groupByCode = array();
		
		// Add to predefined groups
		$member = DataObject::get_by_id($this->objectClass, $objID);
		foreach($this->groups as $group) {
			// TODO This isnt the most memory effective way to add members to a group
			$member->Groups()->add($group);
		}
		
		// Add to groups defined in CSV
		if(isset($record['Groups']) && $record['Groups']) {
			$groupCodes = explode(',', $record['Groups']);
			foreach($groupCodes as $groupCode) {
				if(!isset($_cache_groupByCode[$groupCode])) {
					$group = DataObject::get_one(
						'Group', 
						sprintf('"Code" = \'%s\'', Convert::raw2sql($groupCode))
					);
					if(!$group) {
						$group = new Group();
						$group->Code = $groupCode;
						$group->Title = $groupCode;
						$group->write();
					}
					$member->Groups()->add($group);
				}
				$_cache_groupByCode[$groupCode] = $group;
			}
		}
		
		$member->destroy();
		unset($member);
		
		return $objID;
	}
	
	/**
	 * @param Array $groups
	 */
	public function setGroups($groups) {
		$this->groups = $groups;
	}
	
	/**
	 * @return Array
	 */
	public function getGroups() {
		return $this->groups;
	}
} 
