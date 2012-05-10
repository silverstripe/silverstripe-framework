<?php
/**
 * Partially based on Zend_CurrencyTest.
 * 
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license	http://framework.zend.com/license/new-bsd	 New BSD License
 * @version	$Id: CurrencyTest.php 14644 2009-04-04 18:59:08Z thomas $
 */

/**
 * @package framework
 * @subpackage tests
 */
class MoneyTest extends SapphireTest {
	
	static $fixture_file = 'MoneyTest.yml';

	protected $extraDataObjects = array(
		'MoneyTest_DataObject',
	);
	
	function testMoneyFieldsReturnedAsObjects() {
		$obj = $this->objFromFixture('MoneyTest_DataObject', 'test1');
		$this->assertInstanceOf('Money', $obj->MyMoney);
	}

	
	function testLoadFromFixture() {
		$obj = $this->objFromFixture('MoneyTest_DataObject', 'test1');
		
		$this->assertInstanceOf('Money', $obj->MyMoney);
		$this->assertEquals($obj->MyMoney->getCurrency(), 'EUR');
		$this->assertEquals($obj->MyMoney->getAmount(), 1.23);
	}
	
	function testDataObjectChangedFields() {
		$obj = $this->objFromFixture('MoneyTest_DataObject', 'test1');
		
		// Without changes
		$curr = $obj->obj('MyMoney');
		$changed = $obj->getChangedFields();
		$this->assertNotContains('MyMoney', array_keys($changed));
		
		// With changes
		$this->assertInstanceOf('Money', $obj->MyMoney);
		$obj->MyMoney->setAmount(99);
		$changed = $obj->getChangedFields();
		$this->assertContains('MyMoney', array_keys($changed), 'Field is detected as changed');
		$this->assertEquals(2, $changed['MyMoney']['level'], 'Correct change level');
	}
	
	function testCanOverwriteSettersWithNull() {
		$obj = new MoneyTest_DataObject();

		$m1 = new Money();
		$m1->setAmount(987.65);
		$m1->setCurrency('USD');
		$obj->MyMoney = $m1;
		$obj->write();
		
		$m2 = new Money();
		$m2->setAmount(null);
		$m2->setCurrency(null);
		$obj->MyMoney = $m2;
		$obj->write();

		$moneyTest = DataObject::get_by_id('MoneyTest_DataObject',$obj->ID);
		$this->assertTrue($moneyTest instanceof MoneyTest_DataObject);
		$this->assertEquals('', $moneyTest->MyMoneyCurrency);
		$this->assertEquals(0.0000, $moneyTest->MyMoneyAmount);
	}
	
	/**
     * Write a Money object to the database, then re-read it to ensure it
     * is re-read properly.
     */
    function testGettingWrittenDataObject() {
	    $local = i18n::get_locale();
		i18n::set_locale('en_US');  //make sure that the $ amount is not prefixed by US$, as it would be in non-US locale

		$obj = new MoneyTest_DataObject();
		
		$m = new Money();
		$m->setAmount(987.65);
		$m->setCurrency('USD');
		$obj->MyMoney = $m;
		$this->assertEquals("$987.65", $obj->MyMoney->Nice(),
			"Money field not added to data object properly when read prior to first writing the record."
		);
		
		$objID = $obj->write();

		$moneyTest = DataObject::get_by_id('MoneyTest_DataObject',$objID);
		$this->assertTrue($moneyTest instanceof MoneyTest_DataObject);
		$this->assertEquals('USD', $moneyTest->MyMoneyCurrency);
		$this->assertEquals(987.65, $moneyTest->MyMoneyAmount);
		$this->assertEquals("$987.65", $moneyTest->MyMoney->Nice(),
			"Money field not added to data object properly when read."
		);

	    i18n::set_locale($local);
    }
	
	public function testToCurrency() {
		$USD = new Money();
		$USD->setLocale('en_US');
		$USD->setAmount(53292.18);
		$this->assertSame('$53,292.18', $USD->Nice());
		$this->assertSame('$ 53.292,18', $USD->Nice(array('format' => 'de_AT')));
	}

	public function testGetSign() {
		$SKR = new Money();
		$SKR->setValue(array(
			'Currency' => 'SKR',
			'Amount' => 3.44
		));

		$this->assertSame('€',	$SKR->getSymbol('EUR','de_AT'));
		$this->assertSame(null,	$SKR->getSymbol());

		try {
			$SKR->getSymbol('EGP', 'de_XX');
			$this->setExpectedException("Exception");
		} catch(Exception $e) {
		}
		
		$EUR = new Money();
		$EUR->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$EUR->setLocale('de_DE');
		$this->assertSame('€',	$EUR->getSymbol());
	}

	public function testGetName()
	{
		$m = new Money();
		$m->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$m->setLocale('ar_EG');

		$this->assertSame('Estnische Krone', $m->getName('EEK','de_AT'));
		$this->assertSame('يورو', $m->getName());

		try {
			$m->getName('EGP', 'xy_XY');
			$this->setExpectedException("Exception");
		} catch(Exception $e) {
		}
	}

	public function testGetShortName() {
		$m = new Money();
		$m->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$m->setLocale('de_AT');

		$this->assertSame('EUR', $m->getShortName('Euro',	 'de_AT'));
		$this->assertSame('USD', $m->getShortName('US-Dollar','de_AT'));
		//$this->assertSame('EUR', $m->getShortName(null, 'de_AT'));
		$this->assertSame('EUR', $m->getShortName());

		try {
			$m->getShortName('EUR', 'xy_ZT');
			$this->setExpectedException("Exception");
		} catch(Exception $e) {
		}
	}

	function testSetValueAsArray() {
		$m = new Money();
		$m->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$this->assertEquals(
			$m->getCurrency(),
			'EUR'
		);
		$this->assertEquals(
			$m->getAmount(),
			3.44
		);
	}
	
	function testSetValueAsMoney() {
		$m1 = new Money();
		$m1->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$m2 = new Money();
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
	
	function testExists() {
		$m1 = new Money();
		$this->assertFalse($m1->exists());
		
		$m2 = new Money();
		$m2->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$this->assertTrue($m2->exists());
		
		$m3 = new Money();
		$m3->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 0
		));
		$this->assertTrue($m3->exists());
	}

	function testLoadIntoDataObject() {
		$obj = new MoneyTest_DataObject();
		
		$this->assertInstanceOf('Money', $obj->obj('MyMoney'));
		
		$m = new Money();
		$m->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 1.23
		));
		$obj->MyMoney = $m;

		$this->assertEquals($obj->MyMoney->getCurrency(), 'EUR');
		$this->assertEquals($obj->MyMoney->getAmount(), 1.23);
	}
	
	function testWriteToDataObject() {
		$obj = new MoneyTest_DataObject();
		$m = new Money();
		$m->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 1.23
		));
		$obj->MyMoney = $m;
		$obj->write();
		
		$this->assertEquals(
			'EUR',
			DB::query(sprintf(
				'SELECT "MyMoneyCurrency" FROM "MoneyTest_DataObject" WHERE "ID" = %d',
				$obj->ID
			))->value()
		);
		$this->assertEquals(
			'1.23',
			DB::query(sprintf(
				'SELECT "MyMoneyAmount" FROM "MoneyTest_DataObject" WHERE "ID" = %d',
				$obj->ID
			))->value()
		);
	}
}

class MoneyTest_DataObject extends DataObject implements TestOnly {
	static $db = array(
		'MyMoney' => 'Money', 
		//'MyOtherMoney' => 'Money', 
	);

}
