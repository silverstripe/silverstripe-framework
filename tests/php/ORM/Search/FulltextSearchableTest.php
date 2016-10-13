<?php

use SilverStripe\Assets\File;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Search\FulltextSearchable;




/**
 * @package framework
 * @subpackage tests
 */
class FulltextSearchableTest extends SapphireTest {

	public function setUp() {
		parent::setUp();

		FulltextSearchable::enable('SilverStripe\\Assets\\File');
	}

	/**
	 * FulltextSearchable::enable() leaves behind remains that don't get cleaned up
	 * properly at the end of the test. This becomes apparent when a later test tries to
	 * ALTER TABLE File and add fulltext indexes with the InnoDB table type.
	 */
	public function tearDown() {
		parent::tearDown();

		File::remove_extension('SilverStripe\\ORM\\Search\\FulltextSearchable');
		Config::inst()->update('SilverStripe\\Assets\\File', 'create_table_options', array(
			MySQLSchemaManager::ID => 'ENGINE=InnoDB')
		);
	}

	public function testEnable() {
		$this->assertTrue(File::has_extension('SilverStripe\\ORM\\Search\\FulltextSearchable'));
	}

	public function testEnableWithCustomClasses() {
		FulltextSearchable::enable(array('SilverStripe\\Assets\\File'));
		$this->assertTrue(File::has_extension('SilverStripe\\ORM\\Search\\FulltextSearchable'));

		File::remove_extension('SilverStripe\\ORM\\Search\\FulltextSearchable');
		$this->assertFalse(File::has_extension('SilverStripe\\ORM\\Search\\FulltextSearchable'));
	}

}
