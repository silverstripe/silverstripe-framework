<?php
/**
 * @package sapphire
 * @subpackage Testing
 */
class DatabaseTest extends SapphireTest {
	
	function testDontRequireField() {
		$conn = DB::getConn();
		$this->assertArrayHasKey(
			'MyField',
			$conn->fieldList('DatabaseTest_MyObject')
		);
		
		$conn->dontRequireField('DatabaseTest_MyObject', 'MyField');
		$this->assertArrayHasKey(
			'_obsolete_MyField',
			$conn->fieldList('DatabaseTest_MyObject'),
			'Field is renamed to _obsolete_<fieldname> through dontRequireField()'
		);
		
		// tested schema updates, so need to rebuild the database
		self::kill_temp_db();
		self::create_temp_db();
	}
	
	function testRenameField() {
		$conn = DB::getConn();
		
		$conn->renameField('DatabaseTest_MyObject', 'MyField', 'MyRenamedField');

		$this->assertArrayHasKey(
			'MyRenamedField',
			$conn->fieldList('DatabaseTest_MyObject'),
			'New fieldname is set through renameField()'
		);
		$this->assertArrayNotHasKey(
			'MyField',
			$conn->fieldList('DatabaseTest_MyObject'),
			'Old fieldname isnt preserved through renameField()'
		);
		
		// tested schema updates, so need to rebuild the database
		self::kill_temp_db();
		self::create_temp_db();
	}
	
	function testMySQLCreateTableOptions() {
		if(DB::getConn() instanceof MySQLDatabase) {
			$ret = DB::query(sprintf(
				'SHOW TABLE STATUS WHERE "Name" = \'%s\'',
				'DatabaseTest_MyObject'
			))->first();
			$this->assertEquals($ret['Engine'],'InnoDB',
				"MySQLDatabase tables can be changed to InnoDB through DataObject::\$create_table_options"
			);
		}
		
	}
	
}

class DatabaseTest_MyObject extends DataObject implements TestOnly {
	
	static $create_table_options = array('MySQLDatabase' => 'ENGINE=InnoDB');
	
	static $db = array(
		'MyField' => 'Varchar'
	);
}
?>