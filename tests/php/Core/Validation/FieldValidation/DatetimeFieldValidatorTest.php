<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\DatetimeFieldValidator;

class DatetimeFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid' => [
                'value' => '2020-09-15 13:34:56',
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid-date' => [
                'value' => '2020-02-30 13:34:56',
                'expected' => false,
            ],
            'invalid-time' => [
                'value' => '2020-02-15 13:99:56',
                'expected' => false,
            ],
            'invalid-wrong-format' => [
                'value' => '15-09-2020 13:34:56',
                'expected' => false,
            ],
            'invalid-date-only' => [
                'value' => '2020-09-15',
                'expected' => false,
            ],
            'invalid-time-only' => [
                'value' => '13:34:56',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new DatetimeFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
