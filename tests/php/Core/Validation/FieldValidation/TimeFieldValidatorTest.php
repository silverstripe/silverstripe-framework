<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\TimeFieldValidator;

class TimeFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid' => [
                'value' => '13:34:56',
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid' => [
                'value' => '13:99:56',
                'expected' => false,
            ],
            'invalid-wrong-format' => [
                'value' => '13-34-56',
                'expected' => false,
            ],
            'invalid-date-time' => [
                'value' => '2020-09-15 13:34:56',
                'expected' => false,
            ],
            'invalid-date' => [
                'value' => '2020-09-15',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new TimeFieldValidator('MyField', $value);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
