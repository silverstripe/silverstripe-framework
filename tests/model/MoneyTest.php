<?php
/**
 * Partially based on Zend_CurrencyTest.
 * 
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license	http://framework.zend.com/license/new-bsd	 New BSD License
 * @version	$Id: CurrencyTest.php 14644 2009-04-04 18:59:08Z thomas $
 */

/**
 * @package sapphire
 * @subpackage tests
 */
class MoneyTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/model/MoneyTest.yml';

	protected $extraDataObjects = array(
		'MoneyTest_DataObject',
	);
	
	function testMoneyFieldsReturnedAsObjects() {
		$obj = $this->objFromFixture('MoneyTest_DataObject', 'test1');
		$this->assertType('Money', $obj->MyMoney);
	}

	
	function testLoadFromFixture() {
		$obj = $this->objFromFixture('MoneyTest_DataObject', 'test1');
		
		$this->assertType('Money', $obj->MyMoney);
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
		$this->assertType('Money', $obj->MyMoney);
		$obj->MyMoney->setAmount(99);
		$changed = $obj->getChangedFields();
		$this->assertContains('MyMoney', array_keys($changed));
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
		$USD->setCurrency('USD');
		$USD->setLocale('en_US');
		
		$EGP = new Money();
		$EGP->setCurrency('EGP');
		$EGP->setLocale('ar_EG');

		$USD->setAmount(53292.18);
		$this->assertSame('$53,292.18', $USD->Nice());
		$USD->setAmount(53292.18);
		$this->assertSame('$٥٣,٢٩٢.١٨', $USD->Nice(array('script' => 'Arab' )));
		$USD->setAmount(53292.18);
		$this->assertSame('$ ٥٣.٢٩٢,١٨', $USD->Nice(array('script' => 'Arab', 'format' => 'de_AT')));
		$USD->setAmount(53292.18);
		$this->assertSame('$ 53.292,18', $USD->Nice(array('format' => 'de_AT')));

		$EGP->setAmount(53292.18);
		$this->assertSame('ج.م.‏ 53٬292٫18', $EGP->Nice());
		$EGP->setAmount(53292.18);
		$this->assertSame('ج.م.‏ ٥٣٬٢٩٢٫١٨', $EGP->Nice(array('script' => 'Arab' )));
		$EGP->setAmount(53292.18);
		$this->assertSame('ج.م.‏ ٥٣.٢٩٢,١٨', $EGP->Nice(array('script' =>'Arab', 'format' => 'de_AT')));
		$EGP->setAmount(53292.18);
		$this->assertSame('ج.م.‏ 53.292,18', $EGP->Nice(array('format' => 'de_AT')));

		$USD = new Money();
		$USD->setLocale('en_US');
		$USD->setAmount(53292.18);
		$this->assertSame('$53,292.18', $USD->Nice());
		/*
		try {
			$this->assertSame('$ 53,292.18', $USD->Nice('nocontent'));
			$this->fail("No currency expected");
		} catch (Exception $e) {
			$this->assertContains("has to be numeric", $e->getMessage());
		}
		*/

		$INR = new Money();
		$INR->setLocale('de_AT');
		$INR->setCurrency('INR');
		$INR->setAmount(1.2);
		$this->assertSame('Rs. 1,20', $INR->Nice());
		$INR->setAmount(1);
		$this->assertSame('Re. 1,00', $INR->Nice());
		$INR->setAmount(0);
		$this->assertSame('Rs. 0,00', $INR->Nice());
		$INR->setAmount(-3);
		$this->assertSame('-Rs. 3,00', $INR->Nice());
	}

	public function testGetSign() {
		$EGP = new Money();
		$EGP->setValue(array(
			'Currency' => 'EGP',
			'Amount' => 3.44
		));
		$EGP->setLocale('ar_EG');

		$this->assertSame('ج.م.‏', $EGP->getSymbol('EGP','ar_EG'));
		$this->assertSame('€',	$EGP->getSymbol('EUR','de_AT'));
		$this->assertSame('ج.م.‏', $EGP->getSymbol(null, 'ar_EG'));
		//$this->assertSame('€',	$EGP->getSymbol(null, 'de_AT'));
		$this->assertSame('ج.م.‏',	$EGP->getSymbol());

		try {
			$EGP->getSymbol('EGP', 'de_XX');
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

		$this->assertSame('جنيه مصرى', $m->getName('EGP','ar_EG'));
		$this->assertSame('Estnische Krone', $m->getName('EEK','de_AT'));
		//$this->assertSame('جنيه مصرى', $m->getName(null, 'ar_EG'));
		//$this->assertSame('Euro', $m->getName('de_AT'));
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
	
	function testHasValue() {
		$m1 = new Money();
		$this->assertFalse($m1->hasValue());
		
		$m2 = new Money();
		$m2->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 3.44
		));
		$this->assertTrue($m2->hasValue());
		
		$m3 = new Money();
		$m3->setValue(array(
			'Currency' => 'EUR',
			'Amount' => 0
		));
		$this->assertTrue($m3->hasValue());
	}

	function testLoadIntoDataObject() {
		$obj = new MoneyTest_DataObject();
		
		$this->assertType('Money', $obj->obj('MyMoney'));
		
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
?>