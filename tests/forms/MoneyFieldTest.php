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

	public function testSaveInto() {
		$o = new MoneyFieldTest_Object();

		$m = new Money();
		$m->setAmount(123456.78);
		$m->setCurrency('EUR');
		$f = new MoneyField('MyMoney', 'MyMoney', $m);

		$f->saveInto($o);
		$this->assertEquals(123456.78, $o->MyMoney->getAmount());
		$this->assertEquals('EUR', $o->MyMoney->getCurrency());
	}

	public function testSetValueAsMoney() {
		$o = new MoneyFieldTest_Object();

		$f = new MoneyField('MyMoney', 'MyMoney');

		$m = new Money();
		$m->setAmount(123456.78);
		$m->setCurrency('EUR');
		$f->setValue($m);

		$f->saveInto($o);
		$this->assertEquals(123456.78, $o->MyMoney->getAmount());
		$this->assertEquals('EUR', $o->MyMoney->getCurrency());
	}

	public function testSetValueAsArray() {
		$o = new MoneyFieldTest_Object();

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
	public function testSetValueViaSetter() {
		$o = new MoneyFieldTest_CustomSetter_Object();

		$f = new MoneyField('CustomMoney', 'Test Money Field');
		$f->setValue(array('Currency'=>'EUR','Amount'=>123456.78));

		$f->saveInto($o);
		$this->assertEquals((2 * 123456.78), $o->MyMoney->getAmount());
		$this->assertEquals('EUR', $o->MyMoney->getCurrency());
	}

}

class MoneyFieldTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		'MyMoney' => 'Money',
	);
}

/**
 * Customised class, implementing custom getter and setter methods for
 * MyMoney.
 */
class MoneyFieldTest_CustomSetter_Object extends DataObject implements TestOnly {
	private static $db = array(
		'MyMoney' => 'Money',
	);

	public function getCustomMoney() {
		return $this->MyMoney->getValue();
	}

	public function setCustomMoney($value) {

		$newAmount = $value->getAmount() * 2;
		$this->MyMoney->setAmount($newAmount);

		$newAmount = $value->getAmount() * 2;
		$this->MyMoney->setCurrency($value->getCurrency());

	}
}
