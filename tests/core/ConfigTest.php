<?php

class ConfigTest_DefinesFoo extends Object implements TestOnly {
	protected static $foo = 1;
}

class ConfigTest_DefinesBar extends ConfigTest_DefinesFoo {
	public static $bar = 2;
}

class ConfigTest_DefinesFooAndBar extends ConfigTest_DefinesFoo {
	protected static $foo = 3;
	public static $bar = 3;
}

class ConfigTest_DefinesFooDoesntExtendObject implements TestOnly {
	protected static $foo = 4;
}

class ConfigStaticTest_First extends Config implements TestOnly {
	/** @config */
	private static $first  = array('test_1');
	/** @config */
	private static $second = array('test_1');
	/** @config */
	private static $third  = 'test_1';

	/** @config */
	private static $bool = true;
	/** @config */
	private static $int = 42;
	/** @config */
	private static $string = 'value';
	/** @config */
	private static $nullable = 'value';

	/** @config */
	private static $default_false = false;
	/** @config */
	private static $default_null = null;
	/** @config */
	private static $default_zero = 0;
	public static $default_emtpy_string = '';
}

class ConfigStaticTest_Second extends ConfigStaticTest_First {
	private static $first = array('test_2');
}

class ConfigStaticTest_Third extends ConfigStaticTest_Second {
	private static $first  = array('test_3');
	private static $second = array('test_3');
	public static $fourth = array('test_3');
}

class ConfigStaticTest_Fourth extends ConfigStaticTest_Third {
	public static $fourth = array('test_4');
}

class ConfigStaticTest_Combined1 extends Config implements TestOnly {
	/** @config */
	private static $first  = array('test_1');
	/** @config */
	private static $second = array('test_1');
}

class ConfigStaticTest_Combined2 extends ConfigStaticTest_Combined1 {
	private static $first  = array('test_2');
	private static $second = null;
}

class ConfigStaticTest_Combined3 extends ConfigStaticTest_Combined2 {
	private static $first  = array('test_3');
	private static $second = array('test_3');
}

class ConfigTest_TestNest extends Object implements TestOnly {
	/** @config */
	private static $foo = 3;
	/** @config */
	private static $bar = 5;
}

class ConfigTest extends SapphireTest {
	
	protected $depSettings = null;
	
	public function setUp() {
		parent::setUp();
		$this->depSettings = Deprecation::dump_settings();
		Deprecation::set_enabled(false);
	}
	
	public function tearDown() {
		Deprecation::restore_settings($this->depSettings);
		parent::tearDown();
	}

	public function testNest() {

		// Check basic config
		$this->assertEquals(3, Config::inst()->get('ConfigTest_TestNest', 'foo'));
		$this->assertEquals(5, Config::inst()->get('ConfigTest_TestNest', 'bar'));

		// Test nest copies data
		Config::nest();
		$this->assertEquals(3, Config::inst()->get('ConfigTest_TestNest', 'foo'));
		$this->assertEquals(5, Config::inst()->get('ConfigTest_TestNest', 'bar'));

		// Test nested data can be updated
		Config::inst()->update('ConfigTest_TestNest', 'foo', 4);
		$this->assertEquals(4, Config::inst()->get('ConfigTest_TestNest', 'foo'));
		$this->assertEquals(5, Config::inst()->get('ConfigTest_TestNest', 'bar'));

		// Test unnest restores data
		Config::unnest();
		$this->assertEquals(3, Config::inst()->get('ConfigTest_TestNest', 'foo'));
		$this->assertEquals(5, Config::inst()->get('ConfigTest_TestNest', 'bar'));
	}

	public function testUpdateStatic() {
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_First', 'first', Config::FIRST_SET),
			array('test_1'));
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Second', 'first', Config::FIRST_SET),
			array('test_2'));
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Third', 'first', Config::FIRST_SET),
			array('test_3'));

		Config::inst()->update('ConfigStaticTest_First', 'first', array('test_1_2'));
		Config::inst()->update('ConfigStaticTest_Third', 'first', array('test_3_2'));
		Config::inst()->update('ConfigStaticTest_Fourth', 'first', array('test_4'));

		$this->assertEquals(Config::inst()->get('ConfigStaticTest_First', 'first', Config::FIRST_SET),
			array('test_1_2', 'test_1'));

		Config::inst()->update('ConfigStaticTest_Fourth', 'second', array('test_4'));
		Config::inst()->update('ConfigStaticTest_Third', 'second', array('test_3_2'));

		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Fourth', 'second', Config::FIRST_SET),
			array('test_4'));
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Third', 'second', Config::FIRST_SET),
			array('test_3_2', 'test_3'));

		Config::inst()->remove('ConfigStaticTest_Third', 'second');
		$this->assertEquals(array(), Config::inst()->get('ConfigStaticTest_Third', 'second'));
		Config::inst()->update('ConfigStaticTest_Third', 'second', array('test_3_2'));
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Third', 'second', Config::FIRST_SET),
			array('test_3_2'));
	}

	public function testUpdateWithFalsyValues() {
		// Booleans
		$this->assertTrue(Config::inst()->get('ConfigStaticTest_First', 'bool'));
		Config::inst()->update('ConfigStaticTest_First', 'bool', false);
		$this->assertFalse(Config::inst()->get('ConfigStaticTest_First', 'bool'));
		Config::inst()->update('ConfigStaticTest_First', 'bool', true);
		$this->assertTrue(Config::inst()->get('ConfigStaticTest_First', 'bool'));

		// Integers
		$this->assertEquals(42, Config::inst()->get('ConfigStaticTest_First', 'int'));
		Config::inst()->update('ConfigStaticTest_First', 'int', 0);
		$this->assertEquals(0, Config::inst()->get('ConfigStaticTest_First', 'int'));
		Config::inst()->update('ConfigStaticTest_First', 'int', 42);
		$this->assertEquals(42, Config::inst()->get('ConfigStaticTest_First', 'int'));

		// Strings
		$this->assertEquals('value', Config::inst()->get('ConfigStaticTest_First', 'string'));
		Config::inst()->update('ConfigStaticTest_First', 'string', '');
		$this->assertEquals('', Config::inst()->get('ConfigStaticTest_First', 'string'));
		Config::inst()->update('ConfigStaticTest_First', 'string', 'value');
		$this->assertEquals('value', Config::inst()->get('ConfigStaticTest_First', 'string'));

		// Nulls
		$this->assertEquals('value', Config::inst()->get('ConfigStaticTest_First', 'nullable'));
		Config::inst()->update('ConfigStaticTest_First', 'nullable', null);
		$this->assertNull(Config::inst()->get('ConfigStaticTest_First', 'nullable'));
		Config::inst()->update('ConfigStaticTest_First', 'nullable', 'value');
		$this->assertEquals('value', Config::inst()->get('ConfigStaticTest_First', 'nullable'));
	}

	public function testSetsFalsyDefaults() {
		$this->assertFalse(Config::inst()->get('ConfigStaticTest_First', 'default_false'));
		// Technically the same as an undefined config key
		$this->assertNull(Config::inst()->get('ConfigStaticTest_First', 'default_null'));
		$this->assertEquals(0, Config::inst()->get('ConfigStaticTest_First', 'default_zero'));
		$this->assertEquals('', Config::inst()->get('ConfigStaticTest_First', 'default_empty_string'));
	}

	public function testUninheritedStatic() {
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_First',  'third', Config::UNINHERITED), 'test_1');
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Fourth', 'third', Config::UNINHERITED), null);

		Config::inst()->update('ConfigStaticTest_First', 'first', array('test_1b'));
		Config::inst()->update('ConfigStaticTest_Second', 'first', array('test_2b'));

		// Check that it can be applied to parent and subclasses, and queried directly
		$this->assertContains('test_1b',
			Config::inst()->get('ConfigStaticTest_First', 'first', Config::UNINHERITED));
		$this->assertContains('test_2b',
			Config::inst()->get('ConfigStaticTest_Second', 'first', Config::UNINHERITED));

		// But it won't affect subclasses - this is *uninherited* static
		$this->assertNotContains('test_2b',
			Config::inst()->get('ConfigStaticTest_Third', 'first', Config::UNINHERITED));
		$this->assertNull(Config::inst()->get('ConfigStaticTest_Fourth', 'first', Config::UNINHERITED));

		// Subclasses that don't have the static explicitly defined should allow definition, also
		// This also checks that set can be called after the first uninherited get()
		// call (which can be buggy due to caching)
		Config::inst()->update('ConfigStaticTest_Fourth', 'first', array('test_4b'));
		$this->assertContains('test_4b', Config::inst()->get('ConfigStaticTest_Fourth', 'first', Config::UNINHERITED));
	}

	public function testCombinedStatic() {
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Combined3', 'first'),
			array('test_3', 'test_2', 'test_1'));

		// test that null values are ignored, but values on either side are still merged
		$this->assertEquals(Config::inst()->get('ConfigStaticTest_Combined3', 'second'),
			array('test_3', 'test_1'));
	}

	public function testMerges() {
		$result = array('A' => 1, 'B' => 2, 'C' => 3);
		Config::merge_array_low_into_high($result, array('C' => 4, 'D' => 5));
		$this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => 3, 'D' => 5));

		$result = array('A' => 1, 'B' => 2, 'C' => 3);
		Config::merge_array_high_into_low($result, array('C' => 4, 'D' => 5));
		$this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => 4, 'D' => 5));

		$result = array('A' => 1, 'B' => 2, 'C' => array(1, 2, 3));
		Config::merge_array_low_into_high($result, array('C' => array(4, 5, 6), 'D' => 5));
		$this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => array(1, 2, 3, 4, 5, 6), 'D' => 5));

		$result = array('A' => 1, 'B' => 2, 'C' => array(1, 2, 3));
		Config::merge_array_high_into_low($result, array('C' => array(4, 5, 6), 'D' => 5));
		$this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => array(4, 5, 6, 1, 2, 3), 'D' => 5));

		$result = array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2), 'D' => 3);
		Config::merge_array_low_into_high($result, array('C' => array('Bar' => 3, 'Baz' => 4)));
		$this->assertEquals($result,
			array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2, 'Baz' => 4), 'D' => 3));

		$result = array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2), 'D' => 3);
		Config::merge_array_high_into_low($result, array('C' => array('Bar' => 3, 'Baz' => 4)));
		$this->assertEquals($result,
			array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 3, 'Baz' => 4), 'D' => 3));
	}

	public function testStaticLookup() {
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFoo', 'foo'), 1);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFoo', 'bar'), null);

		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesBar', 'foo'), null);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesBar', 'bar'), 2);

		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooAndBar', 'foo'), 3);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooAndBar', 'bar'), 3);

		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooDoesntExtendObject', 'foo'), 4);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooDoesntExtendObject', 'bar'), null);
	}

	public function testFragmentOrder() {
		$this->markTestIncomplete();
	}

	public function testCacheCleaning() {
		$cache = new ConfigTest_Config_MemCache();

		for ($i = 0; $i < 1000; $i++) $cache->set($i, $i);
		$this->assertEquals(1000, count($cache->cache));

		$cache->clean();
		$this->assertEquals(0, count($cache->cache), 'Clean clears all items');
		$this->assertFalse($cache->get(1), 'Clean clears all items');

		$cache->set(1, 1, array('Foo'));
		$this->assertEquals(1, count($cache->cache));
		$this->assertEquals(1, count($cache->tags));

		$cache->clean('Foo');
		$this->assertEquals(0, count($cache->tags), 'Clean items with matching tag');
		$this->assertFalse($cache->get(1), 'Clean items with matching tag');

		$cache->set(1, 1, array('Foo', 'Bar'));
		$this->assertEquals(2, count($cache->tags));
		$this->assertEquals(1, count($cache->cache));

		$cache->clean('Bar');
		$this->assertEquals(1, count($cache->tags));
		$this->assertEquals(0, count($cache->cache), 'Clean items with any single matching tag');
		$this->assertFalse($cache->get(1), 'Clean items with any single matching tag');
	}

	public function testLRUDiscarding() {
		$cache = new ConfigTest_Config_LRU();
		for ($i = 0; $i < Config_LRU::SIZE*2; $i++) $cache->set($i, $i);
		$this->assertEquals(
			Config_LRU::SIZE, count($cache->indexing),
			'Homogenous usage gives exact discarding'
		);
		$cache = new ConfigTest_Config_LRU();
		for ($i = 0; $i < Config_LRU::SIZE; $i++) $cache->set($i, $i);
		for ($i = 0; $i < Config_LRU::SIZE; $i++) $cache->set(-1, -1);
		$this->assertLessThan(
			Config_LRU::SIZE, count($cache->indexing),
			'Heterogenous usage gives sufficient discarding'
		);
	}

	public function testLRUCleaning() {
		$cache = new ConfigTest_Config_LRU();
		for ($i = 0; $i < Config_LRU::SIZE; $i++) $cache->set($i, $i);
		$this->assertEquals(Config_LRU::SIZE, count($cache->indexing));
		$cache->clean();
		$this->assertEquals(0, count($cache->indexing), 'Clean clears all items');
		$this->assertFalse($cache->get(1), 'Clean clears all items');
		$cache->set(1, 1, array('Foo'));
		$this->assertEquals(1, count($cache->indexing));
		$cache->clean('Foo');
		$this->assertEquals(0, count($cache->indexing), 'Clean items with matching tag');
		$this->assertFalse($cache->get(1), 'Clean items with matching tag');
		$cache->set(1, 1, array('Foo', 'Bar'));
		$this->assertEquals(1, count($cache->indexing));
		$cache->clean('Bar');
		$this->assertEquals(0, count($cache->indexing), 'Clean items with any single matching tag');
		$this->assertFalse($cache->get(1), 'Clean items with any single matching tag');
	}
}

class ConfigTest_Config_LRU extends Config_LRU implements TestOnly {
	public $cache;
	public $indexing;
}

class ConfigTest_Config_MemCache extends Config_MemCache implements TestOnly {

	public $cache;
	public $tags;

}
