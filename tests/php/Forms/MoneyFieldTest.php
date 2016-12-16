<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\Tests\MoneyFieldTest\CustomSetter_Object;
use SilverStripe\Forms\Tests\MoneyFieldTest\TestObject;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\MoneyField;

class MoneyFieldTest extends SapphireTest
{

    protected $extraDataObjects = array(
        TestObject::class,
        CustomSetter_Object::class,
    );

    public function testSaveInto()
    {
        $o = new TestObject();

        $m = new DBMoney();
        $m->setAmount(123456.78);
        $m->setCurrency('EUR');
        $f = new MoneyField('MyMoney', 'MyMoney', $m);

        $f->saveInto($o);
        $this->assertEquals(123456.78, $o->MyMoney->getAmount());
        $this->assertEquals('EUR', $o->MyMoney->getCurrency());
    }

    public function testSetValueAsMoney()
    {
        $o = new TestObject();

        $f = new MoneyField('MyMoney', 'MyMoney');

        $m = new DBMoney();
        $m->setAmount(123456.78);
        $m->setCurrency('EUR');
        $f->setValue($m);

        $f->saveInto($o);
        $this->assertEquals(123456.78, $o->MyMoney->getAmount());
        $this->assertEquals('EUR', $o->MyMoney->getCurrency());
    }

    public function testSetValueAsArray()
    {
        $o = new TestObject();

        $f = new MoneyField('MyMoney', 'MyMoney');

        $f->setValue(array('Currency'=>'EUR','Amount'=>123456.78));

        $f->saveInto($o);
        $this->assertEquals(123456.78, $o->MyMoney->getAmount());
        $this->assertEquals('EUR', $o->MyMoney->getCurrency());
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
        $f->setValue(array('Currency'=>'EUR','Amount'=>123456.78));

        $f->saveInto($o);
        $this->assertEquals((2 * 123456.78), $o->MyMoney->getAmount());
        $this->assertEquals('EUR', $o->MyMoney->getCurrency());
    }
}
