<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\UrlField;
use SilverStripe\Forms\RequiredFields;
use PHPUnit\Framework\Attributes\DataProvider;

class UrlFieldTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            [
                'url' => '',
                'valid' => true,
            ],
            [
                'url' => '',
                'valid' => true,
            ],
            [
                'url' => 'http://example-123.com',
                'valid' => true,
            ],
            [
                'url' => 'https://example-123.com',
                'valid' => true,
            ],
            [
                'url' => 'ftp://example-123.com',
                'valid' => false,
            ],
            [
                'url' => 'http://example-123.com:8080',
                'valid' => true,
            ],
            [
                'url' => 'http://example_with_underscore_in_host.com',
                'valid' => true,
            ],
            [
                'url' => 'http://subdomain.example-123.com',
                'valid' => true,
            ],
            [
                'url' => 'http://subdomain_with_underscores.example-123.com',
                'valid' => true,
            ],
            [
                'url' => 'http://subdomain-with-dashes.example-123.com',
                'valid' => true,
            ],
            [
                'url' => 'http://example-123.com:8080/path_with_underscores_(and)_parens-and-dashes',
                'valid' => true,
            ],
            [
                'url' => 'http://example-123.com:8080/path/?query=string&some=1#fragment',
                'valid' => true,
            ],
            [
                'url' => 'http://a/b/c/g;x?y#s',
                'valid' => true,
            ],
            [
                'url' => 'http://a:123/b/c/g;x?y#s',
                'valid' => true,
            ],
            [
                'url' => 'example-123.com',
                'valid' => false,
            ],
            [
                'url' => 'nope',
                'valid' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(string $url, bool $valid)
    {
        $field = new UrlField('MyUrl');
        $field->setValue($url);
        $validator = new RequiredFields();
        $field->validate($validator);
        $expectedCount = $valid ? 0 : 1;
        $this->assertEquals($expectedCount, count($validator->getErrors()));
    }

    public function testAllowedProtocols(): void
    {
        $field = new UrlField('MyUrl');
        // Defaults should be http and https
        $this->assertSame(['https', 'http'], $field->getAllowedProtocols());

        // Defaults change with config, and ignore keys
        UrlField::config()->set('default_protocols', ['my-key' => 'ftp']);
        $this->assertSame(['ftp'], $field->getAllowedProtocols());

        // Can set explicit protocols - again keys are ignored
        $field->setAllowedProtocols(['http', 'key' => 'irc', 'nntp']);
        $this->assertSame(['http', 'irc', 'nntp'], $field->getAllowedProtocols());

        // Can reset back to config defaults
        $field->setAllowedProtocols([]);
        $this->assertSame(['ftp'], $field->getAllowedProtocols());
    }
}
