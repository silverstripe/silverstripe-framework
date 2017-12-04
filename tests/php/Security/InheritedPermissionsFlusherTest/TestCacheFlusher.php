<?php

namespace SilverStripe\Security\Tests\InheritedPermissionsFlusherTest;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFlusher;

class TestCacheFlusher implements CacheFlusher
{
    /**
     * @var array
     */
    public static $categories = [
        'apples',
        'pears',
        'bananas',
    ];

    /**
     * @var CacheInterface
     */
    public $cache;

    /**
     * TestCacheFlusher constructor.
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Clear the cache for this instance only
     * @param array $ids A list of member IDs
     */
    public function flushCache($ids = null)
    {
        if (!$this->cache) {
            return;
        }

        // Hard flush, e.g. flush=1
        if (!$ids) {
            $this->cache->clear();
        }

        if ($ids && is_array($ids)) {
            foreach (self::$categories as $category) {
                foreach ($ids as $memberID) {
                    $key = $this->generateCacheKey($category, $memberID);
                    $this->cache->delete($key);
                }
            }
        }
    }

    /**
     * @param $category
     * @param $memberID
     * @return string
     */
    public function generateCacheKey($category, $memberID)
    {
        return "{$category}__{$memberID}";
    }
}