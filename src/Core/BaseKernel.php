<?php

namespace SilverStripe\Core;

use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Config\Collections\CachedConfigCollection;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Cache\ManifestCacheFactory;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\Config\CoreConfigFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Injector\SilverStripeServiceConfigurationLocator;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\DebugView;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Logging\ErrorHandler;
use SilverStripe\View\PublicThemes;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeManifest;
use SilverStripe\View\ThemeResourceLoader;
use Exception;

/**
 * Simple Kernel container
 */
abstract class BaseKernel implements Kernel
{
    /**
     * @var Kernel
     */
    protected $nestedFrom = null;

    /**
     * @var Injector
     */
    protected $container = null;

    /**
     * @var string
     */
    protected $enviroment = null;

    /**
     * @var ClassLoader
     */
    protected $classLoader = null;

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader = null;

    /**
     * @var ConfigLoader
     */
    protected $configLoader = null;

    /**
     * @var InjectorLoader
     */
    protected $injectorLoader = null;

    /**
     * @var ThemeResourceLoader
     */
    protected $themeResourceLoader = null;

    protected $basePath = null;

    /**
     * Indicates whether the Kernel has been booted already
     *
     * @var bool
     */
    private $booted = false;


    /**
     * Create a new kernel for this application
     *
     * @param string $basePath Path to base dir for this application
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        // Initialise the dependency injector as soon as possible, as it is
        // subsequently used by some of the following code
        $injectorLoader = InjectorLoader::inst();
        $injector = new Injector(['locator' => SilverStripeServiceConfigurationLocator::class]);
        $injectorLoader->pushManifest($injector);
        $this->setInjectorLoader($injectorLoader);

        // Manifest cache factory
        $manifestCacheFactory = $this->buildManifestCacheFactory();

        // Class loader
        $classLoader = ClassLoader::inst();
        $classLoader->pushManifest(new ClassManifest($basePath, $manifestCacheFactory));
        $this->setClassLoader($classLoader);

        // Module loader
        $moduleLoader = ModuleLoader::inst();
        $moduleManifest = new ModuleManifest($basePath, $manifestCacheFactory);
        $moduleLoader->pushManifest($moduleManifest);
        $this->setModuleLoader($moduleLoader);

        // Config loader
        // @todo refactor CoreConfigFactory
        $configFactory = new CoreConfigFactory($manifestCacheFactory);
        $configManifest = $configFactory->createRoot();
        $configLoader = ConfigLoader::inst();
        $configLoader->pushManifest($configManifest);
        $this->setConfigLoader($configLoader);

        // Load template manifest
        $themeResourceLoader = ThemeResourceLoader::inst();
        $themeResourceLoader->addSet(SSViewer::PUBLIC_THEME, new PublicThemes());
        $themeResourceLoader->addSet(SSViewer::DEFAULT_THEME, new ThemeManifest(
            $basePath,
            null, // project is defined in config, and this argument is deprecated
            $manifestCacheFactory
        ));
        $this->setThemeResourceLoader($themeResourceLoader);
    }

    /**
     * Initialise PHP with default variables
     */
    protected function bootPHP()
    {
        if ($this->getEnvironment() === self::LIVE) {
            // limited to fatal errors and warnings in live mode
            error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));
        } else {
            // Report all errors in dev / test mode
            error_reporting(E_ALL | E_STRICT);
        }

        /**
         * Ensure we have enough memory
         */
        Environment::increaseMemoryLimitTo('64M');

        // Ensure we don't run into xdebug's fairly conservative infinite recursion protection limit
        if (function_exists('xdebug_enable')) {
            $current = ini_get('xdebug.max_nesting_level');
            if ((int)$current < 200) {
                ini_set('xdebug.max_nesting_level', 200);
            }
        }

        /**
         * Set default encoding
         */
        mb_http_output('UTF-8');
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        /**
         * Enable better garbage collection
         */
        gc_enable();
    }

    /**
     * Boot all manifests
     *
     * @param bool $flush
     */
    protected function bootManifests($flush)
    {
        // Setup autoloader
        $this->getClassLoader()->init(
            $this->getIncludeTests(),
            $flush,
            $this->getIgnoredCIConfigs()
        );

        // Find modules
        $this->getModuleLoader()->init(
            $this->getIncludeTests(),
            $flush,
            $this->getIgnoredCIConfigs()
        );

        // Flush config
        if ($flush) {
            $config = $this->getConfigLoader()->getManifest();
            if ($config instanceof CachedConfigCollection) {
                $config->setFlush(true);
            }
        }
        // tell modules to sort, now that config is available
        $this->getModuleLoader()->getManifest()->sort();

        // Find default templates
        $defaultSet = $this->getThemeResourceLoader()->getSet('$default');
        if ($defaultSet instanceof ThemeManifest) {
            $defaultSet->setProject(
                ModuleManifest::config()->get('project')
            );
            $defaultSet->init(
                $this->getIncludeTests(),
                $flush,
                $this->getIgnoredCIConfigs()
            );
        }
    }

    /**
     * Include all _config.php files
     */
    protected function bootConfigs()
    {
        global $project;
        $projectBefore = $project;
        $config = ModuleManifest::config();
        // After loading all other app manifests, include _config.php files
        $this->getModuleLoader()->getManifest()->activateConfig();
        if ($project && $project !== $projectBefore) {
            Deprecation::notice('5.0', '$project global is deprecated');
            $config->set('project', $project);
        }
    }

    /**
     * Turn on error handling
     * @throws Exception
     */
    protected function bootErrorHandling()
    {
        // Register error handler
        $errorHandler = Injector::inst()->get(ErrorHandler::class);
        $errorHandler->start();

        // Register error log file
        $errorLog = Environment::getEnv('SS_ERROR_LOG');
        if ($errorLog) {
            $logger = Injector::inst()->get(LoggerInterface::class);
            if ($logger instanceof Logger) {
                $logger->pushHandler(new StreamHandler($this->basePath . '/' . $errorLog, Logger::WARNING));
            } else {
                user_error("SS_ERROR_LOG setting only works with Monolog, you are using another logger", E_USER_WARNING);
            }
        }
    }

    /**
     * Get the environment type
     *
     * @return string
     *
     * @deprecated 4.12.0 Use Director::get_environment_type() instead
     */
    public function getEnvironment()
    {
        Deprecation::notice('4.12.0', 'Use Director::get_environment_type() instead');
        // Check set
        if ($this->enviroment) {
            return $this->enviroment;
        }

        // Check saved session
        $env = $this->sessionEnvironment();
        if ($env) {
            return $env;
        }

        // Check getenv
        if ($env = Environment::getEnv('SS_ENVIRONMENT_TYPE')) {
            return $env;
        }

        return self::LIVE;
    }

    /**
     * Check or update any temporary environment specified in the session.
     *
     * @return null|string
     *
     * @deprecated 4.12.0 Use Director::get_session_environment_type() instead
     */
    protected function sessionEnvironment()
    {
        Deprecation::notice('4.12.0', 'Use Director::get_session_environment_type() instead');
        if (!$this->booted) {
            // session is not initialyzed yet, neither is manifest
            return null;
        }

        return Director::get_session_environment_type();
    }

    abstract public function boot($flush = false);

    abstract public function isFlushed();

    /**
     * Check if there's a legacy _ss_environment.php file
     *
     * @throws HTTPResponse_Exception
     */
    protected function detectLegacyEnvironment()
    {
        // Is there an _ss_environment.php file?
        if (!file_exists($this->basePath . '/_ss_environment.php') &&
            !file_exists(dirname($this->basePath ?? '') . '/_ss_environment.php')
        ) {
            return;
        }

        // Build error response
        $dv = new DebugView();
        $body = implode([
            $dv->renderHeader(),
            $dv->renderInfo(
                "Configuration Error",
                Director::absoluteBaseURL()
            ),
            $dv->renderParagraph(
                'You need to replace your _ss_environment.php file with a .env file, or with environment variables.<br><br>'
                . 'See the <a href="https://docs.silverstripe.org/en/4/getting_started/environment_management/">'
                . 'Environment Management</a> docs for more information.'
            ),
            $dv->renderFooter()
        ]);

        // Raise error
        $response = new HTTPResponse($body, 500);
        throw new HTTPResponse_Exception($response);
    }

    /**
     * If missing configuration, redirect to install.php if it exists.
     * Otherwise show a server error to the user.
     *
     * @param string $msg Optional message to show to the user on an installed project (install.php missing).
     */
    protected function redirectToInstaller($msg = '')
    {
        // Error if installer not available
        if (!file_exists(Director::publicFolder() . '/install.php')) {
            throw new HTTPResponse_Exception(
                $msg,
                500
            );
        }

        // Redirect to installer
        $response = new HTTPResponse();
        $response->redirect(Director::absoluteURL('install.php'));
        throw new HTTPResponse_Exception($response);
    }

    /**
     * @return ManifestCacheFactory
     */
    protected function buildManifestCacheFactory()
    {
        return new ManifestCacheFactory([
            'namespace' => 'manifestcache',
            'directory' => TEMP_PATH,
        ]);
    }

    /**
     * When manifests are discovering files, tests files in modules using the following CI library type will be ignored.
     *
     * The purpose of this method is to avoid loading PHPUnit test files with incompatible definitions.
     *
     * @return string[] List of CI types to ignore as defined by `Module`.
     * @deprecated 4.12.0 Will be removed without equivalent functionality
     */
    protected function getIgnoredCIConfigs(): array
    {
        Deprecation::notice('4.12.0', 'Will be removed without equivalent functionality');
        return [];
    }

    /**
     * @return bool
     */
    protected function getIncludeTests()
    {
        return false;
    }

    /**
     * @param bool $bool
     */
    protected function setBooted(bool $bool): void
    {
        $this->booted = $bool;
    }

    public function shutdown()
    {
    }

    public function nest()
    {
        // Clone this kernel, nesting config / injector manifest containers
        $kernel = clone $this;
        $kernel->setConfigLoader($this->configLoader->nest());
        $kernel->setInjectorLoader($this->injectorLoader->nest());
        $kernel->nestedFrom = $this;
        return $kernel;
    }

    public function activate()
    {
        $this->configLoader->activate();
        $this->injectorLoader->activate();

        // Self register
        $this->getInjectorLoader()
            ->getManifest()
            ->registerService($this, Kernel::class);
        return $this;
    }

    public function getNestedFrom()
    {
        return $this->nestedFrom;
    }

    public function getContainer()
    {
        return $this->getInjectorLoader()->getManifest();
    }

    public function setInjectorLoader(InjectorLoader $injectorLoader)
    {
        $this->injectorLoader = $injectorLoader;
        $injectorLoader
            ->getManifest()
            ->registerService($this, Kernel::class);
        return $this;
    }

    public function getInjectorLoader()
    {
        return $this->injectorLoader;
    }

    public function getClassLoader()
    {
        return $this->classLoader;
    }

    public function setClassLoader(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
        return $this;
    }

    public function getModuleLoader()
    {
        return $this->moduleLoader;
    }

    public function setModuleLoader(ModuleLoader $moduleLoader)
    {
        $this->moduleLoader = $moduleLoader;
        return $this;
    }

    public function setEnvironment($environment)
    {
        if (!in_array($environment, [self::DEV, self::TEST, self::LIVE, null])) {
            throw new InvalidArgumentException(
                "Director::set_environment_type passed '$environment'.  It should be passed dev, test, or live"
            );
        }
        $this->enviroment = $environment;
        return $this;
    }

    public function getConfigLoader()
    {
        return $this->configLoader;
    }

    public function setConfigLoader($configLoader)
    {
        $this->configLoader = $configLoader;
        return $this;
    }

    public function getThemeResourceLoader()
    {
        return $this->themeResourceLoader;
    }

    public function setThemeResourceLoader($themeResourceLoader)
    {
        $this->themeResourceLoader = $themeResourceLoader;
        return $this;
    }
}
