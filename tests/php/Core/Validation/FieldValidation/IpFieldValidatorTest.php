<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\IpFieldValidator;

class IpFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        // Using symfony/validator for implementation so only smoke testing
        return [
            'valid-ipv4' => [
                'value' => '127.0.0.1',
                'expected' => true,
            ],
            'valid-ipv6' => [
                'value' => '0:0:0:0:0:0:0:1',
                'expected' => true,
            ],
            'valid-ipv6-short' => [
                'value' => '::1',
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid' => [
                'value' => '12345',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new IpFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
