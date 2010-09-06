<?php
/**
 * Provides introspection information about the class tree.
 * It's a cached wrapper around the built-in class functions.  Sapphire uses class introspection heavily
 * and without the caching it creates an unfortunate performance hit.
 *
 * @package sapphire
 * @subpackage core
 */
class ClassInfo {
		/**
	 * @todo Improve documentation
	 */
	static function allClasses() {
		global $_ALL_CLASSES;
		return $_ALL_CLASSES['exists'];
	}

	/**
	 * @todo Improve documentation
	 */
	static function exists($class) {
		global $_ALL_CLASSES;
		return isset($_ALL_CLASSES['exists'][$class]) ? $_ALL_CLASSES['exists'][$class] : null;
	}

	/**
	 * Cache for {@link hasTable()}
	 */
	private static $_cache_all_tables = null;
	
	/**
	 * @todo Move this to SS_Database or DB
	 */
	static function hasTable($class) {
		if(DB::isActive()) {
			// Cache the list of all table names to reduce on DB traffic
			if(empty(self::$_cache_all_tables)) {
				self::$_cache_all_tables = array();
				$tables = DB::query(DB::getConn()->allTablesSQL())->column();
				foreach($tables as $table) self::$_cache_all_tables[strtolower($table)] = true;
			}
			return isset(self::$_cache_all_tables[strtolower($class)]);
		} else {
			return false;
		}
	}
	
	static function reset_db_cache() {
		self::$_cache_all_tables = null;
	}
	
	/**
	 * Returns the manifest of all classes which are present in the database.
	 * @param string $class Class name to check enum values for ClassName field
	 */
	static function getValidSubClasses($class = 'SiteTree') {
		return DB::getConn()->enumValuesForField($class, 'ClassName');
	}

	/**
	 * Return the database tables linked to this class.
	 * Gets an array of the current class, it subclasses and its ancestors.  It then filters that list
	 * to those with DB tables
	 * 
	 * @param mixed $class string of the classname or instance of the class
	 * @todo Move this into data object
	 * @return array
	 */
	static function dataClassesFor($class) {
		global $_ALL_CLASSES;
		if (is_object($class)) $class = get_class($class);
		
		$dataClasses = array();
		
		if(!$_ALL_CLASSES['parents'][$class]) user_error("ClassInfo::dataClassesFor() no parents for $class", E_USER_WARNING);
		foreach($_ALL_CLASSES['parents'][$class] as $subclass) {
			if(self::hasTable($subclass)) $dataClasses[] = $subclass;
		}
		
		if(self::hasTable($class)) $dataClasses[] = $class;

		if(isset($_ALL_CLASSES['children'][$class]))
		foreach($_ALL_CLASSES['children'][$class] as $subclass)
		{
			if(self::hasTable($subclass)) $dataClasses[] = $subclass;
		}
			
		return $dataClasses;
	}
	
	/**
	 * Return the root data class for that class.
	 * This root table has a lot of special use in the DataObject system.
	 * 
	 * @param mixed $class string of the classname or instance of the class
	 * @return array
	 */
	static function baseDataClass($class) {
		global $_ALL_CLASSES;
		if (is_object($class)) $class = get_class($class);
		reset($_ALL_CLASSES['parents'][$class]);
		while($val = next($_ALL_CLASSES['parents'][$class])) {
			if($val == 'DataObject') break;
		}
		$baseDataClass = next($_ALL_CLASSES['parents'][$class]);
		return $baseDataClass ? $baseDataClass : $class;
	}
	
	/**
	 * Returns a list of classes that inherit from the given class.
	 * The resulting array includes the base class passed
	 * through the $class parameter as the first array value.
	 * 
	 * Example usage:
	 * <code>
	 * ClassInfo::subclassesFor('BaseClass');
	 * 	array(
	 * 	0 => 'BaseClass',
	 * 	'ChildClass' => 'ChildClass',
	 * 	'GrandChildClass' => 'GrandChildClass'
	 * )
	 * </code>
	 * 
	 * @param mixed $class string of the classname or instance of the class
	 * @return array Names of all subclasses as an associative array.
	 */
	static function subclassesFor($class){
		global $_ALL_CLASSES;
		if (is_object($class)) $class = get_class($class);
		
		// get all classes from the manifest
		$subclasses = isset($_ALL_CLASSES['children'][$class]) ? $_ALL_CLASSES['children'][$class] : null;

		// add the base class to the array
		if(isset($subclasses)) {
			array_unshift($subclasses, $class);
		} else {
			$subclasses[$class] = $class;
		}
		
		return $subclasses;
	}
	
	/**
	 * @todo Improve documentation
	 */
	static function ancestry($class, $onlyWithTables = false) {
		global $_ALL_CLASSES;

		if(is_object($class)) $class = $class->class;
		else if(!is_string($class)) user_error("Bad class value " . var_export($class, true) . " passed to ClassInfo::ancestry()", E_USER_WARNING);

		$items = $_ALL_CLASSES['parents'][$class];
		$items[$class] = $class;
		if($onlyWithTables) foreach($items as $item) {
			if(!DataObject::has_own_table($item)) unset($items[$item]);
		}
		return $items;
	}

	/**
	 * @return array A self-keyed array of class names. Note that this is only available with Silverstripe
	 * classes and not built-in PHP classes.
	 */
	static function implementorsOf($interfaceName) {
	    global $_ALL_CLASSES;
		return (isset($_ALL_CLASSES['implementors'][$interfaceName])) ? $_ALL_CLASSES['implementors'][$interfaceName] : false;
	}

	/**
	 * Returns true if the given class implements the given interface
	 */
	static function classImplements($className, $interfaceName) {
	    global $_ALL_CLASSES;
		return isset($_ALL_CLASSES['implementors'][$interfaceName][$className]);
	}

	/**
	 * Returns true if $subclass is a subclass of $parentClass.
	 * Identical to the PHP built-in function, but faster.
	 */
	static function is_subclass_of($subclass, $parentClass) {
		global $_ALL_CLASSES;
		return isset($_ALL_CLASSES['parents'][$subclass][$parentClass]);
	}
	
	/**
	 * Get all classes contained in a file.
	 * @uses ManifestBuilder
	 * 
	 * @todo Doesn't return additional classes that only begin
	 *  with the filename, and have additional naming separated through underscores.
	 * 
	 * @param string $filePath Path to a PHP file (absolute or relative to webroot)
	 * @return array
	 */
	static function classes_for_file($filePath) {
		$absFilePath = Director::getAbsFile($filePath);
		global $_CLASS_MANIFEST;
		
		$matchedClasses = array();
		foreach($_CLASS_MANIFEST as $class => $compareFilePath) {
			if($absFilePath == $compareFilePath) $matchedClasses[] = $class;
		}
		
		return $matchedClasses;
	}
	
	/**
	 * Returns all classes contained in a certain folder.
	 *
	 * @todo Doesn't return additional classes that only begin
	 *  with the filename, and have additional naming separated through underscores.
	 * 
	 * @param string $folderPath Relative or absolute folder path
	 * @return array Array of class names
	 */
	static function classes_for_folder($folderPath) {
		$absFolderPath = Director::getAbsFile($folderPath);
		global $_CLASS_MANIFEST;

		$matchedClasses = array();
		foreach($_CLASS_MANIFEST as $class => $compareFilePath) {
			if(stripos($compareFilePath, $absFolderPath) === 0) $matchedClasses[] = $class;
		}

		return $matchedClasses;
	}
	
	/////////////////////////////////////////////////////////////////////////////
	// DEPRECATED
	
	/**
	 * @deprecated Use Security::database_is_ready() instead.
	 */
	static function ready() {
		user_error("ClassInfo::ready() deprectaed - use Security::database_is_ready()", E_USER_NOTICE);
		return Security::database_is_ready();
	}
	
}
?>
