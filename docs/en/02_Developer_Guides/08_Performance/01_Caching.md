# Caching

## Built-In Caches

The framework uses caches to store infrequently changing values.
By default, the storage mechanism is simply the filesystem, although
other cache backends can be configured. All caches use the `[api:SS_Cache]` API.

The most common caches are manifests of various resources: 

 * PHP class locations (`[api:SS_ClassManifest]`)
 * Template file locations and compiled templates (`[api:SS_TemplateManifest]`)
 * Configuration settings from YAML files (`[api:SS_ConfigManifest]`)
 * Language files (`[api:i18n]`)

Flushing the various manifests is performed through a GET
parameter (`flush=1`). Since this action requires more server resources than normal requests,
executing the action is limited to the following cases when performed via a web request:

 * The [environment](../getting_started/environment_management) is in "dev mode"
 * A user is logged in with ADMIN permissions
 * An error occurs during startup

## The Cache API

The `[api:SS_Cache]` class provides a bunch of static functions wrapping the Zend_Cache system 
in something a little more easy to use with the SilverStripe config system.

A `Zend_Cache` has both a frontend (determines how to get the value to cache, 
and how to serialize it for storage) and a backend (handles the actual 
storage).

Rather than require library code to specify the backend directly, cache 
consumers provide a name for the cache backend they want. The end developer 
can then specify which backend to use for each name in their project's
configuration. They can also use 'all' to provide a backend for all named 
caches.

End developers provide a set of named backends, then pick the specific 
backend for each named cache. There is a default File cache set up as the 
'default' named backend, which is assigned to 'all' named caches.

## Using Caches

Caches can be created and retrieved through the `SS_Cache::factory()` method.
The returned object is of type `Zend_Cache`.

	:::php
	// foo is any name (try to be specific), and is used to get configuration 
	// & storage info
	$cache = SS_Cache::factory('foo'); 
	if (!($result = $cache->load($cachekey))) {
		$result = caluate some how;
		$cache->save($result, $cachekey);
	}
	return $result;

Normally there's no need to remove things from the cache - the cache 
backends clear out entries based on age and maximum allocated storage. If you 
include the version of the object in the cache key, even object changes 
don't need any invalidation. You can force disable the cache though,
e.g. in development mode.

	:::php
	// Disables all caches
	SS_Cache::set_cache_lifetime('any', -1, 100);

You can also specifically clean a cache.
Keep in mind that `Zend_Cache::CLEANING_MODE_ALL` deletes all cache
entries across all caches, not just for the 'foo' cache in the example below.

	:::php
	$cache = SS_Cache::factory('foo'); 
	$cache->clean(Zend_Cache::CLEANING_MODE_ALL);

A single element can be invalidated through its cache key.

	:::php
	$cache = SS_Cache::factory('foo');  
	$cache->remove($cachekey);

In order to increase the chance of your cache actually being hit,
it often pays to increase the lifetime of caches ("TTL").
It defaults to 10 minutes (600s) in SilverStripe, which can be
quite short depending on how often your data changes.
Keep in mind that data expiry should primarily be handled by your cache key,
e.g. by including the `LastEdited` value when caching `DataObject` results.

	:::php
	// set all caches to 3 hours
	SS_Cache::set_cache_lifetime('any', 60*60*3);

## Alternative Cache Backends

By default, SilverStripe uses a file-based caching backend.
Together with a file stat cache like [APC](http://us2.php.net/manual/en/book.apc.php) 
this is reasonably quick, but still requires access to slow disk I/O.
The `Zend_Cache` API supports various caching backends ([list](http://framework.zend.com/manual/1.12/en/zend.cache.backends.html))
which can provide better performance, including APC, Xcache, ZendServer, Memcached and SQLite.

## Cleaning caches on flush=1 requests

If `?flush=1` is requested in the URL, e.g. http://mysite.com?flush=1, this will trigger a call to `flush()` on
any classes that implement the `Flushable` interface. Using this, you can trigger your caches to clean.

See [reference documentation on Flushable](/developer_guides/execution_pipeline/flushable/) for implementation details.

### Memcached

This backends stores cache records into a [memcached](http://www.danga.com/memcached/) 
server. memcached is a high-performance, distributed memory object caching system. 
To use this backend, you need a memcached daemon and the memcache PECL extension.

 	:::php
	// _config.php 
	SS_Cache::add_backend(
		'primary_memcached', 
		'Memcached',
		array(
			'servers' => array(
				'host' => 'localhost', 
				'port' => 11211, 
				'persistent' => true, 
				'weight' => 1, 
				'timeout' => 5,
				'retry_interval' => 15, 
				'status' => true, 
				'failure_callback' => ''
			)
		)
	);
	SS_Cache::pick_backend('primary_memcached', 'any', 10);

### APC

This backends stores cache records in shared memory through the [APC](http://pecl.php.net/package/APC)
 (Alternative PHP Cache) extension (which is of course need for using this backend).

	:::php
	SS_Cache::add_backend('primary_apc', 'APC');
	SS_Cache::pick_backend('primary_apc', 'any', 10);

### Two-Levels

This backend is an hybrid one. It stores cache records in two other backends: 
a fast one (but limited) like Apc, Memcache... and a "slow" one like File or Sqlite.

	:::php
	SS_Cache::add_backend('two_level', 'Two-Levels', array(
		'slow_backend' => 'File',
		'fast_backend' => 'APC',
		'slow_backend_options' => array(
				'cache_dir' => TEMP_FOLDER . DIRECTORY_SEPARATOR . 'cache'
		)
	));
	SS_Cache::pick_backend('two_level', 'any', 10); 
