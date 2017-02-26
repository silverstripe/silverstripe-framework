<?php

namespace SilverStripe\Core\Config\Middleware;

use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Config\Middleware\Middleware;
use SilverStripe\Core\ClassInfo;

class InheritanceMiddleware implements Middleware
{
    use MiddlewareCommon;

    /**
     * Get config for a class
     *
     * @param string $class Name of class
     * @param int|true $excludeMiddleware Middleware disable flags
     * @param callable $next Callback to next middleware
     * @return array Complete class config
     */
    public function getClassConfig($class, $excludeMiddleware, $next)
    {
        // Check if enabled
        if (!$this->enabled($excludeMiddleware)) {
            return $next($class, $excludeMiddleware);
        }

        // Merge hierarchy
        $config = [];
        foreach (ClassInfo::ancestry($class) as $nextClass) {
            $nextConfig = $next($nextClass, $excludeMiddleware);
            $config = Priority::mergeArray($nextConfig, $config);
        }
        return $config;
    }
}
