<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\BigIntFieldValidator;

class BigIntFieldValidatorTest extends SapphireTest
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
                'value' => 9223372036854775807,
                'expected' => true,
            ],
            'valid-min-int' => [
                'value' => '-9223372036854775808',
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            // Note: cannot test out of range values as they casting them to int
            // will change the value to PHP_INT_MIN/PHP_INT_MAX
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
        // On 64-bit systems, -9223372036854775808 will end up as a float
        // however it works correctly when cast to an int
        if ($value === '-9223372036854775808') {
            $value = (int) $value;
        }
        $validator = new BigIntFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
