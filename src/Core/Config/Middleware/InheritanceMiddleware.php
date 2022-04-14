<?php

namespace SilverStripe\Core\Config\Middleware;

use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Config\Middleware\Middleware;
use SilverStripe\Config\Middleware\MiddlewareCommon;
use SilverStripe\Core\Config\Config;

class InheritanceMiddleware implements Middleware
{
    use MiddlewareCommon;

    public function __construct($disableFlag = 0)
    {
        $this->setDisableFlag($disableFlag);
    }

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
        // Skip if disabled
        $config = $next($class, $excludeMiddleware);
        if (!$this->enabled($excludeMiddleware)) {
            return $config;
        }

        // Skip if not a class or not parent class
        $parent = class_exists($class ?? '') ? get_parent_class($class) : null;
        if (!$parent) {
            return $config;
        }

        // Merge with parent class
        $parentConfig = Config::inst()->get($parent, null, $excludeMiddleware);
        return Priority::mergeArray($config, $parentConfig);
    }
}
