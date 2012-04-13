<?php 
/**
 * SS_Cache provides a bunch of static functions wrapping the Zend_Cache system in something a little more
 * easy to use with the SilverStripe config system.
 * 
 * A Zend_Cache has both a frontend (determines how to get the value to cache, and how to serialize it for storage)
 * and a backend (handles the actual storage).
 * 
 * Rather than require library code to specify the backend directly, cache consumers provide a name for the cache
 * backend they want. The end developer can then specify which backend to use for each name in their project's
 * _config.php. They can also use 'all' to provide a backend for all named caches
 * 
 * End developers provide a set of named backends, then pick the specific backend for each named cache. There is a 
 * default File cache set up as the 'default' named backend, which is assigned to 'all' named caches
 * 
 * USING A CACHE
 * 
 * $cache = SS_Cache::factory('foo') ; // foo is any name (try to be specific), and is used to get configuration & storage info
 * 
 * if (!($result = $cache->load($cachekey))) {
 * 	$result = caluate some how;
 * 	$cache->save($result);
 * }
 * 
 * return $result;
 * 
 * Normally there's no need to remove things from the cache - the cache backends clear out entries based on age & maximum 
 * allocated storage. If you include the version of the object in the cache key, even object changes don't need any invalidation
 * 
 * DISABLING CACHING IN DEV MOVE
 * 
 * (in _config.php)
 * 
 * if (Director::isDev()) SS_Cache::set_cache_lifetime('any', -1, 100);
 * 
 * USING MEMCACHED AS STORE
 * 
 * (in _config.php)
 * 
 * SS_Cache::add_backend('primary_memcached', 'Memcached',
 * 	array('host' => 'localhost', 'port' => 11211, 'persistent' => true, 'weight' => 1, 'timeout' => 5, 'retry_interval' => 15, 'status' => true, 'failure_callback' => '' )
 * );
 * 
 * SS_Cache::pick_backend('primary_memcached', 'any', 10);
 * SS_Cache::pick_backend('default', 'aggregate', 20); // Aggregate needs a backend with tag support, which memcached doesn't provide
 * </code>
 * 
 * USING APC AND FILE AS TWO LEVEL STORE
 * 
 * (in _config.php)
 * 
 * SS_Cache::add_backend('two-level', 'TwoLevels', array(
 * 	'slow_backend' => 'File',
 * 	'fast_backend' => 'Apc',
 * 	'slow_backend_options' => array('cache_dir' => TEMP_FOLDER . DIRECTORY_SEPARATOR . 'cache')
 * ));
 * 
 * SS_Cache::pick_backend('two-level', 'any', 10); // No need for special backend for aggregate - TwoLevels with a File slow backend supports tags
 * 
 * @author hfried
 * @package framework
 * @subpackage core
 */
class SS_Cache {
	
	protected static $backends = array();
	protected static $backend_picks = array();
	
	protected static $cache_lifetime = array();
	
	/**
	 * Initialize the 'default' named cache backend
	 */
	protected static function init(){
		if (!isset(self::$backends['default'])) {
			$cachedir = TEMP_FOLDER . DIRECTORY_SEPARATOR . 'cache';
			if (!is_dir($cachedir)) mkdir($cachedir);
			self::$backends['default'] = array('File', array('cache_dir' => TEMP_FOLDER . DIRECTORY_SEPARATOR . 'cache'));
		}
	}

	/**
	 * Add a new named cache backend
	 * 
	 * @param string $name The name of this backend as a freeform string
	 * @param string $type The Zend_Cache backend ('File' or 'Sqlite' or ...)
	 * @param array $options The Zend_Cache backend options (see http://framework.zend.com/manual/en/zend.cache.html)
	 * @return none
	 */
	static function add_backend($name, $type, $options=array()) {
		self::init();
		self::$backends[$name] = array($type, $options);
	}
	
	/**
	 * Pick a named cache backend for a particular named cache
	 *  
	 * @param string $name The name of the backend, as passed as the first argument to add_backend
	 * @param string $for The name of the cache to pick this backend for (or 'any' for any backend)
	 * @param integer $priority The priority of this pick - the call with the highest number will be the actual backend picked.
	 *                          A backend picked for a specific cache name will always be used instead of 'any' if it exists, no matter the priority.
	 * @return none
	 */
	static function pick_backend($name, $for, $priority=1) {
		self::init();

		$current = -1;
		if (isset(self::$backend_picks[$for])) $current = self::$backend_picks[$for]['priority'];

		if ($priority >= $current) self::$backend_picks[$for] = array('name' => $name, 'priority' => $priority); 
	}
	
	/**
	 * Set the cache lifetime for a particular named cache
	 * 
	 * @param string $for The name of the cache to set this lifetime for (or 'any' for all backends)
	 * @param integer $lifetime The lifetime of an item of the cache, in seconds, or -1 to disable caching
	 * @param integer $priority The priority. The highest priority setting is used. Unlike backends, 'any' is not special in terms of priority. 
	 */
	static function set_cache_lifetime($for, $lifetime=600, $priority=1) {
		self::init();
		
		$current = -1;
		if (isset(self::$cache_lifetime[$for])) $current = self::$cache_lifetime[$for]['priority'];
		
		if ($priority >= $current) self::$cache_lifetime[$for] = array('lifetime' => $lifetime, 'priority' => $priority); 
	}
	
	/**
	 * Build a cache object
	 * @param string $for The name of the cache to build the cache object for
	 * @param string $frontend (optional) The type of Zend_Cache frontend to use. Output is almost always the best
	 * @param array $frontendOptions (optional) Any frontend options to use.
	 * 
	 * @return The cache object. It has been partitioned so that actions on the object
	 *	won't affect cache objects generated with a different $for value, even those using the same Zend_Backend 
	 * 
	 * -- Cache a calculation
	 * 
	 * if (!($result = $cache->load($cachekey))) {
	 * 	$result = caluate some how;
	 * 	$cache->save($result);
	 * }
	 * 
	 * return $result;
	 * 
	 * -- Cache captured output
	 * 
	 * if (!($cache->start($cachekey))) {
	 * 
	 * 	// output everything as usual
	 * 	echo 'Hello world! ';
	 * 	echo 'This is cached ('.time().') ';
	 * 
	 * 	$cache->end(); // output buffering ends
	 * }
	 * 
	 * -- Invalidate an element
	 * 
	 * $cache->remove($cachekey);
	 * 
	 * -- Clear the cache (warning - this clears the entire backend, not just this named cache partition)
	 * 
	 * $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
	 * 
	 * See the Zend_Cache documentation at http://framework.zend.com/manual/en/zend.cache.html for more
	 * 
	 */
	static function factory($for, $frontend='Output', $frontendOptions=null) {
		self::init();
		
		$backend_name = 'default'; $backend_priority = -1;
		$cache_lifetime = 600; $lifetime_priority = -1;
		
		foreach (array('any', $for) as $name) {
			if (isset(self::$backend_picks[$name]) && self::$backend_picks[$name]['priority'] > $backend_priority) {
				$backend_name = self::$backend_picks[$name]['name'];
				$backend_priority = self::$backend_picks[$name]['priority'];
			}
			
			if (isset(self::$cache_lifetime[$name]) && self::$cache_lifetime[$name]['priority'] > $lifetime_priority) {
				$cache_lifetime = self::$cache_lifetime[$name]['lifetime'];
				$lifetime_priority = self::$cache_lifetime[$name]['priority'];
			}
		} 
		
		$backend = self::$backends[$backend_name];

		$basicOptions = array('cache_id_prefix' => $for);
		
		if ($cache_lifetime >= 0) $basicOptions['lifetime'] = $cache_lifetime;
		else $basicOptions['caching'] = false;
		
		$frontendOptions = $frontendOptions ? array_merge($basicOptions, $frontendOptions) : $basicOptions;
		
		require_once 'Zend/Cache.php';
		return Zend_Cache::factory($frontend, $backend[0], $frontendOptions, $backend[1]);
	}
}
