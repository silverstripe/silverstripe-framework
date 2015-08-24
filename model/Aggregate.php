<?php

/**
 * Calculate an Aggregate on a particular field of a particular DataObject type (possibly with
 * an additional filter before the aggregate)
 * 
 * Implemented as a class to provide a semi-DSL method of calculating Aggregates. DataObject has a function
 * that will create & return an instance of this class with the DataObject type and filter set,
 * but at that point we don't yet know the aggregate function or field
 * 
 * This class captures any XML_val or unknown call, and uses that to get the field & aggregate function &
 * then return the result
 * 
 * Two ways of calling
 * 
 * $aggregate->XML_val(aggregate_function, array(field))     - For templates
 * $aggregate->aggregate_function(field)                     - For PHP
 * 
 * Aggregate functions are uppercased by this class, but are otherwise assumed to be valid SQL functions. Some
 * examples: Min, Max, Avg
 * 
 * Aggregates are often used as portions of a cacheblock key. They are therefore cached themselves, in the 'aggregate'
 * cache, although the invalidation logic prefers speed over keeping valid data. 
 * The aggregate cache is cleared through {@link DataObject::flushCache()}, which in turn is called on
 * {@link DataObject->write()} and other write operations. 
 * This means most write operations to the database will invalidate the cache correctly.
 * Use {@link Aggregate::flushCache()} to manually clear.
 * 
 * NOTE: The cache logic uses tags, and so a backend that supports tags is required. Currently only the File
 * backend (and the two-level backend with the File backend as the slow store) meets this requirement
 * 
 * @deprecated 3.1 Use DataList to aggregate data
 * 
 * @author hfried
 * @package framework
 * @subpackage core
 */
class Aggregate extends ViewableData {

	private static $cache = null;
	
	/** Build & cache the cache object */
	protected static function cache() {
		return self::$cache ? self::$cache : (self::$cache = SS_Cache::factory('aggregate'));
	}

	/** 
	 * Clear the aggregate cache for a given type, or pass nothing to clear all aggregate caches.
	 * {@link $class} is just effective if the cache backend supports tags.
	 */
	public static function flushCache($class=null) {
		$cache = self::cache();
		$capabilities = $cache->getBackend()->getCapabilities();
		if($capabilities['tags'] && (!$class || $class == 'DataObject')) {
			$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('aggregate'));
		} elseif($capabilities['tags']) {
			$tags = ClassInfo::ancestry($class);
			foreach($tags as &$tag) {
				$tag = preg_replace('/[^a-zA-Z0-9_]/', '_', $tag);
			}
			$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
		} else {
			$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
	}
	
	/**
	 * Constructor
	 * 
	 * @deprecated 3.1 Use DataList to aggregate data
	 * 
	 * @param string $type The DataObject type we are building an aggregate for
	 * @param string $filter (optional) An SQL filter to apply to the selected rows before calculating the aggregate
	 */
	public function __construct($type, $filter = '') {
		Deprecation::notice('4.0', 'Call aggregate methods on a DataList directly instead. In templates'
			. ' an example of the new syntax is &lt% cached List(Member).max(LastEdited) %&gt instead'
			. ' (check partial-caching.md documentation for more details.)');
		$this->type = $type;
		$this->filter = $filter;
		parent::__construct();
	}

	/**
	 * Build the SQLSelect to calculate the aggregate
	 * This is a seperate function so that subtypes of Aggregate can change just this bit
	 * @param string $attr - the SQL field statement for selection (i.e. "MAX(LastUpdated)")
	 * @return SQLSelect
	 */
	protected function query($attr) {
		$query = DataList::create($this->type)->where($this->filter);
		$query->setSelect($attr);
		$query->setOrderBy(array()); 
		$singleton->extend('augmentSQL', $query);
		return $query;
	}
	
	/**
	 * Entry point for being called from a template.
	 * 
	 * This gets the aggregate function 
	 * 
	 */
	public function XML_val($name, $args = null, $cache = false) {
		$func = strtoupper( strpos($name, 'get') === 0 ? substr($name, 3) : $name );
		$attribute = $args ? $args[0] : 'ID';
		
		$table = null;
		
		foreach (ClassInfo::ancestry($this->type, true) as $class) {
			$fields = DataObject::database_fields($class, false);
			if (array_key_exists($attribute, $fields)) { $table = $class; break; }
		}
		
		if (!$table) user_error("Couldn't find table for field $attribute in type {$this->type}", E_USER_ERROR);
		
		$query = $this->query("$func(\"$table\".\"$attribute\")");
		
		// Cache results of this specific SQL query until flushCache() is triggered.
		$sql = $query->sql($parameters);
		$cachekey = sha1($sql.'-'.var_export($parameters, true));
		$cache = self::cache();
		
		if (!($result = $cache->load($cachekey))) {
			$result = (string)$query->execute()->value(); if (!$result) $result = '0';
			$cache->save($result, null, array('aggregate', preg_replace('/[^a-zA-Z0-9_]/', '_', $this->type)));
		}
		
		return $result;
	}
	
	/**
	 * Entry point for being called from PHP.
	 */
	public function __call($method, $arguments) {
		return $this->XML_val($method, $arguments);
	}
}

/**
 * A subclass of Aggregate that calculates aggregates for the result of a has_many query.
 *
 * @deprecated
 * 
 * @author hfried
 * @package framework
 * @subpackage core
 */
class Aggregate_Relationship extends Aggregate {

	/**
	 * Constructor
	 * 
	 * @param DataObject $object The object that has_many somethings that we're calculating the aggregate for 
	 * @param string $relationship The name of the relationship
	 * @param string $filter (optional) An SQL filter to apply to the relationship rows before calculating the
	 *                       aggregate
	 */
	public function __construct($object, $relationship, $filter = '') {
		$this->object = $object;
		$this->relationship = $relationship;
		
		$this->has_many = $object->has_many($relationship);
		$this->many_many = $object->many_many($relationship);

		if (!$this->has_many && !$this->many_many) {
			user_error("Could not find relationship $relationship on object class {$object->class} in"
				. " Aggregate Relationship", E_USER_ERROR);
		}
		
		parent::__construct($this->has_many ? $this->has_many : $this->many_many[1], $filter);
	}
	
	protected function query($attr) {
		if ($this->has_many) {
			$query = $this->object->getComponentsQuery($this->relationship, $this->filter);
		}
		else {
			$query = $this->object->getManyManyComponentsQuery($this->relationship, $this->filter);
		}
		
		$query->setSelect($attr);
		$query->setGroupBy(array());
		
		$singleton = singleton($this->type);
		$singleton->extend('augmentSQL', $query);

		return $query;
	}
}
