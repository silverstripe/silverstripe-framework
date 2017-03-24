<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tests\MoneyFieldTest\CustomSetter_Object;
use SilverStripe\Forms\Tests\MoneyFieldTest\TestObject;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\MoneyField;

class MoneyFieldTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        TestObject::class,
        CustomSetter_Object::class,
    );

    public function testSaveInto()
    {
        $testObject = new TestObject();

        $money = new DBMoney();
        $money->setAmount(123456.78);
        $money->setCurrency('EUR');
        $field = new MoneyField('MyMoney', 'MyMoney', $money);

        $field->saveInto($testObject);
        $this->assertEquals(123456.78, $testObject->MyMoney->getAmount());
        $this->assertEquals('EUR', $testObject->MyMoney->getCurrency());
    }

    public function testSetValueAsMoney()
    {
        $testObject = new TestObject();

        $field = new MoneyField('MyMoney', 'MyMoney');
        $field->setLocale('en_NZ');

        $money = new DBMoney();
        $money->setAmount(123456.78);
        $money->setCurrency('EUR');
        $field->setValue($money);

        $field->saveInto($testObject);
        $this->assertEquals(123456.78, $testObject->MyMoney->getAmount());
        $this->assertEquals('EUR', $testObject->MyMoney->getCurrency());
        $this->assertEquals('123456.78 EUR', $field->dataValue());
        $this->assertEquals('€123,456.78', $field->Value());
    }

    public function testSetValueAsArray()
    {
        $testObject = new TestObject();
        $field = new MoneyField('MyMoney', 'MyMoney');
        $field->setSubmittedValue([
            'Currency' => 'EUR',
            'Amount' => 123456.78
        ]);

        $field->saveInto($testObject);
        $this->assertEquals(123456.78, $testObject->MyMoney->getAmount());
        $this->assertEquals('EUR', $testObject->MyMoney->getCurrency());
    }

    public function testSetValueAsString()
    {
        $testObject = new TestObject();
        $field = new MoneyField('MyMoney');
        $field->setLocale('en_NZ');
        $field->setValue('1.01 usd');
        $field->saveInto($testObject);
        $this->assertEquals(1.01, $testObject->MyMoney->getAmount());
        $this->assertEquals('USD', $testObject->MyMoney->getCurrency());
        $this->assertEquals('1.01 USD', $field->dataValue());
        $this->assertEquals('US$1.01', $field->Value());

        $testObject = new TestObject();
        $field = new MoneyField('MyMoney');
        $field->setLocale('en_NZ');
        $field->setValue('1.01');
        $field->saveInto($testObject);
        $this->assertEquals(1.01, $testObject->MyMoney->getAmount());
        $this->assertNull($testObject->MyMoney->getCurrency());
        $this->assertEquals('1.01', $field->dataValue());
        $this->assertEquals('$1.01', $field->Value());
    }

    /**
     * This UT tests if saveInto used customised getters/setters correctly.
     * Saving values for CustomMoney shall go through the setCustomMoney_Test
     * setter method and double the value.
     */
    public function testSetValueViaSetter()
    {
        $o = new CustomSetter_Object();

        $f = new MoneyField('CustomMoney', 'Test Money Field');
        $f->setSubmittedValue([
            'Currency'=>'EUR',
            'Amount'=>123456.78
        ]);

        $f->saveInto($o);
        $this->assertEquals((2 * 123456.78), $o->MyMoney->getAmount());
        $this->assertEquals('EUR', $o->MyMoney->getCurrency());
    }

    public function testValidation()
    {
        $field = new MoneyField('Money');
        $field->setAllowedCurrencies(['NZD', 'USD']);

        // Valid currency
        $validator = new RequiredFields();
        $field->setSubmittedValue([
            'Currency' => 'NZD',
            'Amount' => 123
        ]);
        $this->assertTrue($field->validate($validator));

        // Invalid currency
        $field->setSubmittedValue([
            'Currency' => 'EUR',
            'Amount' => 123
        ]);
        $this->assertFalse($field->validate($validator));
    }
}
