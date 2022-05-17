<?php

namespace SilverStripe\Framework\Tests\Behaviour;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use SilverStripe\BehatExtension\Context\MainContextAwareTrait;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;

/**
 * Provides custom config abilities.
 */
class ConfigContext implements Context
{
    use MainContextAwareTrait;

    /**
     * Path to yml config fixtures
     *
     * @var string
     */
    protected $configPath;

    /**
     * Track all config files installed into mysite/_config/behat*.yml
     *
     * @var string[]
     */
    protected $activatedConfigFiles = [];

    /**
     * Create new ConfigContext
     *
     * @param string $configPath Path to config files. E.g.
     * `%paths.modules.framework%/tests/behat/features/configs/`
     */
    public function __construct($configPath = null)
    {
        if (empty($configPath)) {
            throw new InvalidArgumentException("filesPath is required");
        }
        $this->setConfigPath($configPath);
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->configPath;
    }

    /**
     * @param string $configPath
     * @return $this
     */
    public function setConfigPath($configPath)
    {
        $this->configPath = $configPath;
        return $this;
    }

    /**
     * Clean up all files after scenario
     *
     * @AfterScenario
     * @param AfterScenarioScope $event
     */
    public function afterResetAssets(AfterScenarioScope $event)
    {
        // No files to cleanup
        if (empty($this->activatedConfigFiles)) {
            return;
        }

        foreach ($this->activatedConfigFiles as $configFile) {
            if (file_exists($configFile ?? '')) {
                unlink($configFile ?? '');
            }
        }
        $this->activatedConfigFiles = [];

        // Flush
        $this->stepIFlush();
    }

    /**
     * @When /^(?:|I )go flush the website$/
     */
    public function stepIFlush()
    {
        $this->getMainContext()->visit('/?flush=all');
    }

    /**
     * Setup a config file. The $filename should be a yml filename
     * placed in the directory specified by configPaths argument
     * to fixture constructor.
     *
     * @When /^(?:|I )have a config file "([^"]+)"$/
     * @param string $filename
     */
    public function stepIHaveConfigFile($filename)
    {
        // Ensure site is in dev mode
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        Assert::assertEquals(Kernel::DEV, $kernel->getEnvironment(), "Site is in dev mode");

        // Ensure file exists in specified fixture dir
        $sourceDir = $this->getConfigPath();
        $sourcePath = "{$sourceDir}/{$filename}";
        Assert::assertFileExists($sourcePath, "Config file {$filename} exists");

        // Get destination
        $project = ModuleManifest::config()->get('project') ?: 'mysite';
        $mysite = ModuleLoader::getModule($project);
        Assert::assertNotNull($mysite, 'Project exists');
        $destPath = $mysite->getResource("_config/{$filename}")->getPath();
        Assert::assertFileDoesNotExist($destPath, "Config file {$filename} hasn't already been loaded");

        // Load
        $this->activatedConfigFiles[] = $destPath;
        copy($sourcePath ?? '', $destPath ?? '');

        // Flush website
        $this->stepIFlush();
    }
}
