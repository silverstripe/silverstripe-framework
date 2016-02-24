<?php

/**
 * @package framework
 * @subpackage tests
 */
class ManyManyListExtensionTest extends SapphireTest {

	protected static $fixture_file = 'ManyManyListExtensionTest.yml';

	protected $extraDataObjects = array(
		'ManyManyListTest_IndirectPrimary',
		'ManyManyListTest_Secondary',
		'ManyManyListTest_SecondarySub'
	);

	// Test that when one side of a many-many relationship is added by extension, both
	// sides still see the extra fields.
	public function testExtraFieldsViaExtension() {
		// This extends ManyManyListTest_Secondary with the secondary extension that adds the relationship back
		// to the primary. The instance from the fixture is ManyManyListTest_SecondarySub, deliberately a sub-class of
		// the extended class.
		Object::add_extension('ManyManyListTest_Secondary', 'ManyManyListTest_IndirectSecondaryExtension');

		// Test from the primary (not extended) to the secondary (which is extended)
		$primary = $this->objFromFixture('ManyManyListTest_IndirectPrimary', 'manymany_extra_primary');
		$secondaries = $primary->Secondary();
		$extraFields = $secondaries->getExtraFields();

		$this->assertTrue(count($extraFields) > 0, 'has extra fields');
		$this->assertTrue(isset($extraFields['DocumentSort']), 'has DocumentSort');

		// Test from the secondary (which is extended) to the primary (not extended)
		$secondary = $this->objFromFixture('ManyManyListTest_SecondarySub', 'manymany_extra_secondary');

		$primaries = $secondary->Primary();
		$extraFields = $primaries->getExtraFields();

		$this->assertTrue(count($extraFields) > 0, 'has extra fields');
		$this->assertTrue(isset($extraFields['DocumentSort']), 'has DocumentSort');
	}
}

/**
 * @package framework
 * @subpackage tests
 *
 * A data object that implements the primary side of a many_many (where the extra fields are
 * defined.) The many-many refers to ManyManyListTest_Secondary rather than ManyManyListTest_SecondarySub
 * by design, because we're trying to test that a subclass instance picks up the extra fields of it's parent.
 */
class ManyManyListTest_IndirectPrimary extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $many_many = array(
		'Secondary' => 'ManyManyListTest_Secondary'
	);

	private static $many_many_extraFields = array(
		'Secondary' => array(
			'DocumentSort' => 'Int'
		)
	);
}

/**
 * @package framework
 * @subpackage tests
 *
 * A data object that implements the secondary side of a many_many when extended by
 * ManyManyListTest_IndirectSecondaryExtension.
 */
class ManyManyListTest_Secondary extends DataObject implements TestOnly {

	// Possibly not required, but want to simulate a real test failure case where
	// database tables are present.
	private static $db = array(
		'Title' => 'Varchar(255)'
	);

}

/**
 * @package framework
 * @subpackage tests
 *
 * A data object that is a subclass of the secondary side. The test will create an instance of this,
 * and ensure that the extra fields are available on the instance even though the many many is
 * defined at the parent level.
 */
class ManyManyListTest_SecondarySub extends ManyManyListTest_Secondary {

	// private static $db = array(
	// 	'Other' => 'Varchar(255)'
	// );

}

/**
 * @package framework
 * @subpackage tests
 *
 * An extension that is applied to ManyManyListTest_Secondary that implements the other side of the many-many
 * relationship.
 */
class ManyManyListTest_IndirectSecondaryExtension extends DataExtension implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $belongs_many_many = array(
		'Primary' => 'ManyManyListTest_IndirectPrimary'
	);

}
