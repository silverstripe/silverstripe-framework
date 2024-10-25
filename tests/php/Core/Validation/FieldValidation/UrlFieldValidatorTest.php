<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\UrlFieldValidator;

class UrlFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        // Using symfony/validator for implementation so only smoke testing
        return [
            'valid-https' => [
                'value' => 'https://www.example.com',
                'expected' => true,
            ],
            'valid-http' => [
                'value' => 'https://www.example.com',
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid-ftp' => [
                'value' => 'ftp://www.example.com',
                'expected' => false,
            ],
            'invalid-no-scheme' => [
                'value' => 'www.example.com',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new UrlFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
