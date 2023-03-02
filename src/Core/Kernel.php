<?php

namespace SilverStripe\Core;

use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
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
     *
     * @param bool $flush
     */
    public function boot($flush = false);

    /**
     * Shutdowns the kernel.
     */
    public function shutdown();

    /**
     * Nests this kernel, all components, and returns the nested value.
     *
     * @return Kernel
     */
    public function nest();

    /**
     * Ensures this kernel is the active kernel after or during nesting
     *
     * @return $this
     */
    public function activate();

    /**
     * @return Injector
     */
    public function getContainer();

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
     * Get loader for injector instance
     *
     * @return InjectorLoader
     */
    public function getInjectorLoader();

    /**
     * @param InjectorLoader $injectorLoader
     * @return $this
     */
    public function setInjectorLoader(InjectorLoader $injectorLoader);

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

    /**
     * Returns whether the Kernel has been flushed on boot
     *
     * @return bool|null null if the kernel hasn't been booted yet
     */
    public function isFlushed(): ?bool;
}
