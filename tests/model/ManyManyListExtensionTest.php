<?php

/**
 * @package framework
 * @subpackage tests
 */
class ManyManyListExtensionTest extends SapphireTest {

	protected static $fixture_file = 'ManyManyListExtensionTest.yml';

	protected $extraDataObjects = array(
		'ManyManyListTest_IndirectPrimary'
	);

	// Test that when one side of a many-many relationship is added by extension, both
	// sides still see the extra fields.
	public function testExtraFieldsViaExtension() {
		// This extends SiteTree with the secondary extension that adds the relationship back
		// to the primary. The instance from the fixture is Page, deliberately a sub-class of
		// the extended class.
		Object::add_extension('SiteTree', 'ManyManyListTest_IndirectSecondaryExtension');

		// Test from the primary (not extended) to the secondary (which is extended)
		$primary = $this->objFromFixture('ManyManyListTest_IndirectPrimary', 'manymany_extra_primary');
		$secondaries = $primary->Secondary();
		$extraFields = $secondaries->getExtraFields();

		$this->assertTrue(count($extraFields) > 0, 'has extra fields');
		$this->assertTrue(isset($extraFields['DocumentSort']), 'has DocumentSort');

		// Test from the secondary (which is extended) to the primary (not extended)
		$secondary = $this->objFromFixture('Page', 'manymany_extra_secondary');
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
 * defined.) The many-many refers to SiteTree rather than page by design, because the instance of
 * the other end of the relationship will be a Page, a sub-class.
 */
class ManyManyListTest_IndirectPrimary extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $many_many = array(
		'Secondary' => 'SiteTree'
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
 * An extension that is applied to SiteTree that implements the other side of the many-many
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
