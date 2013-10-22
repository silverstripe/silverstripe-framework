<?php
/**
 * @package framework
 * @subpackage tests
 */

class FulltextSearchableTest extends SapphireTest {

	public function setUp() {
		parent::setUp();

		FulltextSearchable::enable('File');
	}

	/**
	 * FulltextSearchable::enable() leaves behind remains that don't get cleaned up
	 * properly at the end of the test. This becomes apparent when a later test tries to
	 * ALTER TABLE File and add fulltext indexes with the InnoDB table type.
	 */
	public function tearDown() {
		parent::tearDown();

		File::remove_extension('FulltextSearchable');
		Config::inst()->update('File', 'create_table_options', array('MySQLDatabase' => 'ENGINE=InnoDB'));
	}

	public function testEnable() {
		$this->assertTrue(File::has_extension('FulltextSearchable'));
	}

	public function testEnableWithCustomClasses() {
		FulltextSearchable::enable(array('File'));
		$this->assertTrue(File::has_extension('FulltextSearchable'));

		File::remove_extension('FulltextSearchable');
		$this->assertFalse(File::has_extension('FulltextSearchable'));
	}

}
