<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class MoneyFieldTest extends SapphireTest {
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
}

class MoneyFieldTest_Object extends DataObject implements TestOnly {
	static $db = array(
		'MyMoney' => 'Money', 
	);
}
?>