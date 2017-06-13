<?php

namespace SilverStripe\Core;

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
use SilverStripe\Core\Injector\SilverStripeServiceConfigurationLocator;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\DebugView;
use SilverStripe\Dev\Install\DatabaseAdapterRegistry;
use SilverStripe\Logging\ErrorHandler;
use SilverStripe\ORM\DB;
use SilverStripe\View\ThemeManifest;
use SilverStripe\View\ThemeResourceLoader;

class AppKernel extends CoreKernel
{
    /**
     * @var bool
     */
    protected $flush = false;

    public function __construct($flush = false)
    {
        $this->flush = $flush;

        // Initialise the dependency injector as soon as possible, as it is
        // subsequently used by some of the following code
        $injector = new Injector(array('locator' => SilverStripeServiceConfigurationLocator::class));
        $this->setContainer($injector);
        Injector::set_inst($injector);

        // Manifest cache factory
        $manifestCacheFactory = $this->buildManifestCacheFactory();

        // Class loader
        $classLoader = ClassLoader::inst();
        $classLoader->pushManifest(new ClassManifest(BASE_PATH, $manifestCacheFactory));
        $this->setClassLoader($classLoader);

        // Module loader
        $moduleLoader = ModuleLoader::inst();
        $moduleManifest = new ModuleManifest(BASE_PATH, $manifestCacheFactory);
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
        $themeResourceLoader->addSet('$default', new ThemeManifest(
            BASE_PATH,
            project(),
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
        if ($env = getenv('SS_ENVIRONMENT_TYPE')) {
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

    /**
     * @throws HTTPResponse_Exception
     */
    public function boot()
    {
        $this->bootPHP();
        $this->bootManifests();
        $this->bootErrorHandling();
        $this->bootDatabase();
    }

    /**
     * Configure database
     *
     * @throws HTTPResponse_Exception
     */
    protected function bootDatabase()
    {
        // Check if a DB is named
        $name = $this->getDatabaseName();

        // Gracefully fail if no DB is configured
        if (empty($name)) {
            $this->detectLegacyEnvironment();
            $this->redirectToInstaller();
        }

        // Set default database config
        $databaseConfig = $this->getDatabaseConfig();
        $databaseConfig['database'] = $this->getDatabaseName();
        DB::setConfig($databaseConfig);
    }

    /**
     * Check if there's a legacy _ss_environment.php file
     *
     * @throws HTTPResponse_Exception
     */
    protected function detectLegacyEnvironment()
    {
        // Is there an _ss_environment.php file?
        if (!file_exists(BASE_PATH . '/_ss_environment.php') &&
            !file_exists(dirname(BASE_PATH) . '/_ss_environment.php')
        ) {
            return;
        }

        // Build error response
        $dv = new DebugView();
        $body =
            $dv->renderHeader() .
            $dv->renderInfo(
                "Configuraton Error",
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
        if (!file_exists(BASE_PATH . '/install.php')) {
            throw new HTTPResponse_Exception(
                'SilverStripe Framework requires a $databaseConfig defined.',
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
        // Check global config
        global $databaseConfig;
        if (!empty($databaseConfig)) {
            return $databaseConfig;
        }

        /** @skipUpgrade */
        $databaseConfig = [
            "type" => getenv('SS_DATABASE_CLASS') ?: 'MySQLDatabase',
            "server" => getenv('SS_DATABASE_SERVER') ?: 'localhost',
            "username" => getenv('SS_DATABASE_USERNAME') ?: null,
            "password" => getenv('SS_DATABASE_PASSWORD') ?: null,
        ];

        // Set the port if called for
        $dbPort = getenv('SS_DATABASE_PORT');
        if ($dbPort) {
            $databaseConfig['port'] = $dbPort;
        }

        // Set the timezone if called for
        $dbTZ = getenv('SS_DATABASE_TIMEZONE');
        if ($dbTZ) {
            $databaseConfig['timezone'] = $dbTZ;
        }

        // For schema enabled drivers:
        $dbSchema = getenv('SS_DATABASE_SCHEMA');
        if ($dbSchema) {
            $databaseConfig["schema"] = $dbSchema;
        }

        // For SQlite3 memory databases (mainly for testing purposes)
        $dbMemory = getenv('SS_DATABASE_MEMORY');
        if ($dbMemory) {
            $databaseConfig["memory"] = $dbMemory;
        }

        // Allow database adapters to handle their own configuration
        DatabaseAdapterRegistry::autoconfigure();
        return $databaseConfig;
    }

    /**
     * Get name of database
     *
     * @return string
     */
    protected function getDatabaseName()
    {
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'SS_';

        // Check globals
        global $database;
        if (!empty($database)) {
            return $prefix.$database;
        }
        global $databaseConfig;
        if (!empty($databaseConfig['database'])) {
            return $databaseConfig['database']; // Note: Already includes prefix
        }

        // Check environment
        $database = getenv('SS_DATABASE_NAME');
        if ($database) {
            return $prefix.$database;
        }

        // Auto-detect name
        $chooseName = getenv('SS_DATABASE_CHOOSE_NAME');
        if ($chooseName) {
            // Find directory to build name from
            $loopCount = (int)$chooseName;
            $databaseDir = BASE_PATH;
            for ($i = 0; $i < $loopCount-1; $i++) {
                $databaseDir = dirname($databaseDir);
            }

            // Build name
            $database = str_replace('.', '', basename($databaseDir));
            return $prefix.$database;
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

        global $_increase_time_limit_max;
        $_increase_time_limit_max = -1;

        /**
         * Ensure we have enough memory
         */
        increase_memory_limit_to('64M');

        /**
         * Ensure we don't run into xdebug's fairly conservative infinite recursion protection limit
         */
        increase_xdebug_nesting_level_to(200);

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
            'directory' => getTempFolder(),
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
     */
    protected function bootManifests()
    {
        // Setup autoloader
        $this->getClassLoader()->init($this->getIncludeTests(), $this->flush);

        // Find modules
        $this->getModuleLoader()->init($this->getIncludeTests(), $this->flush);

        // Flush config
        if ($this->flush) {
            $config = $this->getConfigLoader()->getManifest();
            if ($config instanceof CachedConfigCollection) {
                $config->setFlush(true);
            }
        }

        // After loading config, boot _config.php files
        $this->getModuleLoader()->getManifest()->activateConfig();

        // Find default templates
        $defaultSet = $this->getThemeResourceLoader()->getSet('$default');
        if ($defaultSet instanceof ThemeManifest) {
            $defaultSet->init($this->getIncludeTests(), $this->flush);
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
        $errorLog = getenv('SS_ERROR_LOG');
        if ($errorLog) {
            $logger = Injector::inst()->get(LoggerInterface::class);
            if ($logger instanceof Logger) {
                $logger->pushHandler(new StreamHandler(BASE_PATH . '/' . $errorLog, Logger::WARNING));
            } else {
                user_error("SS_ERROR_LOG setting only works with Monolog, you are using another logger", E_USER_WARNING);
            }
        }
    }
}
