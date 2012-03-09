<?php

class ConfigTest_DefinesFoo extends Object {
	protected static $foo = 1;
}

class ConfigTest_DefinesBar extends ConfigTest_DefinesFoo {
	public static $bar = 2;
}

class ConfigTest_DefinesFooAndBar extends ConfigTest_DefinesFoo {
	protected static $foo = 3;
	public static $bar = 3;
}

class ConfigTest_DefinesFooDoesntExtendObject {
	protected static $foo = 4;
}

class ConfigTest extends SapphireTest {

	function testMerges() {
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
		$this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2, 'Baz' => 4), 'D' => 3));

		$result = array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 2), 'D' => 3);
		Config::merge_array_high_into_low($result, array('C' => array('Bar' => 3, 'Baz' => 4)));
		$this->assertEquals($result, array('A' => 1, 'B' => 2, 'C' => array('Foo' => 1, 'Bar' => 3, 'Baz' => 4), 'D' => 3));
	}

	function testStaticLookup() {
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFoo', 'foo'), 1);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFoo', 'bar'), null);

		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesBar', 'foo'), null);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesBar', 'bar'), 2);

		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooAndBar', 'foo'), 3);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooAndBar', 'bar'), 3);

		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooDoesntExtendObject', 'foo'), 4);
		$this->assertEquals(Object::static_lookup('ConfigTest_DefinesFooDoesntExtendObject', 'bar'), null);
	}

	function testFragmentOrder() {
		// $manifest = new SS_ConfigManifest(BASE_PATH, false, true);
	}
	
}
