<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\OptionFieldValidator;

class OptionFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid-string' => [
                'value' => 'cat',
                'allowedValues' => ['cat', 'dog'],
                'expected' => true,
            ],
            'valid-int' => [
                'value' => 123,
                'allowedValues' => [123, 456],
                'expected' => true,
            ],
            'valid-none' => [
                'value' => '',
                'allowedValues' => ['cat', 'dog'],
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'allowedValues' => ['cat', 'dog'],
                'expected' => true,
            ],
            'invalid' => [
                'value' => 'fish',
                'allowedValues' => ['cat', 'dog'],
                'expected' => false,
            ],
            'invalid-strict' => [
                'value' => '123',
                'allowedValues' => [123, 456],
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, array $allowedValues, bool $expected): void
    {
        $validator = new OptionFieldValidator('MyField', $value, $allowedValues);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
