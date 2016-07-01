<?php

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\Versioning\VersionableExtension;
/**
 * @package framework
 * @subpackage tests
 */

class VersionableExtensionsTest extends SapphireTest
{
	protected static $fixture_file = 'VersionableExtensionsFixtures.yml';

	protected $requiredExtensions = array(
		'VersionableExtensionsTest_DataObject'  => array('SilverStripe\\ORM\\Versioning\\Versioned'),
	);

	protected $extraDataObjects = array(
		'VersionableExtensionsTest_DataObject',
	);


	public function setUpOnce()
	{
		Config::nest();

		VersionableExtensionsTest_DataObject::add_extension('SilverStripe\\ORM\\Versioning\\Versioned');
		VersionableExtensionsTest_DataObject::add_extension('VersionableExtensionsTest_Extension');

		$cfg = Config::inst();

		$cfg->update('VersionableExtensionsTest_DataObject', 'versionableExtensions', array(
			'VersionableExtensionsTest_Extension' => array(
				'test1',
				'test2',
				'test3'
			)
		));

		parent::setUpOnce();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


	public function testTablesAreCreated()
	{
		$tables = DB::table_list();

		$check = array(
			'versionableextensionstest_dataobject_test1_live', 'versionableextensionstest_dataobject_test2_live', 'versionableextensionstest_dataobject_test3_live',
			'versionableextensionstest_dataobject_test1_versions', 'versionableextensionstest_dataobject_test2_versions', 'versionableextensionstest_dataobject_test3_versions'
		);

		// Check that the right tables exist
		foreach ($check as $tableName) {

			$this->assertContains($tableName, array_keys($tables), 'Contains table: '.$tableName);
		}

	}

}

class VersionableExtensionsTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);

}


class VersionableExtensionsTest_Extension extends DataExtension implements VersionableExtension, TestOnly {


	public function isVersionedTable($table) {
		return true;
	}


	/**
	 * Update fields and indexes for the versonable suffix table
	 *
	 * @param string $suffix Table suffix being built
	 * @param array $fields List of fields in this model
	 * @param array $indexes List of indexes in this model
	 * @return array
	 */
	public function updateVersionableFields($suffix, &$fields, &$indexes){
		$indexes['ExtraField'] = true;
		$fields['ExtraField'] = 'Varchar()';
	}
}
