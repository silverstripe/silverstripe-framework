<?php
/**
 * @package framework
 * @subpackage tests
 */

class VersionableExtensionsTest extends SapphireTest
{
	protected static $fixture_file = 'VersionableExtensionsFixtures.yml';

	protected $requiredExtensions = array(
		'VersionableExtensionsTest_DataObject'  => array('Versioned'),
	);

	protected $extraDataObjects = array(
		'VersionableExtensionsTest_DataObject',
	);


	public function setUpOnce()
	{
		Config::nest();

		VersionableExtensionsTest_DataObject::add_extension('Versioned');
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


class VersionableExtensionsTest_Extension extends DataExtension implements TestOnly {


	public function isVersionedTable($table){
		return true;
	}


	/**
	 * fieldsInExtraTables function.
	 *
	 * @access public
	 * @param mixed $suffix
	 * @return array
	 */
	public function fieldsInExtraTables($suffix){
		$fields = array();
		//$fields['db'] = DataObject::database_fields($this->owner->class);
		$fields['indexes'] = $this->owner->databaseIndexes();

		$fields['db'] = array_merge(
			DataObject::database_fields($this->owner->class)
		);

		return $fields;
	}
}
