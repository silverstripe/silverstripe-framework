<?php

namespace SilverStripe\Core\Config;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Config\Collections\CachedConfigCollection;
use SilverStripe\Config\Collections\MemoryConfigCollection;
use SilverStripe\Config\Transformer\PrivateStaticTransformer;
use SilverStripe\Config\Transformer\YamlTransformer;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Middleware\ExtensionMiddleware;
use SilverStripe\Core\Config\Middleware\InheritanceMiddleware;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ModuleLoader;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Finder\Finder;

/**
 * Factory for silverstripe configs
 */
class CoreConfigFactory
{
    /**
     * @var static
     */
    protected static $inst = null;

    /**
     * @return static
     */
    public static function inst()
    {
        if (!self::$inst) {
            self::$inst = new static();
        }
        return self::$inst;
    }

    /**
     * Create root application config.
     * This will be an immutable cached config,
     * which conditionally generates a nested "core" config.
     *
     * @param bool $flush
     * @return CachedConfigCollection
     */
    public function createRoot($flush)
    {
        $instance = new CachedConfigCollection();

        // Set root cache
        $instance->setPool($this->createPool());
        $instance->setFlush($flush);

        // Set collection creator
        $instance->setCollectionCreator(function () {
            return $this->createCore();
        });

        return $instance;
    }

    /**
     * Rebuild new uncached config, which is mutable
     *
     * @return MemoryConfigCollection
     */
    public function createCore()
    {
        $config = new MemoryConfigCollection();

        // Set default middleware
        $config->setMiddlewares([
            new InheritanceMiddleware(Config::UNINHERITED),
            new ExtensionMiddleware(Config::EXCLUDE_EXTRA_SOURCES),
        ]);

        // Transform
        $config->transform([
            $this->buildStaticTransformer(),
            $this->buildYamlTransformer()
        ]);

        return $config;
    }

    /**
     * @return YamlTransformer
     */
    protected function buildYamlTransformer()
    {
        // Get all module dirs
        $modules = ModuleLoader::instance()->getManifest()->getModules();
        $dirs = [];
        foreach ($modules as $module) {
            // Load from _config dirs
            $path = $module->getPath() . '/_config';
            if (is_dir($path)) {
                $dirs[] = $path;
            }
        }

        return $this->buildYamlTransformerForPath($dirs);
    }

    /**
     * @return PrivateStaticTransformer
     */
    public function buildStaticTransformer()
    {
        return new PrivateStaticTransformer(function () {
            $classes = ClassLoader::instance()->getManifest()->getClasses();
            return array_keys($classes);
        });
    }

    /**
     * @param array|string $dirs Base dir to load from
     * @return YamlTransformer
     */
    public function buildYamlTransformerForPath($dirs)
    {
        // Construct
        $transformer = YamlTransformer::create(
            BASE_PATH,
            Finder::create()
                ->in($dirs)
                ->files()
                ->name('/\.(yml|yaml)$/')
        );

        // Add default rules
        $envvarset = function ($var, $value = null) {
            if (getenv($var) === false) {
                return false;
            }
            if ($value) {
                return getenv($var) === $value;
            }
            return true;
        };
        $constantdefined = function ($const, $value = null) {
            if (!defined($const)) {
                return false;
            }
            if ($value) {
                return constant($const) === $value;
            }
            return true;
        };
        return $transformer
            ->addRule('classexists', function ($class) {
                return class_exists($class);
            })
            ->addRule('envvarset', $envvarset)
            ->addRule('constantdefined', $constantdefined)
            ->addRule(
                'envorconstant',
                // Composite rule
                function ($name, $value = null) use ($envvarset, $constantdefined) {
                    return $envvarset($name, $value) || $constantdefined($name, $value);
                }
            )
            ->addRule('environment', function ($env) {
                $current = Director::get_environment_type();
                return strtolower($current) === strtolower($env);
            })
            ->addRule('moduleexists', function ($module) {
                return ModuleLoader::instance()->getManifest()->moduleExists($module);
            });
    }

    /**
     * @todo Refactor bootstrapping of manifest caching into app object
     * @return FilesystemAdapter
     */
    protected function createPool()
    {
        $cache = new FilesystemAdapter('configcache', 0, getTempFolder());
        $cache->setLogger($this->createLogger());
        return $cache;
    }

    /**
     * Create default error logger
     *
     * @todo Refactor bootstrapping of manifest logging into app object
     * @return LoggerInterface
     */
    protected function createLogger()
    {
        $logger = new Logger("configcache-log");
        if (Director::isDev()) {
            $logger->pushHandler(new StreamHandler('php://output'));
        } else {
            $logger->pushHandler(new ErrorLogHandler());
        }
        return $logger;
    }
}
