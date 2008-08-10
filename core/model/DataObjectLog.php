<?php
/**
 * A DataObjectLog is a log of changes that have been made to the database in this session.
 * It was designed to help with updates to the CMS tree, and could be used wherever an Ajax call 
 * needs to update a complex on-screen representation of your data.
 * @package sapphire
 * @subpackage model
 */
class DataObjectLog extends Object {
	/**
	 * This must be set to true for the DataObjectLog to work
	 */
	static $enabled = false;
	
	
	/**
	 * The DataObjects that have been added to the database in this session.
	 * @var array
	 */
	static $added = array();
	
	/**
	 * The DataObjects that have been deleted from the database in this session.
	 * @var array
	 */
	static $deleted = array();
	
	/**
	 * The DataObjects that have been changed in the database in this session.
	 */
	static $changed = array();
	
	/**
	 * Add this DataObject as added in the log.
	 * @param DataObject $object
	 */
	static function addedObject($object) {
		if(self::$enabled) {
			self::$added[$object->class][] = $object;
		}
	}
	
	/**
	 * Add this DataObject as deleted in the log.
	 * @param DataObject $object
	 */
	static function deletedObject($object) {
		if(self::$enabled) {
			self::$deleted[$object->class][] = $object;	
		}
	}
	
	/**
	 * Add this DataObject as changed in the log.
	 * @param DataObject $object
	 */
	static function changedObject($object) {
		if(self::$enabled) {
			self::$changed[$object->class][] = $object;
		}
	}
	
	/**
	 * Get all DataObjects that have been added this session that are of
	 * the class or a subclass of the class provided.
	 * @param string $className The class name.
	 * @return array
	 */
	static function getAdded($className) {
		return self::getByClass($className, self::$added);
	}
	
	/**
	 * Get all DataObjects that have been deleted this session that are of
	 * the class or a subclass of the class provided.
	 * @param string $className The class name.
	 * @return array
	 */
	static function getDeleted($className) {
		return self::getByClass($className, self::$deleted);
	}
	
	/**
	 * Get all DataObjects that have been changed this session that are of
	 * the class or a subclass of the class provided.
	 * @param string $className The class name.
	 * @return array
	 */
	static function getChanged($className) {
		return self::getByClass($className, self::$changed);
	}
	
	/**
	 * Get all DataObjects in the given set that are of the class or a
	 * subclass of the class provided.
	 * @param string $className The class name.
	 * @param array $set The set to search in.
	 * @return array
	 */
	static function getByClass($className, $set) {
		$allClasses = ClassInfo::subclassesFor($className);
		foreach($allClasses as $subClass) {
			if(isset($set[$subClass])) {
				foreach($set[$subClass] as $page) {
					$result[$page->ID] = $page;
				}
			}
		}
		return isset($result) ? $result : null;
	}
}

?>