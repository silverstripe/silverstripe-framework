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
	 * Returns true if the manifest has actually been built.
	 */	 
	static function ready() {
		global $_ALL_CLASSES;
		return $_ALL_CLASSES && $_ALL_CLASSES['hastable'];
	}
	
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
	 * @todo Improve documentation
	 */
	static function hasTable($class) {
		global $_ALL_CLASSES;
		return isset($_ALL_CLASSES['hastable'][$class]) ? $_ALL_CLASSES['hastable'][$class] : null;
	}
	
	/**
	 * Returns the manifest of all classes which are present in the database.
	 */
	static function getValidSubClasses(){
		// Get the enum of all page types from the SiteTree table
		$classnameinfo = DB::query("DESCRIBE SiteTree ClassName")->first();
		preg_match_all("/'[^,]+'/", $classnameinfo["Type"], $matches);
		
		foreach($matches[0] as $value) {
			$classes[] = trim($value, "'");
		}
		return $classes;
	}

	/**
	 * Return the database tables linked to this class.
	 * Gets an array of the current class, it subclasses and its ancestors.  It then filters that list
	 * to those with DB tables
	 * 
	 * @param mixed $class string of the classname or instance of the class
	 * @return array
	 */
	static function dataClassesFor($class) {
		global $_ALL_CLASSES;
		if (is_object($class)) $class = get_class($class);
		
		$dataClasses = array();
		
		if(!$_ALL_CLASSES['parents'][$class]) user_error("ClassInfo::dataClassesFor() no parents for $class", E_USER_WARNING);
		foreach($_ALL_CLASSES['parents'][$class] as $subclass) {
			if(isset($_ALL_CLASSES['hastable'][$subclass])){
				$dataClasses[] = $subclass;
			}
		}
		
		if(isset($_ALL_CLASSES['hastable'][$class])) $dataClasses[] = $class;

		if(isset($_ALL_CLASSES['children'][$class]))
		foreach($_ALL_CLASSES['children'][$class] as $subclass)
		{
			if(isset($_ALL_CLASSES['hastable'][$subclass]))
			{
				$dataClasses[] = $subclass;
			}
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
	 * 
	 * @param mixed $class string of the classname or instance of the class
	 * @return array
	 */
	static function subclassesFor($class){
		global $_ALL_CLASSES;
		if (is_object($class)) $class = get_class($class);
		$subclasses = isset($_ALL_CLASSES['children'][$class]) ? $_ALL_CLASSES['children'][$class] : null;
		if(isset($subclasses)) array_unshift($subclasses, $class);
		else $subclasses[$class] = $class;
		return $subclasses;
	}
	
	/**
	 * @todo Improve documentation
	 */
	static function ancestry($class, $onlyWithTables = false) {
		global $_ALL_CLASSES;
		
		if(!is_string($class)) $class = $class->class;
		$items = $_ALL_CLASSES['parents'][$class];
		$items[$class] = $class;
		if($onlyWithTables) foreach($items as $item) {
			if(!$_ALL_CLASSES['hastable'][$item]) unset($items[$item]);
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
}
?>