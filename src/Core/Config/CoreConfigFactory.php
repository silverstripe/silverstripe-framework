<?php

namespace SilverStripe\Core\Config;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Config\Collections\CachedConfigCollection;
use SilverStripe\Config\Collections\DeltaConfigCollection;
use SilverStripe\Config\Collections\MemoryConfigCollection;
use SilverStripe\Config\Transformer\PrivateStaticTransformer;
use SilverStripe\Config\Transformer\YamlTransformer;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Config\Middleware\ExtensionMiddleware;
use SilverStripe\Core\Config\Middleware\InheritanceMiddleware;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ModuleLoader;
use Symfony\Component\Finder\Finder;

/**
 * Factory for silverstripe configs
 */
class CoreConfigFactory
{
    /**
     * @var CacheFactory
     */
    protected $cacheFactory = null;

    /**
     * Create factory
     *
     * @param CacheFactory $cacheFactory
     */
    public function __construct(CacheFactory $cacheFactory = null)
    {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Create root application config.
     * This will be an immutable cached config,
     * which conditionally generates a nested "core" config.
     *
     * @return CachedConfigCollection
     */
    public function createRoot()
    {
        $instance = new CachedConfigCollection();

        // Override nested config to use delta collection
        $instance->setNestFactory(function ($collection) {
            return DeltaConfigCollection::createFromCollection($collection, Config::NO_DELTAS);
        });

        // Create config cache
        if ($this->cacheFactory) {
            $cache = $this->cacheFactory->create(CacheInterface::class . '.configcache', [
                'namespace' => 'configcache'
            ]);
            $instance->setCache($cache);
        }

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
        $modules = ModuleLoader::inst()->getManifest()->getModules();
        $dirs = [];
        foreach ($modules as $module) {
            // Load from _config dirs
            $path = $module->getPath() . '/_config';
            if (is_dir($path ?? '')) {
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
            return ClassLoader::inst()
                ->getManifest()
                ->getClassNames();
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
            $actual = Environment::getEnv($var);
            if ($actual === false) {
                return false;
            }
            if ($value) {
                return $actual === $value;
            }
            return true;
        };
        $constantdefined = function ($const, $value = null) {
            if (!defined($const ?? '')) {
                return false;
            }
            if ($value) {
                return constant($const ?? '') === $value;
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
                // Note: The below relies on direct assignment of kernel to injector instance,
                // and will fail if failing back to config service locator
                /** @var Kernel $kernel */
                $kernel = Injector::inst()->get(Kernel::class);
                return strtolower($kernel->getEnvironment()) === strtolower($env);
            })
            ->addRule('moduleexists', function ($module) {
                return ModuleLoader::inst()->getManifest()->moduleExists($module);
            })
            ->addRule('extensionloaded', function ($extension) {
                return extension_loaded($extension ?? '');
            });
    }
}
