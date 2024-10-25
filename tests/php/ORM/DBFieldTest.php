<?php

namespace SilverStripe\ORM\Tests;

use Exception;
use SilverStripe\ORM\FieldType\DBBigInt;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\ORM\FieldType\DBDouble;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBForeignKey;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBLocale;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\ORM\FieldType\DBMultiEnum;
use SilverStripe\ORM\FieldType\DBPercentage;
use SilverStripe\ORM\FieldType\DBPolymorphicForeignKey;
use SilverStripe\ORM\FieldType\DBPrimaryKey;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\ORM\FieldType\DBTime;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBYear;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\ClassInfo;
use ReflectionClass;
use SilverStripe\Core\Validation\FieldValidation\BooleanFieldValidator;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Validation\FieldValidation\BigIntFieldValidator;
use SilverStripe\ORM\FieldType\DBClassName;
use ReflectionMethod;
use SilverStripe\Core\Validation\FieldValidation\CompositeFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\DateFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\DecimalFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\EmailFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\OptionFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\IntFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\IpFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\LocaleFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\MultiOptionFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\TimeFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\UrlFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\YearFieldValidator;
use SilverStripe\ORM\FieldType\DBUrl;
use SilverStripe\ORM\FieldType\DBPolymorphicRelationAwareForeignKey;
use SilverStripe\ORM\FieldType\DBIp;
use SilverStripe\ORM\FieldType\DBEmail;
use SilverStripe\Core\Validation\FieldValidation\DatetimeFieldValidator;
use SilverStripe\ORM\FieldType\DBClassNameVarchar;

/**
 * Tests for DBField objects.
 */
class DBFieldTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DBFieldTest\TestDataObject::class,
    ];

    /**
     * Test the nullValue() method on DBField.
     */
    public function testNullValue()
    {
        /* Float and Double use 0 for "null" value representation */
        $this->assertEquals(0, singleton('Float')->nullValue());
        $this->assertEquals(0, singleton('Double')->nullValue());
    }

    /**
     * Test the prepValueForDB() method on DBField.
     */
    public function testPrepValueForDB()
    {
        /* Float behaviour, asserting we have 0 */
        $float = DBFloat::create();
        $this->assertEquals(0, $float->prepValueForDB(0));
        $this->assertEquals(0, $float->prepValueForDB(null));
        $this->assertEquals(0, $float->prepValueForDB(false));
        $this->assertEquals(0, $float->prepValueForDB(''));
        $this->assertEquals('0', $float->prepValueForDB('0'));

        /* Double behaviour, asserting we have 0 */
        $double = DBDouble::create();
        $this->assertEquals(0, $double->prepValueForDB(0));
        $this->assertEquals(0, $double->prepValueForDB(null));
        $this->assertEquals(0, $double->prepValueForDB(false));
        $this->assertEquals(0, $double->prepValueForDB(''));
        $this->assertEquals('0', $double->prepValueForDB('0'));

        /* Integer behaviour, asserting we have 0 */
        $int = singleton('Int');
        $this->assertEquals(0, $int->prepValueForDB(0));
        $this->assertEquals(0, $int->prepValueForDB(null));
        $this->assertEquals(0, $int->prepValueForDB(false));
        $this->assertEquals(0, $int->prepValueForDB(''));
        $this->assertEquals(0, $int->prepValueForDB('0'));

        /* Integer behaviour, asserting we have 1 */
        $this->assertEquals(1, $int->prepValueForDB(true));
        $this->assertEquals(1, $int->prepValueForDB(1));
        $this->assertEquals(1, $int->prepValueForDB('1'));

        /* Decimal behaviour, asserting we have 0 */
        $decimal = DBDecimal::create();
        $this->assertEquals(0, $decimal->prepValueForDB(0));
        $this->assertEquals(0.0, $decimal->prepValueForDB(0.0));
        $this->assertEquals(0, $decimal->prepValueForDB(null));
        $this->assertEquals(0, $decimal->prepValueForDB(false));
        $this->assertEquals(0, $decimal->prepValueForDB(''));
        $this->assertEquals(0, $decimal->prepValueForDB('0'));
        $this->assertEquals(0.0, $decimal->prepValueForDB('0.0'));

        /* Decimal behaviour, asserting we have 1 */
        $this->assertEquals(1, $decimal->prepValueForDB(true));
        $this->assertEquals(1, $decimal->prepValueForDB(1));
        $this->assertEquals(1.1, $decimal->prepValueForDB(1.1));
        $this->assertEquals(1, $decimal->prepValueForDB('1'));
        $this->assertEquals(1.1, $decimal->prepValueForDB('1.1'));

        /* Boolean behaviour, asserting we have 0 */
        $boolean = DBBoolean::create();
        $this->assertEquals(false, $boolean->prepValueForDB(0));
        $this->assertEquals(false, $boolean->prepValueForDB(null));
        $this->assertEquals(false, $boolean->prepValueForDB(false));
        $this->assertEquals(false, $boolean->prepValueForDB('false'));
        $this->assertEquals(false, $boolean->prepValueForDB('f'));
        $this->assertEquals(false, $boolean->prepValueForDB(''));
        $this->assertEquals(false, $boolean->prepValueForDB('0'));

        /* Boolean behaviour, asserting we have 1 */
        $this->assertEquals(true, $boolean->prepValueForDB(true));
        $this->assertEquals(true, $boolean->prepValueForDB('true'));
        $this->assertEquals(true, $boolean->prepValueForDB('t'));
        $this->assertEquals(true, $boolean->prepValueForDB(1));
        $this->assertEquals(true, $boolean->prepValueForDB('1'));

        /* Varchar behaviour: nullifyifEmpty defaults to true */
        $varchar = DBVarchar::create();
        $this->assertEquals(0, $varchar->prepValueForDB(0));
        $this->assertEquals(null, $varchar->prepValueForDB(null));
        $this->assertEquals(null, $varchar->prepValueForDB(false));
        $this->assertEquals(null, $varchar->prepValueForDB(''));
        $this->assertEquals('0', $varchar->prepValueForDB('0'));
        $this->assertEquals(1, $varchar->prepValueForDB(1));
        $this->assertEquals(true, $varchar->prepValueForDB(true));
        $this->assertEquals('1', $varchar->prepValueForDB('1'));
        $this->assertEquals('00000', $varchar->prepValueForDB('00000'));
        $this->assertEquals(0, $varchar->prepValueForDB(0000));
        $this->assertEquals('test', $varchar->prepValueForDB('test'));
        $this->assertEquals(123, $varchar->prepValueForDB(123));

        /* AllowEmpty Varchar behaviour */
        $varcharField = DBVarchar::create("testfield", 50, ["nullifyEmpty"=>false]);
        $this->assertSame('0', $varcharField->prepValueForDB(0));
        $this->assertSame(null, $varcharField->prepValueForDB(null));
        $this->assertSame('', $varcharField->prepValueForDB(false));
        $this->assertSame('', $varcharField->prepValueForDB(''));
        $this->assertSame('0', $varcharField->prepValueForDB('0'));
        $this->assertSame('1', $varcharField->prepValueForDB(1));
        $this->assertSame('1', $varcharField->prepValueForDB(true));
        $this->assertSame('1', $varcharField->prepValueForDB('1'));
        $this->assertSame('00000', $varcharField->prepValueForDB('00000'));
        $this->assertSame('0', $varcharField->prepValueForDB(0000));
        $this->assertSame('test', $varcharField->prepValueForDB('test'));
        $this->assertSame('123', $varcharField->prepValueForDB(123));
        unset($varcharField);

        /* Text behaviour */
        $text = DBText::create();
        $this->assertEquals('0', $text->prepValueForDB(0));
        $this->assertEquals(null, $text->prepValueForDB(null));
        $this->assertEquals(null, $text->prepValueForDB(false));
        $this->assertEquals(null, $text->prepValueForDB(''));
        $this->assertEquals('0', $text->prepValueForDB('0'));
        $this->assertEquals('1', $text->prepValueForDB(1));
        $this->assertEquals('1', $text->prepValueForDB(true));
        $this->assertEquals('1', $text->prepValueForDB('1'));
        $this->assertEquals('00000', $text->prepValueForDB('00000'));
        $this->assertEquals('0', $text->prepValueForDB(0000));
        $this->assertEquals('test', $text->prepValueForDB('test'));
        $this->assertEquals('123', $text->prepValueForDB(123));

        /* AllowEmpty Text behaviour */
        $textField = DBText::create("testfield", ["nullifyEmpty"=>false]);
        $this->assertSame('0', $textField->prepValueForDB(0));
        $this->assertSame(null, $textField->prepValueForDB(null));
        $this->assertSame('', $textField->prepValueForDB(false));
        $this->assertSame('', $textField->prepValueForDB(''));
        $this->assertSame('0', $textField->prepValueForDB('0'));
        $this->assertSame('1', $textField->prepValueForDB(1));
        $this->assertSame('1', $textField->prepValueForDB(true));
        $this->assertSame('1', $textField->prepValueForDB('1'));
        $this->assertSame('00000', $textField->prepValueForDB('00000'));
        $this->assertSame('0', $textField->prepValueForDB(0000));
        $this->assertSame('test', $textField->prepValueForDB('test'));
        $this->assertSame('123', $textField->prepValueForDB(123));
        unset($textField);

        /* Time behaviour */
        $time = DBTime::create();
        $time->setValue('12:01am');
        $this->assertEquals("00:01:00", $time->getValue());
        $time->setValue('12:59am');
        $this->assertEquals("00:59:00", $time->getValue());
        $time->setValue('11:59am');
        $this->assertEquals("11:59:00", $time->getValue());
        $time->setValue('12:00pm');
        $this->assertEquals("12:00:00", $time->getValue());
        $time->setValue('12:59am');
        $this->assertEquals("00:59:00", $time->getValue());
        $time->setValue('1:00pm');
        $this->assertEquals("13:00:00", $time->getValue());
        $time->setValue('11:59pm');
        $this->assertEquals("23:59:00", $time->getValue());
        $time->setValue('12:00am');
        $this->assertEquals("00:00:00", $time->getValue());
        $time->setValue('00:00:00');
        $this->assertEquals("00:00:00", $time->getValue());

        /* BigInt behaviour */
        $bigInt = DBBigInt::create();
        $bigInt->setValue(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $bigInt->getValue());
    }

    #[DataProvider('dataProviderPrepValueForDBArrayValue')]
    public function testPrepValueForDBArrayValue($dbFieldName, $scalarValueOnly, $extraArgs = [])
    {
        $reflection = new \ReflectionClass($dbFieldName);
        /**
         * @var DBField
         */
        $dbField = $reflection->newInstanceArgs($extraArgs);
        $dbField->setName('SomeField');
        $payload = ['GREATEST(0,?)' => '2'];
        $preparedValue = $dbField->prepValueForDB($payload);
        $this->assertTrue(
            !$scalarValueOnly || !is_array($preparedValue),
            '`prepValueForDB` can not return an array if scalarValueOnly is true'
        );
        $this->assertEquals($scalarValueOnly, $dbField->scalarValueOnly());
    }

    public static function dataProviderPrepValueForDBArrayValue()
    {
        return [
            [DBBigInt::class, true],
            [DBBoolean::class, true],
            [DBCurrency::class, true],
            [DBDate::class, true],
            [DBDatetime::class, true],
            [DBDecimal::class, true],
            [DBDouble::class, true],
            [DBEnum::class, true],
            [DBFloat::class, true],
            [DBForeignKey::class, true, ['SomeField']],
            [DBHTMLText::class, true],
            [DBHTMLVarchar::class, true],
            [DBInt::class, true],
            [DBLocale::class, true],
            [DBMoney::class, false],
            [DBMultiEnum::class, true, ['SomeField', ['One', 'Two', 'Three']]],
            [DBPercentage::class, true],
            [DBPolymorphicForeignKey::class, false, ['SomeField']],
            [DBText::class, true],
            [DBTime::class, true],
            [DBVarchar::class, true],
            [DBYear::class, true],
        ];
    }

    public function testExists()
    {
        $varcharField = new DBVarchar("testfield");
        $this->assertTrue($varcharField->getNullifyEmpty());
        $varcharField->setValue('abc');
        $this->assertTrue($varcharField->exists());
        $varcharField->setValue('');
        $this->assertFalse($varcharField->exists());
        $varcharField->setValue(null);
        $this->assertFalse($varcharField->exists());

        $varcharField = new DBVarchar("testfield", 50, ['nullifyEmpty'=>false]);
        $this->assertFalse($varcharField->getNullifyEmpty());
        $varcharField->setValue('abc');
        $this->assertTrue($varcharField->exists());
        $varcharField->setValue('');
        $this->assertFalse($varcharField->exists());
        $varcharField->setValue(null);
        $this->assertFalse($varcharField->exists());

        $textField = new DBText("testfield");
        $this->assertTrue($textField->getNullifyEmpty());
        $textField->setValue('abc');
        $this->assertTrue($textField->exists());
        $textField->setValue('');
        $this->assertFalse($textField->exists());
        $textField->setValue(null);
        $this->assertFalse($textField->exists());

        $textField = new DBText("testfield", ['nullifyEmpty'=>false]);
        $this->assertFalse($textField->getNullifyEmpty());
        $textField->setValue('abc');
        $this->assertTrue($textField->exists());
        $textField->setValue('');
        $this->assertFalse($textField->exists());
        $textField->setValue(null);
        $this->assertFalse($textField->exists());
    }

    public function testStringFieldsWithMultibyteData()
    {
        $plainFields = ['Varchar', 'Text'];
        $htmlFields = ['HTMLVarchar', 'HTMLText', 'HTMLFragment'];
        $allFields = array_merge($plainFields, $htmlFields);

        $value = 'üåäöÜÅÄÖ';
        foreach ($allFields as $stringField) {
            $stringField = DBString::create_field($stringField, $value);
            for ($i = 1; $i < mb_strlen($value ?? ''); $i++) {
                $expected = mb_substr($value ?? '', 0, $i) . '…';
                $this->assertEquals($expected, $stringField->LimitCharacters($i));
            }
        }

        $value = '<p>üåäö&amp;ÜÅÄÖ</p>';
        foreach ($htmlFields as $stringField) {
            $stringObj = DBString::create_field($stringField, $value);

            // Converted to plain text
            $this->assertEquals('üåäö&ÜÅÄ…', $stringObj->LimitCharacters(8));

            // But which will be safely cast in templates
            $this->assertEquals('üåäö&amp;ÜÅÄ…', $stringObj->obj('LimitCharacters', [8])->forTemplate());
        }

        $this->assertEquals('ÅÄÖ', DBText::create_field('Text', 'åäö')->UpperCase());
        $this->assertEquals('åäö', DBText::create_field('Text', 'ÅÄÖ')->LowerCase());
        $this->assertEquals('<P>ÅÄÖ</P>', DBHTMLText::create_field('HTMLFragment', '<p>åäö</p>')->UpperCase());
        $this->assertEquals('<p>åäö</p>', DBHTMLText::create_field('HTMLFragment', '<p>ÅÄÖ</p>')->LowerCase());
    }

    public function testSaveInto()
    {
        $obj = new DBFieldTest\TestDataObject();
        /** @var DBField $field */
        $field = $obj->dbObject('Title');
        $field->setValue('New Value');
        $field->saveInto($obj);

        $this->assertEquals('New Value', $obj->getField('Title'));
        $this->assertEquals(1, $field->saveIntoCalledCount);
        $this->assertEquals(1, $obj->setFieldCalledCount);
    }

    public function testSaveIntoNoRecursion()
    {
        $obj = new DBFieldTest\TestDataObject();
        /** @var DBField $field */
        $field = $obj->dbObject('Title');
        $value = new DBFieldTest\TestDbField('Title');
        $value->setValue('New Value');
        $field->setValue($value);
        $field->saveInto($obj);

        $this->assertEquals('New Value', $obj->getField('Title'));
        $this->assertEquals(1, $field->saveIntoCalledCount);
        $this->assertEquals(1, $obj->setFieldCalledCount);
    }

    public function testSaveIntoAsProperty()
    {
        $obj = new DBFieldTest\TestDataObject();
        /** @var DBField $field */
        $field = $obj->dbObject('Title');
        $field->setValue('New Value');
        $obj->Title = $field;

        $this->assertEquals('New Value', $obj->getField('Title'));
        $this->assertEquals(1, $field->saveIntoCalledCount);
        // Called twice because $obj->setField($field) => $field->saveInto() => $obj->setField('New Value')
        $this->assertEquals(2, $obj->setFieldCalledCount);
    }

    public function testSaveIntoNoRecursionAsProperty()
    {
        $obj = new DBFieldTest\TestDataObject();
        /** @var DBField $field */
        $field = $obj->dbObject('Title');
        $value = new DBFieldTest\TestDbField('Title');
        $value->setValue('New Value');
        $field->setValue($value);
        $obj->Title = $field;

        $this->assertEquals('New Value', $obj->getField('Title'));
        $this->assertEquals(1, $field->saveIntoCalledCount);
        // Called twice because $obj->setField($field) => $field->saveInto() => $obj->setField('New Value')
        $this->assertEquals(2, $obj->setFieldCalledCount);
    }

    public function testSaveIntoRespectsSetters()
    {
        $obj = new DBFieldTest\TestDataObject();
        /** @var DBField $field */
        $field = $obj->dbObject('MyTestField');
        $field->setValue('New Value');
        $obj->MyTestField = $field;

        $this->assertEquals('new value', $obj->getField('MyTestField'));
    }

    public function testDefaultValues(): void
    {
        $expectedBaseDefault = null;
        $expectedDefaults = [
            DBBoolean::class => false,
            DBDecimal::class => 0.0,
            DBInt::class => 0,
            DBFloat::class => 0.0,
        ];
        $count = 0;
        $classes = ClassInfo::subclassesFor(DBField::class);
        foreach ($classes as $class) {
            if (is_a($class, TestOnly::class, true)) {
                continue;
            }
            if (!str_starts_with($class, 'SilverStripe\ORM\FieldType')) {
                continue;
            }
            $reflector = new ReflectionClass($class);
            if ($reflector->isAbstract()) {
                continue;
            }
            $expected = $expectedBaseDefault;
            foreach ($expectedDefaults as $baseClass => $default) {
                if ($class === $baseClass || is_subclass_of($class, $baseClass)) {
                    $expected = $default;
                    break;
                }
            }
            $field = new $class('TestField');
            $this->assertSame($expected, $field->getValue(), $class);
            $count++;
        }
        // Assert that we have tested all classes e.g. namespace wasn't changed, no new classes were added
        // that haven't been tested
        $this->assertSame(29, $count);
    }

    public function testFieldValidatorConfig(): void
    {
        $expectedFieldValidators = [
            DBBigInt::class => [
                BigIntFieldValidator::class,
            ],
            DBBoolean::class => [
                BooleanFieldValidator::class,
            ],
            DBClassName::class => [
                StringFieldValidator::class,
                OptionFieldValidator::class,
            ],
            DBClassNameVarchar::class => [
                StringFieldValidator::class,
                OptionFieldValidator::class,
            ],
            DBCurrency::class => [
                DecimalFieldValidator::class,
            ],
            DBDate::class => [
                DateFieldValidator::class,
            ],
            DBDatetime::class => [
                DatetimeFieldValidator::class,
            ],
            DBDecimal::class => [
                DecimalFieldValidator::class,
            ],
            DBDouble::class => [],
            DBEmail::class => [
                StringFieldValidator::class,
                EmailFieldValidator::class,
            ],
            DBEnum::class => [
                StringFieldValidator::class,
                OptionFieldValidator::class,
            ],
            DBFloat::class => [],
            DBForeignKey::class => [
                IntFieldValidator::class,
            ],
            DBHTMLText::class => [
                StringFieldValidator::class,
            ],
            DBHTMLVarchar::class => [
                StringFieldValidator::class,
            ],
            DBInt::class => [
                IntFieldValidator::class,
            ],
            DBIp::class => [
                StringFieldValidator::class,
                IpFieldValidator::class,
            ],
            DBLocale::class => [
                StringFieldValidator::class,
                LocaleFieldValidator::class,
            ],
            DBMoney::class => [
                CompositeFieldValidator::class,
            ],
            DBMultiEnum::class => [
                MultiOptionFieldValidator::class,
            ],
            DBPercentage::class => [
                DecimalFieldValidator::class,
            ],
            DBPolymorphicForeignKey::class => [],
            DBPolymorphicRelationAwareForeignKey::class => [],
            DBPrimaryKey::class => [
                IntFieldValidator::class,
            ],
            DBText::class => [
                StringFieldValidator::class,
            ],
            DBTime::class => [
                TimeFieldValidator::class,
            ],
            DBUrl::class => [
                StringFieldValidator::class,
                UrlFieldValidator::class,
            ],
            DBVarchar::class => [
                StringFieldValidator::class,
            ],
            DBYear::class => [
                YearFieldValidator::class,
            ],
        ];
        $count = 0;
        $classes = ClassInfo::subclassesFor(DBField::class);
        foreach ($classes as $class) {
            if (is_a($class, TestOnly::class, true)) {
                continue;
            }
            if (!str_starts_with($class, 'SilverStripe\ORM\FieldType')) {
                continue;
            }
            $reflector = new ReflectionClass($class);
            if ($reflector->isAbstract()) {
                continue;
            }
            if (!array_key_exists($class, $expectedFieldValidators)) {
                throw new Exception("No field validator config found for $class");
            }
            $expected = $expectedFieldValidators[$class];
            $method = new ReflectionMethod($class, 'getFieldValidators');
            $method->setAccessible(true);
            $obj = new $class('MyField');
            $actual = array_map('get_class', $method->invoke($obj));
            $this->assertSame($expected, $actual, $class);
            $count++;
        }
        // Assert that we have tested all classes e.g. namespace wasn't changed, no new classes were added
        // that haven't been tested
        $this->assertSame(29, $count);
    }

    public function testSkipValidateIfNull()
    {
        $field = new DBInt('MyField');
        $field->setValue(null);
        // assert value isn't getting changed on setValue();
        $this->assertNull($field->getValue());
        // assert that field validators were not called
        $this->assertTrue($field->validate()->isValid());
        // assert that IntFieldValidator was applied to the field
        $method = new ReflectionMethod(DBInt::class, 'getFieldValidators');
        $method->setAccessible(true);
        $actual = array_map('get_class', $method->invoke($field));
        $this->assertSame([IntFieldValidator::class], $actual);
        // assert that IntFieldValidator considers null as invalid
        $validator = new IntFieldValidator('Test', null);
        $this->assertFalse($validator->validate()->isValid());
    }
}
