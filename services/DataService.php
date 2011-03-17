<?php

/**
 * Abstraction of data access methods to remove tight coupling 
 * within DataObject and remove static methods. 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class DataService {
	
	private $validationEnabled = true;
	
	public function setValidationEnabled($v) {
		$this->validationEnabled = true;
	}

	/**
	 * Saves a data object. 
	 *
	 * Basically a copy/pasta of DataObject::write() for now. 
	 * 
	 * @param DataObject $object 
	 */
	public function write($object, $showDebug = false, $forceInsert = false, $forceWrite = false, $writeComponents = false) {
		
		$firstWrite = false;
		$object->brokenOnWrite = true;
		$isNewRecord = false;
		
		if($this->validationEnabled) {
			$valid = $object->validate();
			if(!$valid->valid()) {
				// Used by DODs to clean up after themselves, eg, Versioned
				$object->extend('onAfterSkippedWrite');
				throw new ValidationException($valid, "Validation error writing a $object->class object: " . $valid->message() . ".  Object not written.", E_USER_WARNING);
				return false;
			}
		}

		$object->onBeforeWrite();
		if($object->brokenOnWrite) {
			user_error("$object->class has a broken onBeforeWrite() function.  Make sure that you call parent::onBeforeWrite().", E_USER_ERROR);
		}

		$record = $object->toMap();
		
		// New record = everything has changed
		if(($object->ID && is_numeric($object->ID)) && !$forceInsert) {
			$dbCommand = 'update';

			// Update the changed array with references to changed obj-fields
			foreach($record as $k => $v) {
				if(is_object($v) && method_exists($v, 'isChanged') && $v->isChanged()) {
					$object->changed[$k] = true;
				}
			}

		} else{
			$dbCommand = 'insert';

			$object->changed = array();
			foreach($record as $k => $v) {
				$object->changed[$k] = 2;
			}
			
			$firstWrite = true;
		}

		// No changes made
		if($object->changed) {
			foreach($object->getClassAncestry() as $ancestor) {
				if(DataObject::has_own_table($ancestor))
				$ancestry[] = $ancestor;
			}

			// Look for some changes to make
			if(!$forceInsert) unset($object->changed['ID']);

			$hasChanges = false;
			foreach($object->changed as $fieldName => $changed) {
				if($changed) {
					$hasChanges = true;
					break;
				}
			}

			if($hasChanges || $forceWrite || !$record['ID']) {
					
				// New records have their insert into the base data table done first, so that they can pass the
				// generated primary key on to the rest of the manipulation
				$baseTable = $ancestry[0];
				
				if((!isset($record['ID']) || !$record['ID']) && isset($ancestry[0])) {	

					DB::query("INSERT INTO \"{$baseTable}\" (\"Created\") VALUES (" . DB::getConn()->now() . ")");
					$record['ID'] = DB::getGeneratedID($baseTable);
					$object->changed['ID'] = 2;

					$isNewRecord = true;
				}

				// Divvy up field saving into a number of database manipulations
				$manipulation = array();
				if(isset($ancestry) && is_array($ancestry)) {
					foreach($ancestry as $idx => $class) {
						$classSingleton = singleton($class);
						
						foreach($record as $fieldName => $fieldValue) {
							if(isset($object->changed[$fieldName]) && $object->changed[$fieldName] && $fieldType = $classSingleton->hasOwnTableDatabaseField($fieldName)) {
								$fieldObj = $object->dbObject($fieldName);
								if(!isset($manipulation[$class])) $manipulation[$class] = array();

								// if database column doesn't correlate to a DBField instance...
								if(!$fieldObj) {
									$fieldObj = DBField::create('Varchar', $record[$fieldName], $fieldName);
								}

								// Both CompositeDBFields and regular fields need to be repopulated
								$fieldObj->setValue($record[$fieldName], $record);

								if($class != $baseTable || $fieldName!='ID')
									$fieldObj->writeToManipulation($manipulation[$class]);
							}
						}

						// Add the class name to the base object
						if($idx == 0) {
							$manipulation[$class]['fields']["LastEdited"] = "'".SS_Datetime::now()->Rfc2822()."'";
							if($dbCommand == 'insert') {
								$manipulation[$class]['fields']["Created"] = "'".SS_Datetime::now()->Rfc2822()."'";
								//echo "<li>$object->class - " .get_class($this);
								$manipulation[$class]['fields']["ClassName"] = "'$object->class'";
							}
						}

						// In cases where there are no fields, this 'stub' will get picked up on
						if(DataObject::has_own_table($class)) {
							$manipulation[$class]['command'] = $dbCommand;
							$manipulation[$class]['id'] = $record['ID'];
						} else {
							unset($manipulation[$class]);
						}
					}
				}
				$object->extend('augmentWrite', $manipulation);
				
				// New records have their insert into the base data table done first, so that they can pass the
				// generated ID on to the rest of the manipulation
				if(isset($isNewRecord) && $isNewRecord && isset($manipulation[$baseTable])) {
					$manipulation[$baseTable]['command'] = 'update';
				}
				
				DB::manipulate($manipulation);

				if(isset($isNewRecord) && $isNewRecord) {
					DataObjectLog::addedObject($this);
				} else {
					DataObjectLog::changedObject($this);
				}
				
				$object->onAfterWrite();

				$object->changed = null;
			} elseif ( $showDebug ) {
				echo "<b>Debug:</b> no changes for DataObject<br />";
				// Used by DODs to clean up after themselves, eg, Versioned
				$object->extend('onAfterSkippedWrite');
			}

			// Clears the cache for this object so get_one returns the correct object.
			$object->flushCache();

			if(!isset($record['Created'])) {
				$record['Created'] = SS_Datetime::now()->Rfc2822();
			}
			$record['LastEdited'] = SS_Datetime::now()->Rfc2822();
		} else {
			// Used by DODs to clean up after themselves, eg, Versioned
			$object->extend('onAfterSkippedWrite');
		}

		// Write ComponentSets as necessary
		if($writeComponents) {
			$object->writeComponents(true);
		}
		return $record['ID'];
	}
}
