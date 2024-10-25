<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\BooleanFieldValidator;

class BooleanFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid-true' => [
                'value' => true,
                'expected' => true,
            ],
            'valid-false' => [
                'value' => false,
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid-int-1' => [
                'value' => 1,
                'expected' => false,
            ],
            'invalid-int-0' => [
                'value' => 0,
                'expected' => false,
            ],
            'invalid-string-1' => [
                'value' => '1',
                'expected' => false,
            ],
            'invalid-string-0' => [
                'value' => '0',
                'expected' => false,
            ],
            'invalid-string-true' => [
                'value' => 'true',
                'expected' => false,
            ],
            'invalid-string-false' => [
                'value' => 'false',
                'expected' => false,
            ],
            'invalid-string' => [
                'value' => 'abc',
                'expected' => false,
            ],
            'invalid-int' => [
                'value' => 123,
                'expected' => false,
            ],
            'invalid-array' => [
                'value' => [],
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new BooleanFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
