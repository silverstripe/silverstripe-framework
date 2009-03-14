<?php
/**
 * Tests various static getter and setter methods on {@link Object}
 *
 * @package sapphire
 * @subpackage tests
 */
class ObjectStaticTest extends SapphireTest {
	
	/**
	 * Tests {@link Object::get_static()}
	 */
	public function testGetStatic() {
		$this->assertEquals(Object::get_static('ObjectStaticTest_First',  'first'), array('test_1'));
		$this->assertEquals(Object::get_static('ObjectStaticTest_Second', 'first'), array('test_2'));
		$this->assertEquals(Object::get_static('ObjectStaticTest_Third',  'first'), array('test_3'));
		
		Object::addStaticVars('ObjectStaticTest_First',  array('first' => array('test_1_2')));
		Object::addStaticVars('ObjectStaticTest_Third',  array('first' => array('test_3_2')));
		Object::addStaticVars('ObjectStaticTest_Fourth', array('first' => array('test_4')));
		
		$this->assertEquals(Object::get_static('ObjectStaticTest_First',  'first', true), array('test_1_2', 'test_1'));
		$this->assertEquals(Object::get_static('ObjectStaticTest_Second', 'first', true), array('test_1_2', 'test_2'));
		$this->assertEquals(Object::get_static('ObjectStaticTest_Third',  'first', true), array('test_1_2', 'test_3_2', 'test_3'));
	}
	
	/**
	 * Test {@link Object::addStaticVar()} correctly replaces static vars
	 */
	public function testAddStaticReplace() {
		Object::addStaticVars('ObjectStaticTest_Fourth', array('second' => array('test_4')), true);
		Object::addStaticVars('ObjectStaticTest_Third',  array('second' => array('test_3_2')));
		
		$this->assertEquals(Object::get_static('ObjectStaticTest_Fourth', 'second', true), array('test_4'));
		$this->assertEquals(Object::get_static('ObjectStaticTest_Third',  'second', true), array('test_3_2', 'test_3'));
		
		Object::addStaticVars('ObjectStaticTest_Third',  array('second' => array('test_3_2')), true);
		$this->assertEquals(Object::get_static('ObjectStaticTest_Third', 'second', true), array('test_3_2'));
		
		Object::add_static_var('ObjectStaticTest_Third', 'fourth', array('test_3_2'));
		$this->assertEquals(Object::get_static('ObjectStaticTest_Fourth', 'fourth', true), array('test_3_2', 'test_4'));
		
		Object::add_static_var('ObjectStaticTest_Third', 'fourth', array('test_3_2'), true);
		$this->assertEquals(Object::get_static('ObjectStaticTest_Fourth', 'fourth', true), array('test_4', 'test_3_2'));
	}
	
	/**
	 * Tests {@link Object::uninherited_static()}
	 */
	public function testUninherited() {
		$this->assertEquals(Object::uninherited_static('ObjectStaticTest_First',  'third', true), 'test_1');
		$this->assertEquals(Object::uninherited_static('ObjectStaticTest_Fourth', 'third', true), null);
	}
	
}

/**#@+
 * @ignore
 */
class ObjectStaticTest_First extends Object {
	public static $first  = array('test_1');
	public static $second = array('test_1');
	public static $third  = 'test_1';
}

class ObjectStaticTest_Second extends ObjectStaticTest_First {
	public static $first = array('test_2');
}

class ObjectStaticTest_Third extends ObjectStaticTest_Second {
	public static $first  = array('test_3');
	public static $second = array('test_3');
	public static $fourth = array('test_3');
}

class ObjectStaticTest_Fourth extends ObjectStaticTest_Third {
	public static $fourth = array('test_4');
}
/**#@-*/
