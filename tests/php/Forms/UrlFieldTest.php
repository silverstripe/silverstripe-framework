<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\UrlField;
use SilverStripe\Forms\RequiredFields;

class UrlFieldTest extends SapphireTest
{
    public function provideValidate(): array
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

    /**
     * @dataProvider provideValidate
     */
    public function testValidate(string $email, bool $valid)
    {
        $field = new UrlField('MyUrl');
        $field->setValue($email);
        $validator = new RequiredFields();
        $field->validate($validator);
        $expectedCount = $valid ? 0 : 1;
        $this->assertEquals($expectedCount, count($validator->getErrors()));
    }
}
