<?php

namespace SilverStripe\Core\Cache;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\CacheItem;

/**
 * Wraps the new Symfony "Adapter" cache classes, using the psr6 methods rather than Cache Contracts,
 * with Psr\SimpleCache\CacheInterface methods
 *
 * @see Symfony\Component\Cache\Traits\AbstractAdapterTrait for part of Symfonys PSR6 implementation
 * @see Symfony\Component\Cache\Traits\AbstractTrait for more of Symfonys PSR6 implementation
 * @see https://symfony.com/doc/current/components/cache.html about the 2x implementations
 */
class SymfonyAdapterToPsr6Cache implements CacheInterface
{
    /**
     * @var AbstractAdapter
     */
    private $symfonyAdapter;

    /**
     * @param AbstractAdapter $symfonyAdapter
     */
    public function __construct(AbstractAdapter $symfonyAdapter)
    {
        $this->symfonyAdapter = $symfonyAdapter;
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        return $this->getCacheItem($key)->get() ?: $default;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $cacheItem = $this->getCacheItem($key);
        $cacheItem->set($value);
        // TODO: $cacheItem->expiresAt($ttl); - need to convert int|\DateInterval to a DateTimeInterafce
        // I think this is along the lines of new DateTime(now() + $ttl)
        return $this->symfonyAdapter->save($cacheItem);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        return $this->symfonyAdapter->deleteItem($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->symfonyAdapter->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $cacheItems = $this->symfonyAdapter->getItems($keys);
        $arr = [];
        foreach ($keys as $key) {
            $arr[$key] = $cacheItems[$key] ?? $default;
        }
        return $arr;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $keys = array_keys((array) $values);
        $cacheItems = $this->symfonyAdapter->getItems($keys);
        foreach ($keys as $key) {
            $cacheItems[$key]->set($values[$key]);
            // TODO: $ttl
        }
        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        return $this->symfonyAdapter->deleteItems($keys);
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        return $this->symfonyAdapter->hasItem($key);
    }

    /**
     * @param string $key
     * @return CacheItem
     */
    private function getCacheItem(string $key): CacheItem
    {
        return $this->symfonyAdapter->getItem($key);
    }
}
