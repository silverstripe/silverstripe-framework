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
     * @param string $class
     * @param mixed $options
     * @param callable $next
     * @return string
     */
    public function getClassConfig($class, $options, $next)
    {
        // Check if enabled
        if (!$this->enabled($options)) {
            return $next($class, $options);
        }

        // Merge hierarchy
        $config = [];
        foreach (ClassInfo::ancestry($class) as $nextClass) {
            $nextConfig = $next($nextClass, $options);
            $config = Priority::mergeArray($nextConfig, $config);
        }
        return $config;
    }
}
