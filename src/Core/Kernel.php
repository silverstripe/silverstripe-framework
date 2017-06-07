<?php

namespace SilverStripe\Core;

use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Represents the core state of a SilverStripe application
 * Based loosely on symfony/http-kernel's KernelInterface component
 */
interface Kernel
{
    /**
     * Test environment
     */
    const TEST = 'test';

    /**
     * Dev environment
     */
    const DEV = 'dev';

    /**
     * Live (default) environment
     */
    const LIVE = 'live';

    /*
     * Boots the current kernel
     */
    public function boot();

    /**
     * Shutdowns the kernel.
     */
    public function shutdown();

    /**
     * Nests this kernel, all components, and returns the nested value.
     *
     * @return static
     */
    public function nest();

    /**
     * @return Injector
     */
    public function getContainer();

    /**
     * Sets injector
     *
     * @param Injector $container
     * @return $this
     */
    public function setContainer(Injector $container);

    /**
     * @return ClassLoader
     */
    public function getClassLoader();

    /**
     * @param ClassLoader $classLoader
     * @return $this
     */
    public function setClassLoader(ClassLoader $classLoader);

    /**
     * @return ModuleLoader
     */
    public function getModuleLoader();

    /**
     * @param ModuleLoader $moduleLoader
     * @return $this
     */
    public function setModuleLoader(ModuleLoader $moduleLoader);

    /**
     * @return ConfigLoader
     */
    public function getConfigLoader();

    /**
     * @param ConfigLoader $configLoader
     * @return $this
     */
    public function setConfigLoader($configLoader);

    /**
     * @return ThemeResourceLoader
     */
    public function getThemeResourceLoader();

    /**
     * @param ThemeResourceLoader $themeResourceLoader
     * @return $this
     */
    public function setThemeResourceLoader($themeResourceLoader);

    /**
     * One of dev, live, or test
     *
     * @return string
     */
    public function getEnvironment();

    /**
     * Sets new environment
     *
     * @param string $environment
     * @return $this
     */
    public function setEnvironment($environment);
}
