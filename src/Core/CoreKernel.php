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
use SilverStripe\Dev\Install\DatabaseAdapterRegistry;
use SilverStripe\Logging\ErrorHandler;
use SilverStripe\ORM\DB;
use SilverStripe\View\PublicThemes;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeManifest;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Dev\Deprecation;

/**
 * Simple Kernel container
 */
class CoreKernel implements Kernel
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
        $injector = new Injector(array('locator' => SilverStripeServiceConfigurationLocator::class));
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

    public function getEnvironment()
    {
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
     */
    protected function sessionEnvironment()
    {
        // Check isDev in querystring
        if (isset($_GET['isDev'])) {
            if (isset($_SESSION)) {
                unset($_SESSION['isTest']); // In case we are changing from test mode
                $_SESSION['isDev'] = $_GET['isDev'];
            }
            return self::DEV;
        }

        // Check isTest in querystring
        if (isset($_GET['isTest'])) {
            if (isset($_SESSION)) {
                unset($_SESSION['isDev']); // In case we are changing from dev mode
                $_SESSION['isTest'] = $_GET['isTest'];
            }
            return self::TEST;
        }

        // Check session
        if (!empty($_SESSION['isDev'])) {
            return self::DEV;
        }
        if (!empty($_SESSION['isTest'])) {
            return self::TEST;
        }

        // no session environment
        return null;
    }

    public function boot($flush = false)
    {
        $this->bootPHP();
        $this->bootManifests($flush);
        $this->bootErrorHandling();
        $this->bootDatabaseEnvVars();
        $this->bootConfigs();
        $this->bootDatabaseGlobals();
        $this->validateDatabase();
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
     * Load default database configuration from the $database and $databaseConfig globals
     */
    protected function bootDatabaseGlobals()
    {
        // Now that configs have been loaded, we can check global for database config
        global $databaseConfig;
        global $database;

        // Case 1: $databaseConfig global exists. Merge $database in as needed
        if (!empty($databaseConfig)) {
            if (!empty($database)) {
                $databaseConfig['database'] =  $this->getDatabasePrefix() . $database;
            }

            // Only set it if its valid, otherwise ignore $databaseConfig entirely
            if (!empty($databaseConfig['database'])) {
                DB::setConfig($databaseConfig);

                return;
            }
        }

        // Case 2: $database merged into existing config
        if (!empty($database)) {
            $existing = DB::getConfig();
            $existing['database'] = $this->getDatabasePrefix() . $database;

            DB::setConfig($existing);
        }
    }

    /**
     * Load default database configuration from environment variable
     */
    protected function bootDatabaseEnvVars()
    {
        // Set default database config
        $databaseConfig = $this->getDatabaseConfig();
        $databaseConfig['database'] = $this->getDatabaseName();
        DB::setConfig($databaseConfig);
    }

    /**
     * Check that the database configuration is valid, throwing an HTTPResponse_Exception if it's not
     *
     * @throws HTTPResponse_Exception
     */
    protected function validateDatabase()
    {
        $databaseConfig = DB::getConfig();
        // Gracefully fail if no DB is configured
        if (empty($databaseConfig['database'])) {
            $this->detectLegacyEnvironment();
            $this->redirectToInstaller();
        }
    }

    /**
     * Check if there's a legacy _ss_environment.php file
     *
     * @throws HTTPResponse_Exception
     */
    protected function detectLegacyEnvironment()
    {
        // Is there an _ss_environment.php file?
        if (!file_exists($this->basePath . '/_ss_environment.php') &&
            !file_exists(dirname($this->basePath) . '/_ss_environment.php')
        ) {
            return;
        }

        // Build error response
        $dv = new DebugView();
        $body =
            $dv->renderHeader() .
            $dv->renderInfo(
                "Configuration Error",
                Director::absoluteBaseURL()
            ) .
            $dv->renderParagraph(
                'You need to replace your _ss_environment.php file with a .env file, or with environment variables.<br><br>'
                . 'See the <a href="https://docs.silverstripe.org/en/4/getting_started/environment_management/">'
                . 'Environment Management</a> docs for more information.'
            ) .
            $dv->renderFooter();

        // Raise error
        $response = new HTTPResponse($body, 500);
        throw new HTTPResponse_Exception($response);
    }

    /**
     * If missing configuration, redirect to install.php
     */
    protected function redirectToInstaller()
    {
        // Error if installer not available
        if (!file_exists(Director::publicFolder() . '/install.php')) {
            throw new HTTPResponse_Exception(
                'SilverStripe Framework requires database configuration defined via .env',
                500
            );
        }

        // Redirect to installer
        $response = new HTTPResponse();
        $response->redirect(Director::absoluteURL('install.php'));
        throw new HTTPResponse_Exception($response);
    }

    /**
     * Load database config from environment
     *
     * @return array
     */
    protected function getDatabaseConfig()
    {
        /** @skipUpgrade */
        $databaseConfig = [
            "type" => Environment::getEnv('SS_DATABASE_CLASS') ?: 'MySQLDatabase',
            "server" => Environment::getEnv('SS_DATABASE_SERVER') ?: 'localhost',
            "username" => Environment::getEnv('SS_DATABASE_USERNAME') ?: null,
            "password" => Environment::getEnv('SS_DATABASE_PASSWORD') ?: null,
        ];

        // Set the port if called for
        $dbPort = Environment::getEnv('SS_DATABASE_PORT');
        if ($dbPort) {
            $databaseConfig['port'] = $dbPort;
        }

        // Set the timezone if called for
        $dbTZ = Environment::getEnv('SS_DATABASE_TIMEZONE');
        if ($dbTZ) {
            $databaseConfig['timezone'] = $dbTZ;
        }

        // For schema enabled drivers:
        $dbSchema = Environment::getEnv('SS_DATABASE_SCHEMA');
        if ($dbSchema) {
            $databaseConfig["schema"] = $dbSchema;
        }

        // For SQlite3 memory databases (mainly for testing purposes)
        $dbMemory = Environment::getEnv('SS_DATABASE_MEMORY');
        if ($dbMemory) {
            $databaseConfig["memory"] = $dbMemory;
        }

        // Allow database adapters to handle their own configuration
        DatabaseAdapterRegistry::autoconfigure($databaseConfig);
        return $databaseConfig;
    }

    /**
     * @return string
     */
    protected function getDatabasePrefix()
    {
        return Environment::getEnv('SS_DATABASE_PREFIX') ?: '';
    }

    /**
     * Get name of database
     *
     * @return string
     */
    protected function getDatabaseName()
    {
        // Check globals
        global $database;

        if (!empty($database)) {
            return $this->getDatabasePrefix() . $database;
        }

        global $databaseConfig;

        if (!empty($databaseConfig['database'])) {
            return $databaseConfig['database']; // Note: Already includes prefix
        }

        // Check environment
        $database = Environment::getEnv('SS_DATABASE_NAME');

        if ($database) {
            return $this->getDatabasePrefix() . $database;
        }

        // Auto-detect name
        $chooseName = Environment::getEnv('SS_DATABASE_CHOOSE_NAME');

        if ($chooseName) {
            // Find directory to build name from
            $loopCount = (int)$chooseName;
            $databaseDir = $this->basePath;
            for ($i = 0; $i < $loopCount-1; $i++) {
                $databaseDir = dirname($databaseDir);
            }

            // Build name
            $database = str_replace('.', '', basename($databaseDir));
            $prefix = $this->getDatabasePrefix();

            if ($prefix) {
                $prefix = 'SS_';
            } else {
                // If no prefix, hard-code prefix into database global
                $prefix = '';
                $database = 'SS_' . $database;
            }

            return $prefix . $database;
        }

        // no DB name (may be optional for some connectors)
        return null;
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
     * @return ManifestCacheFactory
     */
    protected function buildManifestCacheFactory()
    {
        return new ManifestCacheFactory([
            'namespace' => 'manifestcache',
            'directory' => TempFolder::getTempFolder($this->basePath),
        ]);
    }

    /**
     * @return bool
     */
    protected function getIncludeTests()
    {
        return false;
    }

    /**
     * Boot all manifests
     *
     * @param bool $flush
     */
    protected function bootManifests($flush)
    {
        // Setup autoloader
        $this->getClassLoader()->init($this->getIncludeTests(), $flush);

        // Find modules
        $this->getModuleLoader()->init($this->getIncludeTests(), $flush);

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
            $defaultSet->init($this->getIncludeTests(), $flush);
        }
    }

    /**
     * Turn on error handling
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
