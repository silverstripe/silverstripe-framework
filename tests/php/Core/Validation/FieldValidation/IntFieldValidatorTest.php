<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\IntFieldValidator;

class IntFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid-int' => [
                'value' => 123,
                'expected' => true,
            ],
            'valid-zero' => [
                'value' => 0,
                'expected' => true,
            ],
            'valid-negative-int' => [
                'value' => -123,
                'expected' => true,
            ],
            'valid-max-int' => [
                'value' => 2147483647,
                'expected' => true,
            ],
            'valid-min-int' => [
                'value' => -2147483648,
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid-out-of-bounds' => [
                'value' => 2147483648,
                'expected' => false,
            ],
            'invalid-out-of-negative-bounds' => [
                'value' => -2147483649,
                'expected' => false,
            ],
            'invalid-string-int' => [
                'value' => '123',
                'expected' => false,
            ],
            'invalid-float' => [
                'value' => 123.45,
                'expected' => false,
            ],
            'invalid-array' => [
                'value' => [123],
                'expected' => false,
            ],
            'invalid-true' => [
                'value' => true,
                'expected' => false,
            ],
            'invalid-false' => [
                'value' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new IntFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
