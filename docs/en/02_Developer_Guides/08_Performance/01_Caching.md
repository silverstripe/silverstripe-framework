# Caching

## Overview

The framework uses caches to store infrequently changing values.
By default, the storage mechanism chooses the most performant adapter available
(PHP7 opcache, APC, or filesystem). Other cache backends can be configured.

The most common caches are manifests of various resources: 

 * PHP class locations ([api:ClassManifest])
 * Template file locations and compiled templates ([api:SS_TemplateManifest])
 * Configuration settings from YAML files ([api:ConfigManifest])
 * Language files ([api:i18n])

Flushing the various manifests is performed through a GET
parameter (`flush=1`). Since this action requires more server resources than normal requests,
executing the action is limited to the following cases when performed via a web request:

 * The [environment](/getting_started/environment_management) is in "dev mode"
 * A user is logged in with ADMIN permissions
 * An error occurs during startup

## Configuration

We are using the [PSR-16](http://www.php-fig.org/psr/psr-16/) standard ("SimpleCache")
for caching, through the [symfony/cache](https://symfony.com/doc/current/components/cache.html) library.
Note that this library describes usage of [PSR-6](http://www.php-fig.org/psr/psr-6/) by default,
but also exposes caches following the PSR-16 interface. 

Cache objects are configured via YAML
and SilverStripe's [dependency injection](/developer-guides/extending/injector) system. 

    :::yml
    SilverStripe\Core\Injector\Injector:
      Psr\SimpleCache\CacheInterface.myCache:
        factory: SilverStripe\Core\Cache\CacheFactory
        constructor:
          namespace: "myCache"

Cache objects are instantiated through a [CacheFactory](SilverStripe\Core\Cache\CacheFactory),
which determines which cache adapter is used (see "Adapters" below for details).
This factory allows us you to globally define an adapter for all cache instances.  

    :::php
    use Psr\SimpleCache\CacheInterface
    $cache = Injector::inst()->get(CacheInterface::class . '.myCache');

Caches are namespaced, which might allow granular clearing of a particular cache without affecting others.
In our example, the namespace is "myCache", expressed in the service name as
`Psr\SimpleCache\CacheInterface.myCache`. We recommend the `::class` short-hand to compose the full service name.
 
Clearing caches by namespace is dependant on the used adapter: While the `FilesystemCache` adapter clears only the namespaced cache,
a `MemcachedCache` adapter will clear all caches regardless of namespace, since the underlying memcached
service doesn't support this. See "Invalidation" for alternative strategies.


## Usage

Cache objects follow the [PSR-16](http://www.php-fig.org/psr/psr-16/) class interface.

	:::php
	use Psr\SimpleCache\CacheInterface
    $cache = Injector::inst()->get(CacheInterface::class . '.myCache');

    // create a new item by trying to get it from the cache
    $myValue = $cache->get('myCacheKey');
    
    // set a value and save it via the adapter
    $cache->set('myCacheKey', 1234);
    
    // retrieve the cache item
    if (!$cache->has('myCacheKey')) {
        // ... item does not exists in the cache
    }
    
## Invalidation

Caches can be invalidated in different ways. The easiest is to actively clear the
entire cache. If the adapter supports namespaced cache clearing,
this will only affect a subset of cache keys ("myCache" in this example):

    :::php
    use Psr\SimpleCache\CacheInterface
    $cache = Injector::inst()->get(CacheInterface::class . '.myCache');
    
    // remove all items in this (namespaced) cache
    $cache->clear();
    
You can also delete a single item based on it's cache key:

    :::php
    use Psr\SimpleCache\CacheInterface
    $cache = Injector::inst()->get(CacheInterface::class . '.myCache');
    
    // remove the cache item
    $cache->delete('myCacheKey');

Individual cache items can define a lifetime, after which the cached value is marked as expired:

    :::php
    use Psr\SimpleCache\CacheInterface
    $cache = Injector::inst()->get(CacheInterface::class . '.myCache');
    
    // remove the cache item
    $cache->set('myCacheKey', 'myValue', 300); // cache for 300 seconds

If a lifetime isn't defined on the `set()` call, it'll use the adapter default.
In order to increase the chance of your cache actually being hit,
it often pays to increase the lifetime of caches.
You can also set your lifetime to `0`, which means they won't expire.
Since many adapters don't have a way to actively remove expired caches,
you need to be careful with resources here (e.g. filesystem space).

    :::yml
    SilverStripe\Core\Injector\Injector:
      Psr\SimpleCache\CacheInterface.cacheblock:
          constructor:
            defaultLifetime: 3600

In most cases, invalidation and expiry should be handled by your cache key.
For example, including the `LastEdited` value when caching `DataObject` results
will automatically create a new cache key when the object has been changed.
The following example caches a member's group names, and automatically
creates a new cache key when any group is edited. Depending on the used adapter,
old cache keys will be garbage collected as the cache fills up.

    :::php
    use Psr\SimpleCache\CacheInterface
    $cache = Injector::inst()->get(CacheInterface::class . '.myCache');
    
    // Automatically changes when any group is edited
    $cacheKey = implode(['groupNames', $member->ID, Groups::get()->max('LastEdited')]);
    $cache->set($cacheKey, $member->Groups()->column('Title'));        

If `?flush=1` is requested in the URL, this will trigger a call to `flush()` on
any classes that implement the [Flushable](/developer_guides/execution_pipeline/flushable/)
interface. Use this interface to trigger `clear()` on your caches.

## Adapters

SilverStripe tries to identify the most performant cache available on your system
through the [DefaultCacheFactory](api:SilverStripe\Core\Cache\DefaultCacheFactory) implementation:

 * - `PhpFilesCache` (PHP 5.6 or PHP 7 with [opcache](http://php.net/manual/en/book.opcache.php) enabled).
     This cache has relatively low [memory defaults](http://php.net/manual/en/opcache.configuration.php#ini.opcache.memory-consumption).
     We recommend increasing it for large applications, or enabling the
     [file_cache fallback](http://php.net/manual/en/opcache.configuration.php#ini.opcache.file-cache)
 * - `ApcuCache` (requires APC) with a `FilesystemCache` fallback (for larger cache volumes)
 * - `FilesystemCache` if none of the above is available
 
The library supports various [cache adapters](https://github.com/symfony/cache/tree/master/Simple)
which can provide better performance, particularly in multi-server environments with shared caches like Memcached.

Since we're using dependency injection to create caches, 
you need to define a factory for a particular adapter,
following the `SilverStripe\Core\Cache\CacheFactory` interface.
Different adapters will require different constructor arguments.
We've written factories for the most common cache scenarios:
`FilesystemCacheFactory`, `MemcachedCacheFactory` and `ApcuCacheFactory`.

Example: Configure core caches to use [memcached](http://www.danga.com/memcached/),
which requires the [memcached PHP extension](http://php.net/memcached),
and takes a `MemcachedClient` instance as a constructor argument.

    :::yml
    ---
    After:
      - '#corecache'
    ---
    SilverStripe\Core\Injector\Injector:
      MemcachedClient:
        class: 'Memcached'
        calls:
          - [ addServer, [ 'localhost', 11211 ] ]
      SilverStripe\Core\Cache\CacheFactory:
        class: 'SilverStripe\Core\Cache\MemcachedCacheFactory'
        constructor:
          client: '%$MemcachedClient

## Additional Caches

Unfortunately not all caches are configurable via cache adapters.

 * [SSViewer](api:SilverStripe\View\SSViewer) writes compiled templates as PHP files to the filesystem
   (in order to achieve opcode caching on `include()` calls)
 * [ConfigManifest](api:SilverStripe\Core\Manifest\ConfigManifest) is hardcoded to use `FilesystemCache`
 * [ClassManifest](api:SilverStripe\Core\Manifest\ClassManifest) and [ThemeManifest](api:SilverStripe\View\ThemeManifest)
   are using a custom `ManifestCache`
 * [i18n](api:SilverStripe\i18n\i18n) uses `Symfony\Component\Config\ConfigCacheFactoryInterface` (filesystem-based)
