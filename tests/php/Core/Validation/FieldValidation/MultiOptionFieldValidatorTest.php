<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\MultiOptionFieldValidator;

class MultiOptionFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid-string' => [
                'value' => ['cat'],
                'allowedValues' => ['cat', 'dog'],
                'exception' => false,
                'expected' => true,
            ],
            'valid-multi-string' => [
                'value' => ['cat', 'dog'],
                'allowedValues' => ['cat', 'dog'],
                'exception' => false,
                'expected' => true,
            ],
            'valid-none' => [
                'value' => [],
                'allowedValues' => ['cat', 'dog'],
                'exception' => false,
                'expected' => true,
            ],
            'valid-int' => [
                'value' => [123],
                'allowedValues' => [123, 456],
                'exception' => false,
                'expected' => true,
            ],
            'exception-not-array' => [
                'value' => 'cat,dog',
                'allowedValues' => ['cat', 'dog'],
                'exception' => true,
                'expected' => false,
            ],
            'invalid' => [
                'value' => ['fish'],
                'allowedValues' => ['cat', 'dog'],
                'exception' => false,
                'expected' => false,
            ],
            'invalid-null' => [
                'value' => [null],
                'allowedValues' => ['cat', 'dog'],
                'exception' => false,
                'expected' => false,
            ],
            'invalid-multi' => [
                'value' => ['dog', 'fish'],
                'allowedValues' => ['cat', 'dog'],
                'exception' => false,
                'expected' => false,
            ],
            'invalid-strict' => [
                'value' => ['123'],
                'allowedValues' => [123, 456],
                'exception' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, array $allowedValues, bool $exception, bool $expected): void
    {
        if ($exception) {
            $this->expectException(InvalidArgumentException::class);
        }
        $validator = new MultiOptionFieldValidator('MyField', $value, $allowedValues);
        $result = $validator->validate();
        if (!$exception) {
            $this->assertSame($expected, $result->isValid());
        }
    }
}
