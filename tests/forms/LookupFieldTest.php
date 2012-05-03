<?php
/**
 * @package framework
 * @subpackage tests
 */

class LookupFieldTest extends SapphireTest {
	
	static $fixture_file = 'LookupFieldTest.yml';

	function testNullValueWithNumericArraySource() {
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(null);
		$this->assertEquals(
			'<span class="readonly" id="test"><i>(none)</i></span><input type="hidden" name="test" value="" />', 
			$f->Field()
		);
	}

	function testStringValueWithNumericArraySource() {
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(1);
		$this->assertEquals(
			'<span class="readonly" id="test">one</span><input type="hidden" name="test" value="1" />', 
			$f->Field()
		);
	}
	
	function testUnknownStringValueWithNumericArraySource() {
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue('<ins>w00t</ins>');
		$f->dontEscape = true; // simulates CMSMain->compareversions()
		$this->assertEquals(
			'<span class="readonly" id="test"><ins>w00t</ins></span><input type="hidden" name="test" value="" />', 
			$f->Field()
		);
	}

	function testArrayValueWithAssociativeArraySource() {
		// Array values (= multiple selections) might be set e.g. from ListboxField
		$source = array('one' => 'one val', 'two' => 'two val', 'three' => 'three val');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(array('one','two'));
		$this->assertEquals(
			'<span class="readonly" id="test">one val, two val</span><input type="hidden" name="test" value="one, two" />', 
			$f->Field()
		);
	}
	
	function testArrayValueWithNumericArraySource() {
		// Array values (= multiple selections) might be set e.g. from ListboxField
		$source = array(1 => 'one', 2 => 'two', 3 => 'three');
		$f = new LookupField('test', 'test', $source);
		$f->setValue(array(1,2));
		$this->assertEquals(
			'<span class="readonly" id="test">one, two</span><input type="hidden" name="test" value="1, 2" />', 
			$f->Field()
		);
	}
	
	function testArrayValueWithSqlMapSource() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member2 = $this->objFromFixture('Member', 'member2');
		$member3 = $this->objFromFixture('Member', 'member3');
		
		$source = DataObject::get('Member');
		$f = new LookupField('test', 'test', $source->map());
		$f->setValue(array($member1->ID, $member2->ID));
		$this->assertEquals(
			sprintf(
				'<span class="readonly" id="test">member1, member2</span><input type="hidden" name="test" value="%s, %s" />', 
				$member1->ID,
				$member2->ID
			),
			$f->Field()
		);
	}
	
}
