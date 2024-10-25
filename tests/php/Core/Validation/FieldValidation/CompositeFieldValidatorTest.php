<?php

namespace SilverStripe\Core\Tests\Validation\FieldValidation;

use InvalidArgumentException;
use stdClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Validation\FieldValidation\CompositeFieldValidator;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBVarchar;

class CompositeFieldValidatorTest extends SapphireTest
{
    public static function provideValidate(): array
    {
        return [
            'valid' => [
                'valueBoolean' => true,
                'valueString' => 'fish',
                'valueIsNull' => false,
                'exception' => null,
                'expected' => true,
            ],
            'exception-not-iterable' => [
                'valueBoolean' => true,
                'valueString' => 'not-iterable',
                'valueIsNull' => false,
                'exception' => InvalidArgumentException::class,
                'expected' => true,
            ],
            'exception-not-field-validator' => [
                'valueBoolean' => true,
                'valueString' => 'no-field-validation',
                'valueIsNull' => false,
                'exception' => InvalidArgumentException::class,
                'expected' => true,
            ],
            'exception-do-not-skip-null' => [
                'valueBoolean' => true,
                'valueString' => 'fish',
                'valueIsNull' => true,
                'exception' => InvalidArgumentException::class,
                'expected' => true,
            ],
            'invalid-bool-field' => [
                'valueBoolean' => 'dog',
                'valueString' => 'fish',
                'valueIsNull' => false,
                'exception' => null,
                'expected' => false,
            ],
            'invalid-string-field' => [
                'valueBoolean' => true,
                'valueString' => 456.789,
                'valueIsNull' => false,
                'exception' => null,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(
        mixed $valueBoolean,
        mixed $valueString,
        bool $valueIsNull,
        ?string $exception,
        bool $expected
    ): void {
        if ($exception) {
            $this->expectException($exception);
        }
        if ($valueIsNull) {
            $iterable = null;
        } else {
            $booleanField = new DBBoolean('BooleanField');
            $booleanField->setValue($valueBoolean);
            if ($exception && $valueString === 'no-field-validation') {
                $stringField = new stdClass();
            } else {
                $stringField = new DBVarchar('StringField');
                $stringField->setValue($valueString);
            }
            if ($exception && $valueString === 'not-iterable') {
                $iterable = 'banana';
            } else {
                $iterable = [$booleanField, $stringField];
            }
        }
        $validator = new CompositeFieldValidator('MyField', $iterable);
        $result = $validator->validate();
        if (!$exception) {
            $this->assertSame($expected, $result->isValid());
        }
    }
}
