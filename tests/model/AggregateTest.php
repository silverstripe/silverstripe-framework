<?php

/*
 * A hierarchy of data types, to...
 *
 * @deprecated
 *
 * This is testing`
 * {@link DataObject::Aggregate()} and {@link DataObject::RelationshipAggregate()}
 * which are deprecated. Aggregates are handled directly by DataList instead.
 * This test should be removed or merged into DataListTest once those functions are
 * removed from DataObject.
 */
class AggregateTest_Foo extends DataObject implements TestOnly {
	private static $db = array(
		"Foo" => "Int"
	);
	
	private static $has_one = array('Bar' => 'AggregateTest_Bar');
	private static $belongs_many_many = array('Bazi' => 'AggregateTest_Baz');
}

/**
 * @deprecated
 */
class AggregateTest_Fab extends AggregateTest_Foo {
	private static $db = array(
		"Fab" => "Int"
	);
}

/**
 * @deprecated
 */
class AggregateTest_Fac extends AggregateTest_Fab {
	private static $db = array(
		"Fac" => "Int"
	);
}


/**
 * @deprecated
 */
class AggregateTest_Bar extends DataObject implements TestOnly {
	private static $db = array(
		"Bar" => "Int"
	);
	
	private static $has_many = array(
		"Foos" => "AggregateTest_Foo"
	);
}


/**
 * @deprecated
 */
class AggregateTest_Baz extends DataObject implements TestOnly {
	private static $db = array(
		"Baz" => "Int"
	);
	
	private static $many_many = array(
		"Foos" => "AggregateTest_Foo"
	);
}

/**
 * @deprecated
 */
class AggregateTest extends SapphireTest {
	protected static $fixture_file = 'AggregateTest.yml';
	
	protected $extraDataObjects = array(
		'AggregateTest_Foo',
		'AggregateTest_Fab',
		'AggregateTest_Fac',
		'AggregateTest_Bar',
		'AggregateTest_Baz'
	);
	
	protected $originalDeprecation;

	public function setUp() {
		parent::setUp();
		// This test tests code that was deprecated after 2.4
		$this->originalDeprecation = Deprecation::dump_settings();
		Deprecation::notification_version('2.4');
	}

	public function tearDown() {
		Deprecation::restore_settings($this->originalDeprecation);
		parent::tearDown();
	}
	
	/**
	 * Test basic aggregation on a passed type
	 */
	public function testTypeSpecifiedAggregate() {
		$foo = $this->objFromFixture('AggregateTest_Foo', 'foo1');

		// Template style access
		$this->assertEquals($foo->Aggregate('AggregateTest_Foo')->XML_val('Max', array('Foo')), 9);
		$this->assertEquals($foo->Aggregate('AggregateTest_Fab')->XML_val('Max', array('Fab')), 3);

		// PHP style access
		$this->assertEquals($foo->Aggregate('AggregateTest_Foo')->Max('Foo'), 9);
		$this->assertEquals($foo->Aggregate('AggregateTest_Fab')->Max('Fab'), 3);
	}
	/* */
	
	/**
	 * Test basic aggregation on a given dataobject
	 * @return unknown_type
	 */
	public function testAutoTypeAggregate() {
		$foo = $this->objFromFixture('AggregateTest_Foo', 'foo1');
		$fab = $this->objFromFixture('AggregateTest_Fab', 'fab1');

		// Template style access
		$this->assertEquals($foo->Aggregate()->XML_val('Max', array('Foo')), 9);
		$this->assertEquals($fab->Aggregate()->XML_val('Max', array('Fab')), 3);

		// PHP style access
		$this->assertEquals($foo->Aggregate()->Max('Foo'), 9);
		$this->assertEquals($fab->Aggregate()->Max('Fab'), 3);
	}
	/* */
	
	/**
	 * Test base-level field access - was failing due to use of custom_database_fields, not just database_fields
	 * @return unknown_type
	 */
	public function testBaseFieldAggregate() {
		$foo = $this->objFromFixture('AggregateTest_Foo', 'foo1');

		$this->assertEquals(
			$this->formatDate($foo->Aggregate('AggregateTest_Foo')->Max('LastEdited')),
			$this->formatDate(DataObject::get_one('AggregateTest_Foo', '', '', '"LastEdited" DESC')->LastEdited)
		);
		
		$this->assertEquals(
			$this->formatDate($foo->Aggregate('AggregateTest_Foo')->Max('Created')),
			$this->formatDate(DataObject::get_one('AggregateTest_Foo', '', '', '"Created" DESC')->Created)
		);
	}
	/* */

	/**
	 * Test aggregation takes place on the passed type & it's children only
	 */
	public function testChildAggregate() {
		$foo = $this->objFromFixture('AggregateTest_Foo', 'foo1');
	
		// For base classes, aggregate is calculcated on it and all children classes
		$this->assertEquals($foo->Aggregate('AggregateTest_Foo')->Max('Foo'), 9);

		// For subclasses, aggregate is calculated for that subclass and it's children only
		$this->assertEquals($foo->Aggregate('AggregateTest_Fab')->Max('Foo'), 9);
		$this->assertEquals($foo->Aggregate('AggregateTest_Fac')->Max('Foo'), 6);
		
	}
	/* */

	/**
	 * Test aggregates are cached properly
	 */
	public function testCache() {
		$this->markTestIncomplete();		
	}
	/* */
	
	/**
	 * Test cache is correctly flushed on write
	 */
	public function testCacheFlushing() {
		$foo = $this->objFromFixture('AggregateTest_Foo', 'foo1');
		$fab = $this->objFromFixture('AggregateTest_Fab', 'fab1');

		// For base classes, aggregate is calculcated on it and all children classes
		$this->assertEquals($fab->Aggregate('AggregateTest_Foo')->Max('Foo'), 9);

		// For subclasses, aggregate is calculated for that subclass and it's children only
		$this->assertEquals($fab->Aggregate('AggregateTest_Fab')->Max('Foo'), 9);
		$this->assertEquals($fab->Aggregate('AggregateTest_Fac')->Max('Foo'), 6);

		$foo->Foo = 12;
		$foo->write();

		// For base classes, aggregate is calculcated on it and all children classes
		$this->assertEquals($fab->Aggregate('AggregateTest_Foo')->Max('Foo'), 12);

		// For subclasses, aggregate is calculated for that subclass and it's children only
		$this->assertEquals($fab->Aggregate('AggregateTest_Fab')->Max('Foo'), 9);
		$this->assertEquals($fab->Aggregate('AggregateTest_Fac')->Max('Foo'), 6);
				
		$fab->Foo = 15;
		$fab->write();
		
		// For base classes, aggregate is calculcated on it and all children classes
		$this->assertEquals($fab->Aggregate('AggregateTest_Foo')->Max('Foo'), 15);

		// For subclasses, aggregate is calculated for that subclass and it's children only
		$this->assertEquals($fab->Aggregate('AggregateTest_Fab')->Max('Foo'), 15);
		$this->assertEquals($fab->Aggregate('AggregateTest_Fac')->Max('Foo'), 6);
	}
	/* */
	
	/**
	 * Test basic relationship aggregation
	 */
	public function testRelationshipAggregate() {
		$bar1 = $this->objFromFixture('AggregateTest_Bar', 'bar1');
		$this->assertEquals($bar1->RelationshipAggregate('Foos')->Max('Foo'), 8);

		$baz1 = $this->objFromFixture('AggregateTest_Baz', 'baz1');
		$this->assertEquals($baz1->RelationshipAggregate('Foos')->Max('Foo'), 8);
	}
	/* */
	
	/**
	 * Copied from DataObject::__construct(), special case for MSSQLDatabase.
	 * 
	 * @param String
	 * @return String
	 */
	protected function formatDate($dateStr) {
		$dateStr = preg_replace('/:[0-9][0-9][0-9]([ap]m)$/i', ' \\1', $dateStr);
		return date('Y-m-d H:i:s', strtotime($dateStr));
	}
}
