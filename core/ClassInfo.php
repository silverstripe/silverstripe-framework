<?php
/**
 * Provides introspection information about the class tree.
 * It's a cached wrapper around the built-in class functions.  Sapphire uses class introspection heavily
 * and without the caching it creates an unfortunate performance hit.
 */
class ClassInfo {
	/**
	 * Returns true if the manifest has actually been built.
	 */	 
	static function ready() {
		global $_ALL_CLASSES;
		return $_ALL_CLASSES && isset($_ALL_CLASSES['hastable']) && $_ALL_CLASSES['hastable'];
	}
	static function allClasses() {
		global $_ALL_CLASSES;
		return $_ALL_CLASSES['exists'];
	}
	static function exists($class) {
		global $_ALL_CLASSES;
		return isset($_ALL_CLASSES['exists'][$class]) ? $_ALL_CLASSES['exists'][$class] : null;
	}
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
	 */
	static function dataClassesFor($class) {
		global $_ALL_CLASSES;
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
	 */
	static function baseDataClass($class) {
		global $_ALL_CLASSES;
		reset($_ALL_CLASSES['parents'][$class]);
		while($val = next($_ALL_CLASSES['parents'][$class])) {
			if($val == 'DataObject') break;
		}
		$baseDataClass = next($_ALL_CLASSES['parents'][$class]);
		return $baseDataClass ? $baseDataClass : $class;
	}
	
	static function subclassesFor($class){
		global $_ALL_CLASSES;
		$subclasses = isset($_ALL_CLASSES['children'][$class]) ? $_ALL_CLASSES['children'][$class] : null;
		if(isset($subclasses)) array_unshift($subclasses, $class);
		else $subclasses[$class] = $class;
		return $subclasses;
	}
	
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
	 * Return all the class names implementing the given interface.
	 * Note: this method is slow; if that becomes a problem you may need to reimplement this to cache results in
	 * the manifest.
	 * @return array A self-keyed array of class names
	 */
	protected static $implementors_of = array();
	static function implementorsOf($interfaceName) {
		if(array_key_exists($interfaceName, self::$implementors_of)) return self::$implementors_of[$interfaceName];

		$matchingClasses = array();	

		$classes = self::allClasses();
		foreach($classes as $potentialClass) {
			if(class_exists($potentialClass)) {
				$refl = new ReflectionClass($potentialClass);
				if($refl->implementsInterface($interfaceName)) {
					$matchingClasses[$potentialClass] = $potentialClass;
				}
			}
		}
		
		self::$implementors_of[$interfaceName] = $matchingClasses;

		return $matchingClasses;
	}
}
?>