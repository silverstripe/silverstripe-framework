<?php

namespace SilverStripe\Core\Config\Middleware;

use Generator;
use InvalidArgumentException;
use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Config\Middleware\Middleware;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Object;

class ExtensionMiddleware implements Middleware
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
        // Get base config
        $config = $next($class, $excludeMiddleware);

        if (!$this->enabled($excludeMiddleware)) {
            return $config;
        }

        foreach ($this->getExtraConfig($class, $config) as $extra) {
            $config = Priority::mergeArray($extra, $config);
        }
        return $config;
    }

    /**
     * Applied config to a class from its extensions
     *
     * @param string $class
     * @param array $classConfig
     * @return Generator
     */
    protected function getExtraConfig($class, $classConfig)
    {
        if (empty($classConfig['extensions'])) {
            return;
        }

        $extensions = $classConfig['extensions'];
        foreach ($extensions as $extension) {
            list($extensionClass, $extensionArgs) = Object::parse_class_spec($extension);
            if (!class_exists($extensionClass)) {
                throw new InvalidArgumentException("$class references nonexistent $extensionClass in 'extensions'");
            }

            // Init extension
            call_user_func(array($extensionClass, 'add_to_class'), $class, $extensionClass, $extensionArgs);

            // Check class hierarchy from root up
            foreach (ClassInfo::ancestry($extensionClass) as $extensionClassParent) {
                // Merge config from extension
                $extensionConfig = Config::inst()->get($extensionClassParent, null, true);
                if ($extensionConfig) {
                    yield $extensionConfig;
                }
                if (ClassInfo::has_method_from($extensionClassParent, 'get_extra_config', $extensionClassParent)) {
                    $extensionConfig = call_user_func(
                        [ $extensionClassParent, 'get_extra_config' ],
                        $class,
                        $extensionClass,
                        $extensionArgs
                    );
                    if ($extensionConfig) {
                        yield $extensionConfig;
                    }
                }
            }
        }
    }
}
