<?php
/**
 * This a database that intercepts calls to a SS_Database and use a caches to
 * remove the need for unneccesary calls to the database.
 *
 * It's recommended that a cache is configured to use a memory cache instead of
 * the default filebased cached. Following is an example of cache configuration
 * that can be used for using APC as a caching backend. It could be put in a
 * _config.php.
 * 
 * <code>
 * SS_Cache::add_backend('InMemory', 'Apc');
 * SS_Cache::pick_backend('InMemory', 'SS_DBCache', 1000);
 * </code>
 *
 * The cache works by building a cache key from a tables LastEdited column. The
 * value of that call is cached in it self and will need to be flushed when
 * changes to that table is executed.
 *
 * DB::flush_cache() will forward a call to this class with the relevant table
 * and the cache will be flushed.
 * 
 */
class CachedDatabase {

	/**
	 *
	 * @var SS_Database
	 */
	protected $database = null;

	/**
	 *
	 * @var boolean
	 */
	protected static $enable_cache = true;

	/**
	 *
	 * @var Zend_Cache_Core|Zend_Cache_Frontend
	 */
	protected static $query_cache = null;

	/**
	 *
	 * @var Zend_Cache_Core|Zend_Cache_Frontend
	 */
	protected static $lastedited_cache = null;

	/**
	 * Clear the lastedit cache for a given type, or pass nothing to clear all aggregate caches
	 *
	 * @param string $class
	 */
	public static function flush_cache($class=null) {
		$cache = self::get_lastedited_cache();

		if (!$class || $class == 'DataObject') {
			$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('lastedited'));
		} else {
			$tags = ClassInfo::ancestry($class);
			foreach($tags as &$tag) {
				$tag = preg_replace('/[^a-zA-Z0-9_]/', '_', $tag);
			}
			$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
		}
	}

	/**
	 * Build & cache the cache object
	 *
	 * @return Zend_Cache_Core|Zend_Cache_Frontend
	 */
	protected static function get_query_cache() {
		if(self::$query_cache) {  return self::$query_cache; }
		self::$query_cache = SS_Cache::factory('SS_DBCache', 'Core', array(
			'lifetime' => null,
			'automatic_serialization' => 'true'
		));
		return self::$query_cache;
	}

	/**
	 * Build & cache the cache object
	 *
	 * @return Zend_Cache_Core|Zend_Cache_Frontend
	 */
	protected static function get_lastedited_cache() {
		if(self::$lastedited_cache) {  return self::$lastedited_cache; }
		self::$lastedited_cache = SS_Cache::factory('SS_DBCache_LastEdited', 'Core', array(
			'lifetime' => null,
			'automatic_serialization' => 'false'
		));
		return self::$lastedited_cache;
	}

	/**
	 *
	 * @param SS_Database $database
	 */
	public function __construct(SS_Database $database) {
		$this->setDatabase($database);
	}

	/**
	 *
	 * @param SS_Database $database
	 */
	public function setDatabase($database) {
		$this->database = $database;
	}

	/**
	 *
	 * @return SS_Database
	 */
	public function getDatabase() {
		return $this->database;
	}

	/**
	 *
	 * @param SQLQuery $query
	 */
	public function execute(SQLQuery $query, $errorLevel = E_USER_ERROR) {
		$result = $this->load($query);
		if($result) {return $result;}
		$result = $this->getDatabase()->query($query->sql());
		$this->save($query, $result);
		return $result;
	}

	/**
	 * Proxy the rest of the method calls to the backend database
	 * 
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->getDatabase(), $name), $arguments);
	}

	/**
	 * If this query is cached, return it
	 *
	 * @param type $query
	 * @return boolean
	 */
	public function load(SQLQuery $query) {
		// Skip loading the cache if it's disabled or a flush in in effect
		if(!(self::$enable_cache && !isset($_GET['flush']))) {
			return false;
		}
		
		return self::get_query_cache()->load($this->getCacheKey($query));
	}

	/**
	 * Save the passed in data in the cache
	 *
	 * @param SQLQuery $query
	 * @param mixed $queryResult
	 * @return boolean
	 */
	public function save(SQLQuery $query, SS_Query $queryResult) {
		if(!self::$enable_cache) { 
			return;
		}

		$rows = array();
		foreach($queryResult as $row) {
			$rows[] = $row;
		}
		
		self::get_query_cache()->save(new CachedDatabase_Query($rows), $this->getCacheKey($query));
	}

	/**
	 * Generate a cache key for this query
	 *
	 * @param type $query
	 * @return boolean
	 */
	protected function getCacheKey(SQLQuery $query) {
		return sha1($this->getLastEditedTime($query).$query->sql());
	}

	/**
	 * Get the highest LastEdited time from a set of tables
	 *
	 * @param array $tables
	 * @return string - a datetime stamp
	 */
	protected function getLastEditedTime(SQLQuery $query) {
		$tables = $this->getInvolvedTableNames($query);
		$lastEdited = 0;
		foreach($tables as $table){
			$lastEdited = max($lastEdited, $this->getLastEdited($table));
		}
		return $lastEdited;
	}

	/**
	 * Get the last edited time for the type passed in.
	 *
	 * This is using a cache so it doesn't have to query the database at every
	 * hit. The cache gets flushed on calls to CachedDatabase::flush_cache()
	 *
	 * @param string $type
	 * @return string - the LastEdited time
	 */
	protected function getLastEdited($type) {
		if(!class_exists($type)) {
			return 0;
		}

		$sqlQuery = $this->getLastEditedQuery($type);
		$cachekey = sha1($sqlQuery->sql());
		$result = self::get_lastedited_cache()->load($cachekey);
		if ($result) {
			return $result;
		}
		$result = (string)$sqlQuery->execute()->value();
		if (!$result) {
			$result = '0';
		}
		self::get_lastedited_cache()->save($result, null, array('lastedited', preg_replace('/[^a-zA-Z0-9_]/', '_', $type)));
		return $result;
	}

	protected function getFirstAncestorWithLastEdited($type) {
		foreach (ClassInfo::ancestry($type, true) as $class) {
			$fields = DataObject::database_fields($class);
			if (array_key_exists('LastEdited', $fields)) {
				return $class;
			}
		}
		throw new LogicException("Couldn't find table for field LastEdited in type {$type}");
	}

	/**
	 * Get a query for fetching the highest LastEdited time for a DataObject
	 *
	 * @param string $type - the name of the DataObject
	 * @return SQLQuery
	 */
	protected function getLastEditedQuery($type) {
		$type = $this->getFirstAncestorWithLastEdited($type);
		$dataQuery = new DataQuery($type);
		$sqlQuery = $dataQuery->query();
		$sqlQuery->setSelect('Max("'.$type.'"."LastEdited")');
		singleton($type)->extend('augmentSQL', $sqlQuery, $dataQuery);
		$sqlQuery->setOrderBy();
		return $sqlQuery;
	}

	/**
	 * Find all tables involved from the passed in $query
	 *
	 * @param SQLQuery $query
	 * @return array
	 */
	protected function getInvolvedTableNames(SQLQuery $query){
		$tableNames = array();
		foreach($query->getFrom() as $from) {
			if(is_array($from)) {
				$from = $from['table'];
			}
			$tableNames[] = str_replace('"', '', $from);
		}
		return $tableNames;
	}
}

/**
 * This is a SS_Query that doesnt contain any active resource like so it can
 * be searialized and cached
 * 
 */
class CachedDatabase_Query extends SS_Query {

	/**
	 *
	 * @var array
	 */
	protected $records = array();

	/**
	 *
	 * @var booleand
	 */
	protected $queryHasBegun = false;

	/**
	 *
	 * @param array $list
	 */
	public function __construct($list) {
		$this->records = $list;
		reset($this->records);
	}

	/**
	 *
	 * @return array
	 */
	public function nextRecord() {
		if(!$this->queryHasBegun){
			$this->queryHasBegun = true;
			return current($this->records);
		}
		return next($this->records);
	}

	/**
	 *
	 * @return int
	 */
	public function numRecords() {
		return count($this->records);
	}

	/**
	 *
	 * @param type $rowNum
	 */
	public function seek($rowNum) {
		die('seek is called');
		if(isset($this->records[$rowNum])) {
			return $this->records[$rowNum];
		}
	}
}