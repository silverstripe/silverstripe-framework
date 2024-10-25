<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;
use InvalidArgumentException;

class NumericFieldValidatorTest extends SapphireTest
{
    public static function provideValidateType(): array
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
            'valid-null' => [
                'value' => null,
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

    #[DataProvider('provideValidateType')]
    public function testValidateType(mixed $value, bool $expected): void
    {
        $validator = new NumericFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }

    public static function provideValidate(): array
    {
        return [
            'valid' => [
                'value' => 10,
                'minValue' => null,
                'maxValue' => null,
                'exception' => false,
                'expected' => true,
            ],
            'valid-min' => [
                'value' => 15,
                'minValue' => 10,
                'maxValue' => null,
                'exception' => false,
                'expected' => true,
            ],
            'valid-min-equal' => [
                'value' => 10,
                'minValue' => 10,
                'maxValue' => null,
                'exception' => false,
                'expected' => true,
            ],
            'valid-max' => [
                'value' => 5,
                'minValue' => null,
                'maxValue' => 10,
                'exception' => false,
                'expected' => true,
            ],
            'valid-max-equal' => [
                'value' => 10,
                'minValue' => null,
                'maxValue' => 10,
                'exception' => false,
                'expected' => true,
            ],
            'valid-min-max-between' => [
                'value' => 15,
                'minValue' => 10,
                'maxValue' => 20,
                'exception' => false,
                'expected' => true,
            ],
            'valid-min-max-equal' => [
                'value' => 10,
                'minValue' => 10,
                'maxValue' => 10,
                'exception' => false,
                'expected' => true,
            ],
            'exception-min-above-max' => [
                'value' => 15,
                'minValue' => 20,
                'maxValue' => 10,
                'exception' => true,
                'expected' => false,
            ],
            'invalid-below-min' => [
                'value' => 5,
                'minValue' => 10,
                'maxValue' => 20,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-above-max' => [
                'value' => 25,
                'minValue' => 10,
                'maxValue' => 20,
                'exception' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(int $value, ?int $minValue, ?int $maxValue, bool $exception, bool $expected): void
    {
        if ($exception) {
            $this->expectException(InvalidArgumentException::class);
        }
        $validator = new NumericFieldValidator('MyField', $value, $minValue, $maxValue);
        $result = $validator->validate();
        if (!$exception) {
            $this->assertSame($expected, $result->isValid());
        }
    }
}
