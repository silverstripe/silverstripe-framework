<?php

namespace SilverStripe\Security\Tests\InheritedPermissionsFlusherTest;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\MemberCacheFlusher;

class TestCacheFlusher implements MemberCacheFlusher
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
     * @param array $memberIDs A list of member IDs
     */
    public function flushMemberCache($memberIDs = null)
    {
        if (!$this->cache) {
            return;
        }

        // Hard flush, e.g. flush=1
        if (!$memberIDs) {
            $this->cache->clear();
        }

        if ($memberIDs && is_array($memberIDs)) {
            foreach (self::$categories as $category) {
                foreach ($memberIDs as $memberID) {
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