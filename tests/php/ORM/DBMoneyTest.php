<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;

class DBMoneyTest extends SapphireTest
{

    protected static $fixture_file = 'DBMoneyTest.yml';

    protected static $extra_dataobjects = array(
        DBMoneyTest\TestObject::class,
        DBMoneyTest\TestObjectSubclass::class,
    );

    public function testMoneyFieldsReturnedAsObjects()
    {
        $obj = $this->objFromFixture(DBMoneyTest\TestObject::class, 'test1');
        $this->assertInstanceOf(DBMoney::class, $obj->MyMoney);
    }

    public function testLoadFromFixture()
    {
        $obj = $this->objFromFixture(DBMoneyTest\TestObject::class, 'test1');

        $this->assertInstanceOf(DBMoney::class, $obj->MyMoney);
        $this->assertEquals($obj->MyMoney->getCurrency(), 'EUR');
        $this->assertEquals($obj->MyMoney->getAmount(), 1.23);
    }

    public function testDataObjectChangedFields()
    {
        $obj = $this->objFromFixture(DBMoneyTest\TestObject::class, 'test1');

        // Without changes
        $curr = $obj->obj('MyMoney');
        $changed = $obj->getChangedFields();
        $this->assertNotContains('MyMoney', array_keys($changed));

        // With changes
        $this->assertInstanceOf(DBMoney::class, $obj->MyMoney);
        $obj->MyMoney->setAmount(99);
        $changed = $obj->getChangedFields();
        $this->assertContains('MyMoney', array_keys($changed), 'Field is detected as changed');
        $this->assertEquals(2, $changed['MyMoney']['level'], 'Correct change level');
    }

    public function testCanOverwriteSettersWithNull()
    {
        $obj = new DBMoneyTest\TestObject();

        $m1 = new DBMoney();
        $m1->setAmount(987.65);
        $m1->setCurrency('USD');
        $obj->MyMoney = $m1;
        $obj->write();

        $m2 = new DBMoney();
        $m2->setAmount(null);
        $m2->setCurrency(null);
        $obj->MyMoney = $m2;
        $obj->write();

        $moneyTest = DataObject::get_by_id(DBMoneyTest\TestObject::class, $obj->ID);
        $this->assertTrue($moneyTest instanceof DBMoneyTest\TestObject);
        $this->assertEquals('', $moneyTest->MyMoneyCurrency);
        $this->assertEquals(0.0000, $moneyTest->MyMoneyAmount);
    }

    public function testIsChanged()
    {
        $obj1 = $this->objFromFixture(DBMoneyTest\TestObject::class, 'test1');
        $this->assertFalse($obj1->isChanged());
        $this->assertFalse($obj1->isChanged('MyMoney'));

        // modify non-db field
        $m1 = new DBMoney();
        $m1->setAmount(500);
        $m1->setCurrency('NZD');
        $obj1->NonDBMoneyField = $m1;
        $this->assertFalse($obj1->isChanged()); // Because only detects DB fields
        $this->assertTrue($obj1->isChanged('NonDBMoneyField')); // Allow change detection to non-db fields explicitly named

        // Modify db field
        $obj2 = $this->objFromFixture(DBMoneyTest\TestObject::class, 'test2');
        $m2 = new DBMoney();
        $m2->setAmount(500);
        $m2->setCurrency('NZD');
        $obj2->MyMoney = $m2;
        $this->assertTrue($obj2->isChanged()); // Detects change to DB field
        $this->assertTrue($obj2->ischanged('MyMoney'));

        // Modify sub-fields
        $obj3 = $this->objFromFixture(DBMoneyTest\TestObject::class, 'test3');
        $obj3->MyMoneyCurrency = 'USD';
        $this->assertTrue($obj3->isChanged()); // Detects change to DB field
        $this->assertTrue($obj3->ischanged('MyMoneyCurrency'));
    }

    /**
     * Write a Money object to the database, then re-read it to ensure it
     * is re-read properly.
     */
    public function testGettingWrittenDataObject()
    {
        $local = i18n::get_locale();
        //make sure that the $ amount is not prefixed by US$, as it would be in non-US locale
        i18n::set_locale('en_US');

        $obj = new DBMoneyTest\TestObject();

        $m = new DBMoney();
        $m->setAmount(987.65);
        $m->setCurrency('USD');
        $obj->MyMoney = $m;
        $this->assertEquals(
            "$987.65",
            $obj->MyMoney->Nice(),
            "Money field not added to data object properly when read prior to first writing the record."
        );

        $objID = $obj->write();

        $moneyTest = DataObject::get_by_id(DBMoneyTest\TestObject::class, $objID);
        $this->assertTrue($moneyTest instanceof DBMoneyTest\TestObject);
        $this->assertEquals('USD', $moneyTest->MyMoneyCurrency);
        $this->assertEquals(987.65, $moneyTest->MyMoneyAmount);
        $this->assertEquals(
            "$987.65",
            $moneyTest->MyMoney->Nice(),
            "Money field not added to data object properly when read."
        );

        i18n::set_locale($local);
    }

    /**
     * Covers Nice() and getValue()
     */
    public function testToCurrency()
    {
        $USD = new DBMoney();
        $USD->setValue([
            'Currency' => 'USD',
            'Amount' => 53292.18,
        ]);
        $USD->setLocale('en_US');
        $this->assertSame('53292.18 USD', $USD->getValue());
        $this->assertSame('$53,292.18', $USD->Nice());

        // USD in de locale
        $USD->setLocale('de_DE');
        $this->assertSame($this->clean('53.292,18 $'), $this->clean($USD->Nice()));
    }

    public function testGetSymbol()
    {
        // Swedish kroner
        $SKR = new DBMoney();
        $SKR->setValue([
            'Currency' => 'SEK',
            'Amount' => 3.44
        ]);
        $SKR->setLocale('sv');
        $this->assertSame('kr', $SKR->getSymbol());

        // EU currency
        $EUR = new DBMoney();
        $EUR->setValue([
            'Currency' => 'EUR',
            'Amount' => 3.44
        ]);
        $EUR->setLocale('de_DE');
        $this->assertSame('€', $EUR->getSymbol());

        // Where locale doesn't match currency
        $USD = new DBMoney();
        $USD->setValue([
            'Currency' => 'USD',
            'Amount' => 3.44,
        ]);
        $USD->setLocale('de_DE');
        $this->assertSame('$', $USD->getSymbol());
    }

    public function testSetValueAsArray()
    {
        $m = new DBMoney();
        $m->setValue([
            'Currency' => 'EUR',
            'Amount' => 3.44
        ]);
        $this->assertEquals(
            $m->getCurrency(),
            'EUR'
        );
        $this->assertEquals(
            $m->getAmount(),
            3.44
        );
    }

    public function testSetValueAsMoney()
    {
        $m1 = new DBMoney();
        $m1->setValue([
            'Currency' => 'EUR',
            'Amount' => 3.44
        ]);
        $m2 = new DBMoney();
        $m2->setValue($m1);
        $this->assertEquals(
            $m2->getCurrency(),
            'EUR'
        );
        $this->assertEquals(
            $m2->getAmount(),
            3.44
        );
    }

    public function testExists()
    {
        $m1 = new DBMoney();
        $this->assertFalse($m1->exists());

        $m2 = new DBMoney();
        $m2->setValue([
            'Currency' => 'EUR',
            'Amount' => 3.44
        ]);
        $this->assertTrue($m2->exists());

        $m3 = new DBMoney();
        $m3->setValue([
            'Currency' => 'EUR',
            'Amount' => 0
        ]);
        $this->assertTrue($m3->exists());

        $m4 = new DBMoney();
        $m4->setValue([
            'Currency' => 'EUR',
            'Amount' => null,
        ]);
        $this->assertFalse($m4->exists());
    }

    public function testLoadIntoDataObject()
    {
        $obj = new DBMoneyTest\TestObject();

        $this->assertInstanceOf(DBMoney::class, $obj->obj('MyMoney'));

        $m = new DBMoney();
        $m->setValue([
            'Currency' => 'EUR',
            'Amount' => 1.23
        ]);
        $obj->MyMoney = $m;

        $this->assertEquals($obj->MyMoney->getCurrency(), 'EUR');
        $this->assertEquals($obj->MyMoney->getAmount(), 1.23);
    }

    public function testWriteToDataObject()
    {
        $obj = new DBMoneyTest\TestObject();
        $m = new DBMoney();
        $m->setValue([
            'Currency' => 'EUR',
            'Amount' => 1.23
        ]);
        $obj->MyMoney = $m;
        $obj->write();

        $this->assertEquals(
            'EUR',
            DB::query(
                sprintf(
                    'SELECT "MyMoneyCurrency" FROM "MoneyTest_DataObject" WHERE "ID" = %d',
                    $obj->ID
                )
            )->value()
        );
        $this->assertEquals(
            '1.23',
            DB::query(
                sprintf(
                    'SELECT "MyMoneyAmount" FROM "MoneyTest_DataObject" WHERE "ID" = %d',
                    $obj->ID
                )
            )->value()
        );
    }

    public function testMoneyLazyLoading()
    {
        // Get the object, ensuring that MyOtherMoney will be lazy loaded
        $id = $this->idFromFixture(DBMoneyTest\TestObjectSubclass::class, 'test2');
        $obj = DBMoneyTest\TestObject::get()->byID($id);

        $this->assertEquals('£2.46', $obj->obj('MyOtherMoney')->Nice());
    }

    public function testHasAmount()
    {
        $obj = new DBMoneyTest\TestObject();
        $m = new DBMoney();
        $obj->MyMoney = $m;

        $m->setValue(array('Amount' => 1));
        $this->assertTrue($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 1.00));
        $this->assertTrue($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 1.01));
        $this->assertTrue($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 0.99));
        $this->assertTrue($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 0.01));
        $this->assertTrue($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 0));
        $this->assertFalse($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 0.0));
        $this->assertFalse($obj->MyMoney->hasAmount());

        $m->setValue(array('Amount' => 0.00));
        $this->assertFalse($obj->MyMoney->hasAmount());
    }


    /**
     * In some cases and locales, validation expects non-breaking spaces.
     *
     * Duplicates non-public NumericField::clean method
     *
     * @param  string $input
     * @return string The input value, with all spaces replaced with non-breaking spaces
     */
    protected function clean($input)
    {
        $nbsp = html_entity_decode('&nbsp;', null, 'UTF-8');
        return str_replace(' ', $nbsp, trim($input));
    }
}
