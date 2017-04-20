<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBBigInt;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\ORM\FieldType\DBDouble;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\ORM\FieldType\DBTime;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for DBField objects.
 */
class DBFieldTest extends SapphireTest
{

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

        // @todo - Revisit Varchar to evaluate correct behaviour of nullifyEmpty

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
        $varcharField = DBVarchar::create("testfield", 50, array("nullifyEmpty"=>false));
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
        $textField = DBText::create("testfield", array("nullifyEmpty"=>false));
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

        $varcharField = new DBVarchar("testfield", 50, array('nullifyEmpty'=>false));
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

        $textField = new DBText("testfield", array('nullifyEmpty'=>false));
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
        $plainFields = array('Varchar', 'Text');
        $htmlFields = array('HTMLVarchar', 'HTMLText', 'HTMLFragment');
        $allFields = array_merge($plainFields, $htmlFields);

        $value = 'üåäöÜÅÄÖ';
        foreach ($allFields as $stringField) {
            $stringField = DBString::create_field($stringField, $value);
            for ($i = 1; $i < mb_strlen($value); $i++) {
                $expected = mb_substr($value, 0, $i) . '...';
                $this->assertEquals($expected, $stringField->LimitCharacters($i));
            }
        }

        $value = '<p>üåäö&amp;ÜÅÄÖ</p>';
        foreach ($htmlFields as $stringField) {
            $stringObj = DBString::create_field($stringField, $value);

            // Converted to plain text
            $this->assertEquals('üåäö&ÜÅÄ...', $stringObj->LimitCharacters(8));

            // But which will be safely cast in templates
            $this->assertEquals('üåäö&amp;ÜÅÄ...', $stringObj->obj('LimitCharacters', [8])->forTemplate());
        }

        $this->assertEquals('ÅÄÖ', DBText::create_field('Text', 'åäö')->UpperCase());
        $this->assertEquals('åäö', DBText::create_field('Text', 'ÅÄÖ')->LowerCase());
        $this->assertEquals('<P>ÅÄÖ</P>', DBHTMLText::create_field('HTMLFragment', '<p>åäö</p>')->UpperCase());
        $this->assertEquals('<p>åäö</p>', DBHTMLText::create_field('HTMLFragment', '<p>ÅÄÖ</p>')->LowerCase());
    }
}
