<?php
/**
 * @todo Migrate Permission->Arg and Permission->Type values
 * 
 * @package framework
 * @subpackage security
 */
class GroupCsvBulkLoader extends CsvBulkLoader {

	public $duplicateChecks = array(
		'Code' => 'Code',
	);
	
	public function __construct($objectClass = null) {
		if(!$objectClass) $objectClass = 'Group';
		
		parent::__construct($objectClass);
	}
	
	public function processRecord($record, $columnMap, &$results, $preview = false) {
		// We match by 'Code', the ID property is confusing the importer
		if(isset($record['ID'])) unset($record['ID']);
		
		$objID = parent::processRecord($record, $columnMap, $results, $preview);

		$group = DataObject::get_by_id($this->objectClass, $objID);
		// set group hierarchies - we need to do this after all records
		// are imported to avoid missing "early" references to parents
		// which are imported later on in the CSV file.
		if(isset($record['ParentCode']) && $record['ParentCode']) {
			$parentGroup = DataObject::get_one(
				'Group',
				sprintf('"Code" = \'%s\'', Convert::raw2sql($record['ParentCode']))
			);
			if($parentGroup) {
				$group->ParentID = $parentGroup->ID;
				$group->write();
			}
		}
		
		// set permission codes - these are all additive, meaning
		// existing permissions arent cleared.
		if(isset($record['PermissionCodes']) && $record['PermissionCodes']) {
			foreach(explode(',', $record['PermissionCodes']) as $code) {
				$p = DataObject::get_one(
					'Permission',
					sprintf(
						'"Code" = \'%s\' AND "GroupID" = %d', 
						Convert::raw2sql($code),
						$group->ID
					)
				);
				if(!$p) {
					$p = new Permission(array('Code' => $code));
					$p->write();
				}
				$group->Permissions()->add($p);
			}
		}
	
		return $objID;
	}

}
