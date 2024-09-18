<?php

namespace SilverStripe\i18n\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\Messages\YamlReader;
use Symfony\Component\Translation\Exception\InvalidResourceException;

class YamlReaderTest extends SapphireTest
{
    public function testRead()
    {
        $reader = new YamlReader();
        $path = __DIR__ . '/i18nTest/_fakewebroot/i18ntestmodule/lang/en.yml';
        $output = $reader->read('en', $path);
        $expected = [
            'NONAMESPACE' => 'Include Entity without Namespace',
            'REPLACEMENTNONAMESPACE' => 'My replacement no namespace: {replacement}',
            'REPLACEMENTINCLUDENONAMESPACE' => 'My include replacement no namespace: {replacement}',
            'LAYOUTTEMPLATENONAMESPACE' => 'Layout Template no namespace',
            'i18nTestModule.ENTITY' => 'Entity with "Double Quotes"',
            'i18nTestModule.ADDITION' => 'Addition',
            'i18nTestModule.MAINTEMPLATE' => 'Main Template',
            'i18nTestModule.WITHNAMESPACE' => 'Include Entity with Namespace',
            'i18nTestModule.LAYOUTTEMPLATE' => 'Layout Template',
            'i18nTestModule.REPLACEMENTNAMESPACE' => 'My replacement: {replacement}',
            'i18nTestModuleInclude_ss.REPLACEMENTINCLUDENAMESPACE' => 'My include replacement: {replacement}',
            'i18nTestModule.PLURALS' => [
                'one' => 'A test',
                'other' => '{count} tests',
            ],
            'Month.PLURALS' => [
                'one' => 'A month',
                'other' => '{count} months',
            ],
        ];
        $this->assertEquals($expected, $output);
    }


    public function testThrowsMeaningfulExceptionWhenYmlIsCorrupted()
    {
        $path = __DIR__ . '/i18nTest/_fakewebroot/i18ntestmodule/lang/en_corrupt.yml';
        $this->expectException(InvalidResourceException::class);
        $regex_path = str_replace('.', '\.', $path ?? '');
        $this->expectExceptionMessageMatches('@^Error parsing YAML, invalid file "' . $regex_path . '"\. Message: ([\w ].*) line 5 @');
        $reader = new YamlReader();
        $reader->read('en', $path);
    }
}
