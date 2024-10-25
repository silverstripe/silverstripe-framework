<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Validation\FieldValidation\YearFieldValidator;
use SilverStripe\ORM\FieldType\DBYear;

class YearFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        // YearFieldValidator extends IntFieldValidator so only testing a subset
        // of possible values here
        return [
            'valid-int' => [
                'value' => 2021,
                'expected' => true,
            ],
            'valid-zero' => [
                'value' => 0,
                'expected' => true,
            ],
            'valid-null' => [
                'value' => null,
                'expected' => true,
            ],
            'invalid-out-of-range-low' => [
                'value' => 1850,
                'expected' => false,
            ],
            'invalid-out-of-range-high' => [
                'value' => 3000,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $validator = new YearFieldValidator('MyField', $value, DBYear::MIN_YEAR, DBYear::MAX_YEAR);
        $result = $validator->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
