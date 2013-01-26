<?php
/**
 * Provides introspection information about the class tree.
 * It's a cached wrapper around the built-in class functions.  SilverStripe uses class introspection heavily
 * and without the caching it creates an unfortunate performance hit.
 *
 * @package framework
 * @subpackage core
 */
class ClassInfo {
	/**
	 * Wrapper for classes getter.
	 */
	public static function allClasses() {
		return SS_ClassLoader::instance()->getManifest()->getClasses();
	}

	/**
	 * @todo Improve documentation
	 */
	public static function exists($class) {
		return SS_ClassLoader::instance()->classExists($class);
	}

	/**
	 * Cache for {@link hasTable()}
	 */
	private static $_cache_all_tables = null;

	/**
	 * @var Array Cache for {@link ancestry()}.
	 */
	private static $_cache_ancestry = array();
	
	/**
	 * @todo Move this to SS_Database or DB
	 */
	public static function hasTable($class) {
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
	
	public static function reset_db_cache() {
		self::$_cache_all_tables = null;
		self::$_cache_ancestry = array();
	}
	
	/**
	 * Returns the manifest of all classes which are present in the database.
	 * @param string $class Class name to check enum values for ClassName field
	 */
	public static function getValidSubClasses($class = 'SiteTree', $includeUnbacked = false) {
		$classes = DB::getConn()->enumValuesForField($class, 'ClassName');
		if (!$includeUnbacked) $classes = array_filter($classes, array('ClassInfo', 'exists'));
		return $classes;
	}

	/**
	 * Returns an array of the current class and all its ancestors and children
	 * which have a DB table.
	 * 
	 * @param string|object $class
	 * @todo Move this into data object
	 * @return array
	 */
	public static function dataClassesFor($class) {
		$result = array();

		if (is_object($class)) {
			$class = get_class($class);
		}

		$classes = array_merge(
			self::ancestry($class),
			self::subclassesFor($class));

		foreach ($classes as $class) {
			if (self::hasTable($class)) $result[$class] = $class;
		}

		return $result;
	}

	/**
	 * Returns the root class (the first to extend from DataObject) for the
	 * passed class.
	 *
	 * @param  string|object $class
	 * @return string
	 */
	public static function baseDataClass($class) {
		if (is_object($class)) $class = get_class($class);

		if (!is_subclass_of($class, 'DataObject')) {
			throw new InvalidArgumentException("$class is not a subclass of DataObject");
		}

		while ($next = get_parent_class($class)) {
			if ($next == 'DataObject') {
				return $class;
			}

			$class = $next;
		}
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
	public static function subclassesFor($class) {
		$descendants = SS_ClassLoader::instance()->getManifest()->getDescendantsOf($class);
		$result      = array($class => $class);

		if ($descendants) {
			return $result + ArrayLib::valuekey($descendants);
		} else {
			return $result;
		}
	}

	/**
	 * Returns the passed class name along with all its parent class names in an
	 * array, sorted with the root class first.
	 *
	 * @param  string $class
	 * @param  bool $tablesOnly Only return classes that have a table in the db.
	 * @return array
	 */
	public static function ancestry($class, $tablesOnly = false) {
		if (!is_string($class)) $class = get_class($class);

		$cacheKey = $class . '_' . (string)$tablesOnly;
		$parent = $class;
		if(!isset(self::$_cache_ancestry[$cacheKey])) {
			$ancestry = array();
			do {
				if (!$tablesOnly || DataObject::has_own_table($parent)) {
					$ancestry[$parent] = $parent;
				}
			} while ($parent = get_parent_class($parent));
			self::$_cache_ancestry[$cacheKey] = array_reverse($ancestry);	
		}

		return self::$_cache_ancestry[$cacheKey];
	}

	/**
	 * @return array A self-keyed array of class names. Note that this is only available with Silverstripe
	 * classes and not built-in PHP classes.
	 */
	public static function implementorsOf($interfaceName) {
		return SS_ClassLoader::instance()->getManifest()->getImplementorsOf($interfaceName);
	}

	/**
	 * Returns true if the given class implements the given interface
	 */
	public static function classImplements($className, $interfaceName) {
		return in_array($className, SS_ClassLoader::instance()->getManifest()->getImplementorsOf($interfaceName));
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
	public static function classes_for_file($filePath) {
		$absFilePath    = Director::getAbsFile($filePath);
		$matchedClasses = array();
		$manifest       = SS_ClassLoader::instance()->getManifest()->getClasses();

		foreach($manifest as $class => $compareFilePath) {
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
	public static function classes_for_folder($folderPath) {
		$absFolderPath  = Director::getAbsFile($folderPath);
		$matchedClasses = array();
		$manifest       = SS_ClassLoader::instance()->getManifest()->getClasses();

		foreach($manifest as $class => $compareFilePath) {
			if(stripos($compareFilePath, $absFolderPath) === 0) $matchedClasses[] = $class;
		}

		return $matchedClasses;
	}

	private static $method_from_cache = array();

	public static function has_method_from($class, $method, $compclass) {
		if (!isset(self::$method_from_cache[$class])) self::$method_from_cache[$class] = array();

		if (!array_key_exists($method, self::$method_from_cache[$class])) {
			self::$method_from_cache[$class][$method] = false;

			$classRef = new ReflectionClass($class);

			if ($classRef->hasMethod($method)) {
				$methodRef = $classRef->getMethod($method);
				self::$method_from_cache[$class][$method] = $methodRef->getDeclaringClass()->getName();
			}
		}

		return self::$method_from_cache[$class][$method] == $compclass;
	}
	
}

