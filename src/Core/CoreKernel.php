<?php

namespace SilverStripe\Core;

use InvalidArgumentException;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Simple Kernel container
 */
class CoreKernel implements Kernel
{
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
     * @var ThemeResourceLoader
     */
    protected $themeResourceLoader = null;

    public function boot()
    {
    }

    public function shutdown()
    {
    }

    public function nest()
    {
        // TODO: Implement nest() method.
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer(Injector $container)
    {
        $this->container = $container;
        $container->registerService($this, Kernel::class);
        return $this;
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
        if (!in_array($environment, [self::DEV, self::TEST, self::LIVE])) {
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
