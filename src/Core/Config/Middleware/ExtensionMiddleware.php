<?php

namespace SilverStripe\Core\Config\Middleware;

use Generator;
use InvalidArgumentException;
use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Config\Middleware\Middleware;
use SilverStripe\Config\Middleware\MiddlewareCommon;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataExtension;

class ExtensionMiddleware implements Middleware
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
        // Get base config
        $config = $next($class, $excludeMiddleware);

        if (!$this->enabled($excludeMiddleware)) {
            return $config;
        }

        foreach ($this->getExtraConfig($class, $config, $excludeMiddleware) as $extra) {
            $config = Priority::mergeArray($config, $extra);
        }
        return $config;
    }

    /**
     * Applied config to a class from its extensions
     *
     * @param string $class
     * @param array $classConfig
     * @param int $excludeMiddleware
     * @return Generator
     */
    protected function getExtraConfig($class, $classConfig, $excludeMiddleware)
    {
        // Note: 'extensions' config needs to come from it's own middleware call in case
        // applied by delta middleware (e.g. Object::add_extension)
        $extensionSourceConfig = Config::inst()->get($class, null, Config::UNINHERITED | $excludeMiddleware | $this->disableFlag);
        if (empty($extensionSourceConfig['extensions'])) {
            return;
        }

        $extensions = $extensionSourceConfig['extensions'];
        foreach ($extensions as $extension) {
            // Allow removing extensions via yaml config by setting named extension config to null
            if ($extension === null) {
                continue;
            }

            list($extensionClass, $extensionArgs) = ClassInfo::parse_class_spec($extension);
            // Strip service name specifier
            $extensionClass = strtok($extensionClass ?? '', '.');
            if (!class_exists($extensionClass ?? '')) {
                throw new InvalidArgumentException("$class references nonexistent $extensionClass in 'extensions'");
            }

            // Init extension
            call_user_func([$extensionClass, 'add_to_class'], $class, $extensionClass, $extensionArgs);

            // Check class hierarchy from root up
            foreach (ClassInfo::ancestry($extensionClass) as $extensionClassParent) {
                // Skip base classes
                switch ($extensionClassParent) {
                    case Extension::class:
                    case DataExtension::class:
                        continue 2;
                    default:
                        // continue
                }
                // Merge config from extension
                $extensionConfig = Config::inst()->get(
                    $extensionClassParent,
                    null,
                    Config::EXCLUDE_EXTRA_SOURCES | Config::UNINHERITED
                );
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
