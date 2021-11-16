<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Dev\SapphireTest;

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
     * @dataProvider ciLibraryProvider
     */
    public function testGetCILibrary($fixture, $expected)
    {
        $path = __DIR__ . '/fixtures/phpunit-detection/' . $fixture;
        $module = new Module($path, $path);
        $this->assertEquals($expected, $module->getCILibrary());
    }

    public function ciLibraryProvider()
    {
        return [
            'empty require-dev' => ['empty-require-dev', Module::CI_PHPUNIT_UNKNOWN],
            'no require-dev' => ['no-require-dev', Module::CI_PHPUNIT_UNKNOWN],
            'older version of phpunit' => ['old-phpunit', Module::CI_PHPUNIT_UNKNOWN],
            'phpunit between 5 and 9' => ['inbetween-phpunit', Module::CI_PHPUNIT_UNKNOWN],
            'phpunit beyond 9' => ['future-phpunit', Module::CI_PHPUNIT_UNKNOWN],

            'phpunit 5.0' => ['phpunit-five-zero', Module::CI_PHPUNIT_FIVE],
            'phpunit 5.7' => ['phpunit-five-seven', Module::CI_PHPUNIT_FIVE],
            'sminnee 5.7' => ['sminnee-five-seven', Module::CI_PHPUNIT_FIVE],
            'sminnee 5' => ['sminnee-five-seven', Module::CI_PHPUNIT_FIVE],

            'phpunit 9' => ['phpunit-nine', Module::CI_PHPUNIT_NINE],
            'phpunit 9.5' => ['phpunit-nine-five', Module::CI_PHPUNIT_NINE],
            'future phpunit 9' => ['phpunit-nine-x', Module::CI_PHPUNIT_NINE],

            'recipe-testing 1' => ['recipe-testing-one', Module::CI_PHPUNIT_FIVE],
            'recipe-testing 1.x' => ['recipe-testing-one-x', Module::CI_PHPUNIT_FIVE],
            'recipe-testing 1.2.x' => ['recipe-testing-one-two-x', Module::CI_PHPUNIT_FIVE],

            'recipe-testing 2' => ['recipe-testing-two', Module::CI_PHPUNIT_NINE],
            'recipe-testing 2.x' => ['recipe-testing-two-x', Module::CI_PHPUNIT_NINE],
        ];
    }
}
