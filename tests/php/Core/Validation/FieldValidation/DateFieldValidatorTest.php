<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\DateFieldValidator;

class DateFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid' => [
                'value' => '2020-09-15',
                'expected' => true,
            ],
            'valid-blank-string' => [
                'value' => '',
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid' => [
                'value' => '2020-02-30',
                'expected' => false,
            ],
            'invalid-wrong-format' => [
                'value' => '15-09-2020',
                'expected' => false,
            ],
            'invalid-date-time' => [
                'value' => '2020-09-15 13:34:56',
                'expected' => false,
            ],
            'invalid-time' => [
                'value' => '13:34:56',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new DateFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
