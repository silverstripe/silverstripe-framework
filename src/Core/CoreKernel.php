<?php

namespace SilverStripe\Core;

use InvalidArgumentException;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\View\ThemeResourceLoader;

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

    public function boot($flush = false)
    {
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

    public function getEnvironment()
    {
        return $this->enviroment ?: self::LIVE;
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
