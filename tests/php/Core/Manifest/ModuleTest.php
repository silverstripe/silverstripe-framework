<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Deprecation;

class ModuleTest extends SapphireTest
{
    public function testUnsetResourcesDir()
    {
        $path = __DIR__ . '/fixtures/ss-projects/withoutCustomResourcesDir';
        $module = new Module($path, $path);
        $this->assertEquals('', $module->getResourcesDir());
    }

    public function testResourcesDir()
    {
        $path = __DIR__ . '/fixtures/ss-projects/withCustomResourcesDir';
        $module = new Module($path, $path);
        $this->assertEquals('customised-resources-dir', $module->getResourcesDir());
    }

    /**
     * @dataProvider ciConfigProvider
     * @param string $fixture The folder containing our test composer file
     * @param string $expectedPhpConfig
     */
    public function testGetCIConfig($fixture, $expectedPhpConfig)
    {
        if (Deprecation::isEnabled()) {
            $this->markTestSkipped('Test calls deprecated code');
        }
        $path = __DIR__ . '/fixtures/phpunit-detection/' . $fixture;
        $module = new Module($path, $path);
        $this->assertEquals(
            $expectedPhpConfig,
            $module->getCIConfig()['PHP'],
            'PHP config is set to ' . $expectedPhpConfig
        );
    }

    public function ciConfigProvider()
    {
        return [
            'empty require-dev' => ['empty-require-dev', Module::CI_UNKNOWN],
            'no require-dev' => ['no-require-dev', Module::CI_UNKNOWN],
            'older version of phpunit' => ['old-phpunit', Module::CI_UNKNOWN],
            'phpunit between 5 and 9' => ['inbetween-phpunit', Module::CI_UNKNOWN],
            'phpunit beyond 9' => ['future-phpunit', Module::CI_UNKNOWN],

            'phpunit 5.0' => ['phpunit-five-zero', Module::CI_PHPUNIT_FIVE],
            'phpunit 5.7' => ['phpunit-five-seven', Module::CI_PHPUNIT_FIVE],
            'phpunit 5 exact version' => ['phpunit-five-exact-version', Module::CI_PHPUNIT_FIVE],
            'phpunit 5 tilde' => ['phpunit-five-tilde', Module::CI_PHPUNIT_FIVE],
            'sminnee 5.7' => ['sminnee-five-seven', Module::CI_PHPUNIT_FIVE],
            'sminnee 5' => ['sminnee-five-seven', Module::CI_PHPUNIT_FIVE],
            'sminnee 5 star' => ['sminnee-five-star', Module::CI_PHPUNIT_FIVE],

            'phpunit 9' => ['phpunit-nine', Module::CI_PHPUNIT_NINE],
            'phpunit 9.5' => ['phpunit-nine-five', Module::CI_PHPUNIT_NINE],
            'future phpunit 9' => ['phpunit-nine-x', Module::CI_PHPUNIT_NINE],
            'phpunit 9 exact version' => ['phpunit-nine-exact', Module::CI_PHPUNIT_NINE],

            'recipe-testing 1' => ['recipe-testing-one', Module::CI_PHPUNIT_FIVE],
            'recipe-testing 1.x' => ['recipe-testing-one-x', Module::CI_PHPUNIT_FIVE],
            'recipe-testing 1.2.x' => ['recipe-testing-one-two-x', Module::CI_PHPUNIT_FIVE],
            'recipe-testing 1 with stability flag' => ['recipe-testing-one-flag', Module::CI_PHPUNIT_FIVE],

            'recipe-testing 2' => ['recipe-testing-two', Module::CI_PHPUNIT_NINE],
            'recipe-testing 2.x' => ['recipe-testing-two-x', Module::CI_PHPUNIT_NINE],
            'recipe-testing 2 exact' => ['recipe-testing-two-x', Module::CI_PHPUNIT_NINE],
        ];
    }
}
