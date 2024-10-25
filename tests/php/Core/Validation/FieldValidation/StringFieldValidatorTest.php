<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Dev\SapphireTest;

class StringFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid-no-limit' => [
                'value' => 'fish',
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => true,
            ],
            'valid-blank' => [
                'value' => '',
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => true,
            ],
            'valid-max' => [
                'value' => 'fish',
                'minLength' => 0,
                'maxLength' => 4,
                'exception' => false,
                'expected' => true,
            ],
            'valid-less-than-max-null-min' => [
                'value' => 'fish',
                'minLength' => null,
                'maxLength' => 4,
                'exception' => false,
                'expected' => true,
            ],
            'valid-less-than-max-unicode' => [
                'value' => '☕☕☕☕',
                'minLength' => 0,
                'maxLength' => 4,
                'exception' => false,
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => true,
            ],
            'exception-negative-min' => [
                'value' => 'fish',
                'minLength' => -1,
                'maxLength' => null,
                'exception' => true,
                'expected' => false,
            ],
            'exception-negative-max' => [
                'value' => 'fish',
                'minLength' => null,
                'maxLength' => -1,
                'exception' => true,
                'expected' => false,
            ],
            'exception-max-below-min' => [
                'value' => 'fish',
                'minLength' => 20,
                'maxLength' => 10,
                'exception' => true,
                'expected' => false,
            ],
            'invalid-below-min' => [
                'value' => 'fish',
                'minLength' => 5,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-below-min-unicode' => [
                'value' => '☕☕☕☕',
                'minLength' => 5,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-blank-with-min' => [
                'value' => '',
                'minLength' => 5,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-above-min' => [
                'value' => 'fish',
                'minLength' => 0,
                'maxLength' => 3,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-above-min-unicode' => [
                'value' => '☕☕☕☕',
                'minLength' => 0,
                'maxLength' => 3,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-int' => [
                'value' => 123,
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-float' => [
                'value' => 123.56,
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-true' => [
                'value' => true,
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-false' => [
                'value' => false,
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
            'invalid-array' => [
                'value' => ['fish'],
                'minLength' => null,
                'maxLength' => null,
                'exception' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, ?int $minLength, ?int $maxLength, bool $exception, bool $expected): void
    {
        if ($exception) {
            $this->expectException(InvalidArgumentException::class);
        }
        $validator = new StringFieldValidator('MyField', $value, $minLength, $maxLength);
        $result = $validator->validate();
        if (!$exception) {
            $this->assertSame($expected, $result->isValid());
        }
    }
}
