<?php

namespace SilverStripe\i18n\Tests;

use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\Messages\YamlReader;

class YamlReaderTest extends SapphireTest
{
    /**
     *
     */
    public function testRead()
    {
        $reader = new YamlReader();
        $path = __DIR__ . '/i18nTest/_fakewebroot/i18ntestmodule/lang/en.yml';
        $output = $reader->read('en', $path);
        $expected = [
            'NONAMESPACE' => 'Include Entity without Namespace',
            'SPRINTFNONAMESPACE' => 'My replacement no namespace: %s',
            'SPRINTFINCLUDENONAMESPACE' => 'My include replacement no namespace: %s',
            'LAYOUTTEMPLATENONAMESPACE' => 'Layout Template no namespace',
            'i18nTestModule.ENTITY' => 'Entity with "Double Quotes"',
            'i18nTestModule.ADDITION' => 'Addition',
            'i18nTestModule.MAINTEMPLATE' => 'Main Template',
            'i18nTestModule.WITHNAMESPACE' => 'Include Entity with Namespace',
            'i18nTestModule.LAYOUTTEMPLATE' => 'Layout Template',
            'i18nTestModule.SPRINTFNAMESPACE' => 'My replacement: %s',
            'i18nTestModule.PLURAL' => [
                'one' => 'A test',
                'other' => '{count} tests',
            ],
            'i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE' => 'My include replacement: %s',
        ];
        $this->assertEquals($expected, $output);
    }
}
