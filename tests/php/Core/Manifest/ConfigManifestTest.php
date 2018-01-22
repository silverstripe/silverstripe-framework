<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Config\Collections\MemoryConfigCollection;
use SilverStripe\Core\Config\CoreConfigFactory;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\SapphireTest;

class ConfigManifestTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();

        $moduleManifest = new ModuleManifest(dirname(__FILE__) . '/fixtures/configmanifest');
        $moduleManifest->init();
        ModuleLoader::inst()->pushManifest($moduleManifest);
    }

    protected function tearDown()
    {
        ModuleLoader::inst()->popManifest();
        parent::tearDown();
    }

    /**
     * This is a helper method for getting a new manifest
     *
     * @param string $name
     * @return mixed
     */
    protected function getConfigFixtureValue($name)
    {
        return $this->getTestConfig()->get(__CLASS__, $name);
    }

    /**
     * Build a new config based on YMl manifest
     *
     * @return MemoryConfigCollection
     */
    public function getTestConfig()
    {
        $config = new MemoryConfigCollection();
        $factory = new CoreConfigFactory();
        $transformer = $factory->buildYamlTransformerForPath(dirname(__FILE__) . '/fixtures/configmanifest');
        $config->transform([$transformer]);
        return $config;
    }

    /**
     * This is a helper method for displaying a relevant message about a parsing failure
     *
     * @param string $path
     * @return string
     */
    protected function getParsedAsMessage($path)
    {
        return sprintf('Reference path "%s" failed to parse correctly', $path);
    }

    public function testClassRules()
    {
        $config = $this->getConfigFixtureValue('Class');

        $this->assertEquals(
            'Yes',
            @$config['DirectorExists'],
            'Only rule correctly detects existing class'
        );

        $this->assertEquals(
            'No',
            @$config['NoSuchClassExists'],
            'Except rule correctly detects missing class'
        );
    }

    public function testModuleRules()
    {
        $config = $this->getConfigFixtureValue('Module');

        $this->assertEquals(
            'Yes',
            @$config['MysiteExists'],
            'Only rule correctly detects existing module'
        );

        $this->assertEquals(
            'No',
            @$config['NoSuchModuleExists'],
            'Except rule correctly detects missing module'
        );
    }

    public function testEnvVarSetRules()
    {
        Environment::setEnv('ENVVARSET_FOO', '1');
        $config = $this->getConfigFixtureValue('EnvVarSet');

        $this->assertEquals(
            'Yes',
            @$config['FooSet'],
            'Only rule correctly detects set environment variable'
        );

        $this->assertEquals(
            'No',
            @$config['BarSet'],
            'Except rule correctly detects unset environment variable'
        );
    }

    public function testConstantDefinedRules()
    {
        define('CONSTANTDEFINED_FOO', 1);
        $config = $this->getConfigFixtureValue('ConstantDefined');

        $this->assertEquals(
            'Yes',
            @$config['FooDefined'],
            'Only rule correctly detects defined constant'
        );

        $this->assertEquals(
            'No',
            @$config['BarDefined'],
            'Except rule correctly detects undefined constant'
        );
    }

    public function testEnvOrConstantMatchesValueRules()
    {
        Environment::setEnv('CONSTANTMATCHESVALUE_FOO', 'Foo');
        define('CONSTANTMATCHESVALUE_BAR', 'Bar');
        $config = $this->getConfigFixtureValue('EnvOrConstantMatchesValue');

        $this->assertEquals(
            'Yes',
            @$config['FooIsFoo'],
            'Only rule correctly detects environment variable matches specified value'
        );

        $this->assertEquals(
            'Yes',
            @$config['BarIsBar'],
            'Only rule correctly detects constant matches specified value'
        );

        $this->assertEquals(
            'No',
            @$config['FooIsQux'],
            'Except rule correctly detects environment variable that doesn\'t match specified value'
        );

        $this->assertEquals(
            'No',
            @$config['BarIsQux'],
            'Except rule correctly detects environment variable that doesn\'t match specified value'
        );

        $this->assertEquals(
            'No',
            @$config['BazIsBaz'],
            'Except rule correctly detects undefined variable'
        );
    }

    public function testEnvironmentRules()
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        foreach (array('dev', 'test', 'live') as $env) {
            $kernel->setEnvironment($env);
            $config = $this->getConfigFixtureValue('Environment');

            foreach (array('dev', 'test', 'live') as $check) {
                $this->assertEquals(
                    $env == $check ? $check : 'not' . $check,
                    @$config[ucfirst($check) . 'Environment'],
                    'Only & except rules correctly detect environment in env ' . $env
                );
            }
        }
    }

    public function testMultipleRules()
    {
        Environment::setEnv('MULTIPLERULES_ENVVARIABLESET', '1');
        define('MULTIPLERULES_DEFINEDCONSTANT', 'defined');
        $config = $this->getConfigFixtureValue('MultipleRules');

        $this->assertFalse(
            isset($config['TwoOnlyFail']),
            'Fragment is not included if one of the Only rules fails.'
        );

        $this->assertTrue(
            isset($config['TwoOnlySucceed']),
            'Fragment is included if both Only rules succeed.'
        );

        $this->assertFalse(
            isset($config['OneExceptFail']),
            'Fragment is not included if one of the Except rules fails.'
        );

        $this->assertFalse(
            isset($config['TwoExceptFail']),
            'Fragment is not included if both of the Except rules fail.'
        );

        $this->assertFalse(
            isset($config['TwoBlocksFail']),
            'Fragment is not included if one block fails.'
        );

        $this->assertTrue(
            isset($config['TwoBlocksSucceed']),
            'Fragment is included if both blocks succeed.'
        );
    }
}
