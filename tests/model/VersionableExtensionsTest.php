<?php
/**
 * @package framework
 * @subpackage tests
 */
class VersionableExtensionsTest extends SapphireTest
{
    protected static $fixture_file = 'VersionableExtensionsFixtures.yml';

    protected $requiredExtensions = array(
        'SiteTree'  => array('Versioned'),
    );

    public function setUpOnce()
    {
        Config::nest();

        SiteTree::add_extension('SiteTreeTest_Versionable_Extension');
        
        $cfg = Config::inst();
        
        $cfg->update('SiteTree', 'versionableExtensions', array(
        	'SiteTreeTest_Versionable_Extension' => array(
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
        $tables = DB::tableList();
        
        $check = array(
        	'sitetree_test1_live', 'sitetree_test2_live', 'sitetree_test3_live',
            'sitetree_test1_versions', 'sitetree_test2_versions', 'sitetree_test3_versions'
        );

        // Check that the right tables exist
        foreach ($check as $tableName) {
            $this->assertArrayHasKey($tableName, $tables);
        }

    }

}


class SiteTreeTest_Versionable_Extension extends DataExtension implements TestOnly {


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
			//Config::inst()->get('Versioned', 'db_for_versions_table'),
			DataObject::database_fields($this->owner->class)
		);
		
		return $fields;
	}	
}
