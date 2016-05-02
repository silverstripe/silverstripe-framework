<?php

/**
 * @package framework
 * @subpackage tests
 */

class LookupFieldTest extends SapphireTest {

	protected static $fixture_file = 'LookupFieldTest.yml';

	public function testNullValueWithNumericArraySource() {
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(null);

		$this->assertEquals(
			'<span class="readonly" id="test"><i>(none)</i></span><input type="hidden" name="test" value="" />',
			$f->Field()->getValue()
		);
	}

	public function testStringValueWithNumericArraySource() {
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(1);
		$this->assertEquals(
			'<span class="readonly" id="test">one</span><input type="hidden" name="test" value="1" />',
			$f->Field()->getValue()
		);
	}

	public function testUnknownStringValueWithNumericArraySource() {
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue('<ins>w00t</ins>');
		$f->dontEscape = true; // simulates CMSMain->compareversions()
		$this->assertEquals(
			'<span class="readonly" id="test"><ins>w00t</ins></span><input type="hidden" name="test" value="" />',
			$f->Field()->getValue()
		);
	}

	public function testArrayValueWithAssociativeArraySource() {
		// Array values (= multiple selections) might be set e.g. from ListboxField
		$source = array('one' => 'one val', 'two' => 'two val', 'three' => 'three val');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(array('one','two'));
		$this->assertEquals('<span class="readonly" id="test">one val, two val</span>'
			. '<input type="hidden" name="test" value="one, two" />',
			$f->Field()->getValue()
		);
	}

	public function testArrayValueWithNumericArraySource() {
		// Array values (= multiple selections) might be set e.g. from ListboxField
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(array(1,2));
		$this->assertEquals(
			'<span class="readonly" id="test">one, two</span><input type="hidden" name="test" value="1, 2" />',
			$f->Field()->getValue()
		);
	}

	public function testArrayValueWithSqlMapSource() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member2 = $this->objFromFixture('Member', 'member2');
		$member3 = $this->objFromFixture('Member', 'member3');

		$source = DataObject::get('Member');
		$f = new LookupField('test', 'test', $source->map('ID', 'FirstName'));
		$f->setValue(array($member1->ID, $member2->ID));

		$this->assertEquals(
			sprintf(
				'<span class="readonly" id="test">member1, member2</span>'
					. '<input type="hidden" name="test" value="%s, %s" />',
				$member1->ID,
				$member2->ID
			),
			$f->Field()->getValue()
		);
	}

	public function testWithMultiDimensionalSource() {
		$choices = array(
			"Non-vegetarian" => array(
				0 => 'Carnivore',
			),
			"Vegetarian" => array(
				3 => 'Carrots',
			),
			"Other" => array(
				9 => 'Vegan'
			)
		);

		$f = new LookupField('test', 'test', $choices);
		$f->setValue(3);

		$this->assertEquals(
			'<span class="readonly" id="test">Carrots</span><input type="hidden" name="test" value="3" />',
			$f->Field()->getValue()
		);

		$f->setValue(array(
			3, 9
		));

		$this->assertEquals(
			'<span class="readonly" id="test">Carrots, Vegan</span><input type="hidden" name="test" value="3, 9" />',
			$f->Field()->getValue()
		);
	}
}
