<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;

class NumericFieldValidatorTest extends SapphireTest
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
            'valid-float' => [
                'value' => 123.45,
                'expected' => true,
            ],
            'valid-negative-float' => [
                'value' => -123.45,
                'expected' => true,
            ],
            'valid-max-int' => [
                'value' => PHP_INT_MAX,
                'expected' => true,
            ],
            'valid-min-int' => [
                'value' => PHP_INT_MIN,
                'expected' => true,
            ],
            'valid-max-float' => [
                'value' => PHP_FLOAT_MAX,
                'expected' => true,
            ],
            'valid-min-float' => [
                'value' => PHP_FLOAT_MIN,
                'expected' => true,
            ],
            'invalid-string' => [
                'value' => '123',
                'expected' => false,
            ],
            'invalid-array' => [
                'value' => [123],
                'expected' => false,
            ],
            'invalid-null' => [
                'value' => null,
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
        $validator = new NumericFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
