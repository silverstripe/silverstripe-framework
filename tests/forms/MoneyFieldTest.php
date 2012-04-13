<?php
/**
 * @package framework
 * @subpackage tests
 */
class MoneyFieldTest extends SapphireTest {
	
	protected $extraDataObjects = array(
		'MoneyFieldTest_Object',
		'MoneyFieldTest_CustomSetter_Object',
	);

	function testSaveInto() {
		$o = new MoneyFieldTest_Object();
		
		$m = new Money();
		$m->setAmount(1.23);
		$m->setCurrency('EUR');
		$f = new MoneyField('MyMoney', 'MyMoney', $m);
		
		$f->saveInto($o);
		$this->assertEquals($o->MyMoney->getAmount(), 1.23);
		$this->assertEquals($o->MyMoney->getCurrency(), 'EUR');
	}
	
	function testSetValueAsMoney() {
		$o = new MoneyFieldTest_Object();
		
		$f = new MoneyField('MyMoney', 'MyMoney');
		
		$m = new Money();
		$m->setAmount(1.23);
		$m->setCurrency('EUR');
		$f->setValue($m);
		
		$f->saveInto($o);
		$this->assertEquals($o->MyMoney->getAmount(), 1.23);
		$this->assertEquals($o->MyMoney->getCurrency(), 'EUR');
	}
	
	function testSetValueAsArray() {
		$o = new MoneyFieldTest_Object();
		
		$f = new MoneyField('MyMoney', 'MyMoney');
		
		$f->setValue(array('Currency'=>'EUR','Amount'=>1.23));
		
		$f->saveInto($o);
		$this->assertEquals($o->MyMoney->getAmount(), 1.23);
		$this->assertEquals($o->MyMoney->getCurrency(), 'EUR');
	}

	/**
	 * This UT tests if saveInto used customised getters/setters correctly.
	 * Saving values for CustomMoney shall go through the setCustomMoney_Test
	 * setter method and double the value. 
	 */
	function testSetValueViaSetter() {
		$o = new MoneyFieldTest_CustomSetter_Object();
		
		$f = new MoneyField('CustomMoney', 'Test Money Field');
		$f->setValue(array('Currency'=>'EUR','Amount'=>1.23));
		
		$f->saveInto($o);
		$this->assertEquals($o->MyMoney->getAmount(), (2 * 1.23) );
		$this->assertEquals($o->MyMoney->getCurrency(), 'EUR');
	}
}

class MoneyFieldTest_Object extends DataObject implements TestOnly {
	static $db = array(
		'MyMoney' => 'Money', 
	);
}

/**
 * Customised class, implementing custom getter and setter methods for
 * MyMoney.
 */
class MoneyFieldTest_CustomSetter_Object extends DataObject implements TestOnly {
	static $db = array(
		'MyMoney' => 'Money', 
	);
	
	function getCustomMoney() {
		return $this->MyMoney->getValue();
	}
	
	function setCustomMoney($value) {
		
		$newAmount = $value->getAmount() * 2;
		$this->MyMoney->setAmount($newAmount);

		$newAmount = $value->getAmount() * 2;
		$this->MyMoney->setCurrency($value->getCurrency());

	}
}
