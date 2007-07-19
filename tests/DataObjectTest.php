<?php

/**
 * Rigorous testing of all of DataObject's abilities
 * The testing is carried out by generating a number of test object types.
 */
class DataObjectTest extends UnitTestCase {
	static $tables = array('DataObjectTest_Class','DataObjectTest_NoDefaults','DataObjectTest_OtherSubclass',
		'DataObjectTest_Subclass','DataObjectTest_FinalOne','DataObjectTest_BrokenBeforeWrite');
		
	public $whatsBeingTested = "Everything except for permissions and inter-table relationships are being tested";
	public $testComplete = "orange";
	
	function __construct() {
		global $_ALL_CLASSES;
		
		// Build the data-objects
		Database::$supressOutput = true;
		
		foreach(self::$tables as $table) {
			DB::query("DROP TABLE IF EXISTS $table");
			singleton($table)->requireTable();
			$_ALL_CLASSES['hastable'][$table] = true;
		}
		
		// Create some Class and Subclass objects
		$obj = new DataObjectTest_Class();
		$obj->update(array("Field1" => 1, "Field2" => "red"));
		$obj->write();

		$obj = new DataObjectTest_Class();
		$obj->update(array("Field1" => 6, "Field2" => "red"));
		$obj->write();

		$obj = new DataObjectTest_Class();
		$obj->update(array("Field1" => 6, "Field2" => "green"));
		$obj->write();

		$obj = new DataObjectTest_Class();
		$obj->update(array("Field1" => 2, "Field2" => "green"));
		$obj->write();

		$obj = new DataObjectTest_Subclass();
		$obj->update(array("Field1" => 2, "Field2" => "black", "Field3" => "2005-12-03"));
		$obj->write();

		$obj = new DataObjectTest_Subclass();
		$obj->update(array("Field1" => 6, "Field2" => "red", "Field3" => "2006-01-10"));
		$obj->write();

		$obj = new DataObjectTest_Subclass();
		$obj->update(array("Field1" => 4, "Field2" => "blue"));
		$obj->write();

		$obj = new DataObjectTest_OtherSubclass();
		$obj->update(array("Field1" => 4, "OtherField" => 6, "DuplicateField" => 1));
		$obj->write();

		$obj = new DataObjectTest_OtherSubclass();
		$obj->update(array("Field1" => 4, "OtherField" => 4, "DuplicateField" => 2));
		$obj->write();

	}
	function __destruct() {
		// Remove all our tester tables
		foreach(self::$tables as $table) DB::query("DROP TABLE $table");
	}
	
	/**
	 * Test customDatabaseFields(), databaseFields(), requireTable()
	 */
	function testDatabaseSetup() {
		// Check that the database has been created correctly.
		foreach(self::$tables as $table) {
			foreach(DB::query("SHOW FIELDS FROM $table") as $item) $tableSpec[$table][] = $item;
		}
		
		$desiredSpec = array ( 'DataObjectTest_Class' => array ( 0 => array ( 'Field' => 'ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => 'PRI', 'Default' => NULL, 'Extra' => 'auto_increment', ), 1 => array ( 'Field' => 'ClassName', 'Type' => 'enum(\'DataObjectTest_Class\',\'DataObjectTest_Subclass\',\'DataObjectTest_OtherSubclass\')', 'Null' => 'YES', 'Key' => '', 'Default' => 'DataObjectTest_Class', 'Extra' => '', ), 2 => array ( 'Field' => 'Created', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 3 => array ( 'Field' => 'LastEdited', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 4 => array ( 'Field' => 'Field1', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), 5 => array ( 'Field' => 'Field2', 'Type' => 'varchar(50)', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 6 => array ( 'Field' => 'Link1ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), ), 'DataObjectTest_NoDefaults' => array ( 0 => array ( 'Field' => 'ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => 'PRI', 'Default' => NULL, 'Extra' => 'auto_increment', ), 1 => array ( 'Field' => 'ClassName', 'Type' => 'enum(\'DataObjectTest_NoDefaults\')', 'Null' => 'YES', 'Key' => '', 'Default' => 'DataObjectTest_NoDefaults', 'Extra' => '', ), 2 => array ( 'Field' => 'Created', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 3 => array ( 'Field' => 'LastEdited', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 4 => array ( 'Field' => 'Field1', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), 5 => array ( 'Field' => 'Field2', 'Type' => 'tinyint(1) unsigned', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), ), 'DataObjectTest_OtherSubclass' => array ( 0 => array ( 'Field' => 'ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => 'PRI', 'Default' => NULL, 'Extra' => 'auto_increment', ), 1 => array ( 'Field' => 'DuplicateField', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), 2 => array ( 'Field' => 'OtherField', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), ), 'DataObjectTest_Subclass' => array ( 0 => array ( 'Field' => 'ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => 'PRI', 'Default' => NULL, 'Extra' => 'auto_increment', ), 1 => array ( 'Field' => 'Field3', 'Type' => 'date', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 2 => array ( 'Field' => 'AnotherField', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), 3 => array ( 'Field' => 'VarcharField', 'Type' => 'varchar(50)', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 4 => array ( 'Field' => 'DuplicateField', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), 5 => array ( 'Field' => 'Link2ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), 6 => array ( 'Field' => 'Link3ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), ), 'DataObjectTest_FinalOne' => array ( 0 => array ( 'Field' => 'ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => 'PRI', 'Default' => NULL, 'Extra' => 'auto_increment', ), 1 => array ( 'Field' => 'ClassName', 'Type' => 'enum(\'DataObjectTest_FinalOne\')', 'Null' => 'YES', 'Key' => '', 'Default' => 'DataObjectTest_FinalOne', 'Extra' => '', ), 2 => array ( 'Field' => 'Created', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 3 => array ( 'Field' => 'LastEdited', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 4 => array ( 'Field' => 'Field1', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), ), 'DataObjectTest_BrokenBeforeWrite' => array ( 0 => array ( 'Field' => 'ID', 'Type' => 'int(11)', 'Null' => '', 'Key' => 'PRI', 'Default' => NULL, 'Extra' => 'auto_increment', ), 1 => array ( 'Field' => 'ClassName', 'Type' => 'enum(\'DataObjectTest_BrokenBeforeWrite\')', 'Null' => 'YES', 'Key' => '', 'Default' => 'DataObjectTest_BrokenBeforeWrite', 'Extra' => '', ), 2 => array ( 'Field' => 'Created', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 3 => array ( 'Field' => 'LastEdited', 'Type' => 'datetime', 'Null' => 'YES', 'Key' => '', 'Default' => NULL, 'Extra' => '', ), 4 => array ( 'Field' => 'Field1', 'Type' => 'int(11)', 'Null' => '', 'Key' => '', 'Default' => '0', 'Extra' => '', ), ), );

		// Use this line to generate an updated spec.  
		// echo "<p>" . var_export($tableSpec, true) . "</p>";
		
		$this->assertEqual($tableSpec, $desiredSpec, "Database schema not properly generated: %s");
		
		// Call databaseFields() on an object with just fields
		$obj = new DataObjectTest_NoDefaults();

		$this->assertEqual($obj->databaseFields(), array(
			'ClassName' => "Enum('DataObjectTest_NoDefaults')",
			'Created' => "Datetime",
			'LastEdited' => "Datetime",
			'Field1' => "Int",
			'Field2' => "Boolean",
		));
		
		// Call customDatabaseFields() on an object with fields and has_ones
		$obj2 = new DataObjectTest_Class();
		$this->assertEqual($obj2->customDatabaseFields(), array(
			'Field1' => "Int",
			'Field2' => "Varchar",
			'Link1ID' => "Int",
		));

	}
	
	/**
	 * Test __construct(), populateDefaults()
	 */
	function testCreation() {
		// Create a new object
		$obj = new DataObjectTest_Subclass();
		
			// Are its defaults set, from self and parent?
			$this->assertEqual($obj->Field2,'happy');
			$this->assertEqual($obj->AnotherField,7);
			$this->assertEqual($obj->getAllFields(), array(
				'ID' => 0,
				'Field2' => 'happy',
				'AnotherField' => 7,
			));
		
			// Can you write it? Does it create a new record? Are all the defaults saved?
			$obj->write();
			$this->assertNotEqual($obj->ID, 0);
			$this->assertEqual(DB::query("SELECT Field2 FROM DataObjectTest_Class WHERE ID = $obj->ID")->value(), 'happy');
			$this->assertEqual(DB::query("SELECT AnotherField FROM DataObjectTest_Subclass WHERE ID = $obj->ID")->value(), 7);
		
			// Can you write an object that has no defaults?
			$obj2 = new DataObjectTest_NoDefaults();
			$obj2->write();
			$this->assertNotEqual($obj->ID, 0);

		// Create an object, passing the data - force a change
		$obj3 = new DataObjectTest_Subclass(array(
			"ID" => $obj->ID,
			"Field2" => 'sad',
		));
		$obj3->forceChange();
		
		$this->assertNull($obj3->AnotherField);

			// Does saving update the database, rather than insert?
			$obj3->write();
			$this->assertEqual(DB::$lastQuery, array ( 'DataObjectTest_Class' => array ( 'fields' => array ( 'Field2' => '\'sad\'', 'LastEdited' => 'now()', ), 'command' => 'update', 'id' => '10', ), ));
			$this->assertEqual($obj->ID, $obj3->ID);
			$this->assertEqual(DB::query("SELECT Field2 FROM DataObjectTest_Class WHERE ID = $obj3->ID")->value(), 'sad');
			$this->assertEqual(DB::query("SELECT AnotherField FROM DataObjectTest_Subclass WHERE ID = $obj3->ID")->value(), 7);
			
		// Create a singleton - are the defaults not set?
		$obj4 = singleton("DataObjectTest_Class");
		$this->assertNull($obj4->Field2);
		
		// Clean-up: delete any records.
		$obj->delete();
		$obj2->delete();
		$obj3->delete();
	}
	
	/**
	 * Test buildDataObjectSet(), buildSQL()
	 */
	function testQueryGeneration() {
		// Test query of Class - returns Class, Subclass and OtherSubclass objects
		$records = DataObject::get("DataObjectTest_Class");
		
		$this->assertEqual($records->consolidate('ClassName','Field1','Field2','Link1ID','Field3','AnotherField'),
			array (
				array( 'DataObjectTest_Class', '1', 'red', '0', '', '',),
				array( 'DataObjectTest_Class', '6', 'red', '0', '', '',),
				array( 'DataObjectTest_Class', '6', 'green', '0', '', '',),
				array( 'DataObjectTest_Class', '2', 'green', '0', '', '',),
				array( 'DataObjectTest_Subclass', '2', 'black', '0', '2005-12-03', '7',),
				array( 'DataObjectTest_Subclass', '6', 'red', '0', '2006-01-10', '7',),
				array( 'DataObjectTest_Subclass', '4', 'blue', '0', '', '7',),
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '',),
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '',), 
    	)
		);
		

		// Test query of Subclass - returns just Subclass objects
		$records = DataObject::get("DataObjectTest_Subclass");
		$this->assertEqual($records->consolidate('ClassName','Field1','Field2','Link1ID','Field3','AnotherField'),
			array (
				array( 'DataObjectTest_Subclass', '2', 'black', '0', '2005-12-03', '7',),
				array( 'DataObjectTest_Subclass', '6', 'red', '0', '2006-01-10', '7',),
				array( 'DataObjectTest_Subclass', '4', 'blue', '0', '', '7',),
			)
		);

		// Test filtering on one table's fields, sorting on another
		$records = DataObject::get("DataObjectTest_Class", "Field1 = 4", "OtherField");
		$this->assertEqual($records->consolidate('ClassName','Field1','Field2','Link1ID','Field3','AnotherField'), 
			array (
				array( 'DataObjectTest_Subclass', '4', 'blue', '0', '', '7',),
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '',),
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '',),
			)
		);
			
		// Test filtering on DuplicateField
		$records = DataObject::get("DataObjectTest_Class", "`DataObjectTest_OtherSubclass`.DuplicateField = 2");
		$this->assertEqual($records->consolidate('ClassName','Field1','Field2','Link1ID','Field3','AnotherField'), 
			array (
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '',),
			)
		);
		
		// Test sorting by DuplicateField
		$records = DataObject::get("DataObjectTest_Class", "Field1 = 4", "`DataObjectTest_OtherSubclass`.DuplicateField");
		$this->assertEqual($records->consolidate('ClassName','Field1','Field2','Link1ID','Field3','AnotherField','DuplicateField'), 
			array (
				array( 'DataObjectTest_Subclass', '4', 'blue', '0', '', '7', '',),
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '', '1',),
				array( 'DataObjectTest_OtherSubclass', '4', 'happy', '0', '', '', '2',),
			)
		);

		// Test an empty query
		$records = DataObject::get("DataObjectTest_Class", "Field1 = 4234234", "`DataObjectTest_OtherSubclass`.DuplicateField");
		$this->assertNull($records);
	}
	
	/**
	 * Test buildDataObjectSet(), buildSQL()
	 */
	function testLimits() {
		// Test query of Class with a limit 5
		$records = DataObject::get("DataObjectTest_Class", "", "`DataObjectTest_Class`.ID", "", "5");
		$this->assertPattern('/start=5$/', $records->NextLink());
		$this->assertNull($records->PrevLink());
		$this->assertEqual(5, $records->Count());
		$this->assertEqual(9, $records->TotalItems());
		
		// Test query of Class with a limit 5,5
		$records = DataObject::get("DataObjectTest_Class", "", "`DataObjectTest_Class`.ID", "", "5,5");
		$this->assertNull($records->NextLink());
		$this->assertPattern('/start=0$/',$records->PrevLink());
		$this->assertEqual(4, $records->Count());
		$this->assertEqual(9, $records->TotalItems());
		
		// Test query of Class with a limit 0,5		
		$records = DataObject::get("DataObjectTest_Class", "", "`DataObjectTest_Class`.ID", "", "0,5");
		$this->assertPattern('/start=5$/', $records->NextLink());
		$this->assertNull($records->PrevLink());
		$this->assertEqual(5, $records->Count());
		$this->assertEqual(9, $records->TotalItems());
	}
	
	/**
	 * Test delete(), onBeforeDelete()
	 */
	function testDelete() {
		// Test simple delete - has the record disappeared
		$obj = new DataObjectTest_Subclass();
		$obj->write();
		$id = $obj->ID;

		$this->assertEqual($id, DB::query("SELECT ID FROM DataObjectTest_Class WHERE ID = $id")->value());
		$this->assertEqual($id, DB::query("SELECT ID FROM DataObjectTest_Subclass WHERE ID = $id")->value());
		
		$obj->delete();
		$this->assertNull(DB::query("SELECT ID FROM DataObjectTest_Class WHERE ID = $id")->value(), "Couldn't delete record #$id");
		$this->assertNull(DB::query("SELECT ID FROM DataObjectTest_Subclass WHERE ID = $id")->value(), "Couldn't delete record #$id");
		$this->assertEqual(0, $obj->ID, 'ID not set to null when data-object deleted - %s');
		
		// Test delete with onBeforeDelete()
		$obj = new DataObjectTest_NoDefaults();
		$obj->Field2 = 10;
		$obj->write();
		$obj->delete();
		$this->assertEqual(0, $obj->ID, 'ID not set to null when data-object deleted - %s');
		$this->assertEqual(30, $obj->Field1, 'onBeforeDelete not getting executed  -%s');
		
		// Test delete with a broken onBeforeDelete()
		$obj = new DataObjectTest_FinalOne();
		$obj->write();
		$obj->delete();
		$this->assertError(null, 'Broken onBeforeDelete not detected - %s');
	}
	
	/**
	 * Test update(), forceChange(), onBeforeWrite(), setField()
	 */
	function testUpdate() {
		// Test adding some values and writing
		$obj = new DataObjectTest_Class();
		$obj->Field2 = "grumpy";
		$obj->write();
		$this->assertEqual("grumpy", DB::query("SELECT Field2 FROM DataObjectTest_Class WHERE ID = $obj->ID")->value());
		
		// Test adding some values are the same and writing, ensure that no DB query is made.
		DB::$lastQuery = null;
		$obj->Field2 = "grumpy";
		$obj->Field1 = null;
		$obj->write();
		$this->assertNull(DB::$lastQuery, "Redundnant update called - %s");

		// Similar test using update()
		$obj2 = new DataObjectTest_Class();
		$obj2->update(array('Field2' => "grumpy"));
		$obj2->write();
		$this->assertEqual("grumpy", DB::query("SELECT Field2 FROM DataObjectTest_Class WHERE ID = $obj2->ID")->value());
		
		// Test adding some values are the same and writing, ensure that no DB query is made.
		DB::$lastQuery = null;
		$obj2->update(array('Field2' => "grumpy", 'Field1' => null));
		$obj2->write();
		$this->assertNull(DB::$lastQuery, "Redundnant update called - %s");
		
		// Test forceChange() and update, ensure complete DB query is made.
		$obj2->forceChange();
		$obj2->write();
		$this->assertNotNull(DB::$lastQuery, "Forced update not called - %s");
		
		// Test writing with an onBeforeWrite()
		$obj3 = new DataObjectTest_NoDefaults();
		$obj3->Field2 = 10;
		$obj3->write();
		$this->assertEqual(50, $obj3->Field1);
		$this->assertEqual(50, DB::query("SELECT Field1 FROM DataObjectTest_NoDefaults WHERE ID = $obj3->ID")->value());
		
		// Test writing with a broken onBeforeWrite()
		$obj4 = new DataObjectTest_BrokenBeforeWrite();
		$obj4->Field2 = 7;
		$obj4->write();
		$this->assertError(null, "Broken onBeforeWrite not detected - %s");
		
		// Cleanup
		$obj->delete();
		$obj2->delete();
		$obj3->delete();
		$obj4->delete();
	}
	
	/**
	 * Test can()
	 */
	function testPermissions() {
		// STILL TO COME!
	}
	
	/**
	 * Test createComponent(), getComponent(), has_one()
	 */
	function testHasOne() {
		// Get a has-one item using getComponent(), where one exists
		
		// Get a has-one item using the FieldName() generated method.

		// Get a has-one item that's defined on the parent class, using the FieldName() generated method
		
		// Get a has-one item using getComponent(), where one doesn't exists
				
		// Call has_one() for a specific field

		// Call has_one() for all fields - base class

		// Call has_one() for all fields - subclass
		
		// Call has_one() for a non-existant field
	}

	/**
	 * Test createComponent(), getComponents(), has_many()
	 */
	function testHasMany() {
		
	}
	
	/**
	 * Test getManyManyComponents(), many_many()
	 */
	function testManyMany() {
		
	}
	
	/**
	 * Test defineMethods()
	 */
	function testDefineMethods() {
		// Create an object with has-one, has-many, many-many joins
		
		// Create another one of those objects, ensure that the same methods are set up
	}
	
	/**
	 * Test fieldExists()
	 */	
	function testFieldExists() {
		$obj1 = singleton("DataObjectTest_Class");
		$obj2 = singleton("DataObjectTest_Subclass");
		
		// Test that fieldExists() returns field from this class
		$this->assertEqual('Int', $obj1->fieldExists('Field1'));
		
		// Test that fieldExists() returns has-one from this class
		$this->assertEqual('Int', $obj1->fieldExists('Link1ID'));

		// Test that fieldExists() doesn't return field from parent class
		$this->assertEqual('Date', $obj2->fieldExists('Field3'));
		$this->assertFalse($obj2->fieldExists('Field1'));

		// Test that fieldExists() doesn't return a totally unrelated field
		$this->assertFalse($obj2->fieldExists('afsdafasdfdsafs'));
		
		// Test that fieldExists() doesnt' return has-one from parent class
		$this->assertFalse($obj2->fieldExists('Link1ID'));

		// Test ID, ClassName, LastEdited and Created on base class
		$this->assertEqual('Enum', $obj1->fieldExists('ClassName'));
		$this->assertEqual('Datetime', $obj1->fieldExists('LastEdited'));
		$this->assertEqual('Datetime', $obj1->fieldExists('Created'));
		$this->assertEqual('Int', $obj1->fieldExists('ID'));
		
		// Test ID on sub-class
		$this->assertEqual('Int', $obj1->fieldExists('ID'));
	}
	
	/**
	 * Test getAllFields()
	 */
	function testGetAllFields() {
		// Call getAllFields() on a base class
		$obj1 = DataObject::get_by_id("DataObjectTest_Class",1);
		$getAllFields1 = $obj1->getAllFields();
		$this->assertEqual($getAllFields1['ID'],1);
		$this->assertEqual($getAllFields1['ClassName'],"DataObjectTest_Class");
		$this->assertEqual($getAllFields1['Field1'],1);
		$this->assertEqual($getAllFields1['Field2'],'red');
		
		// Call getAllFields() on a sub-class
		$obj2 = DataObject::get_by_id("DataObjectTest_Class",6);
		$getAllFields2 = $obj2->getAllFields();
		$this->assertEqual($getAllFields2['ID'],6);
		$this->assertEqual($getAllFields2['ClassName'],"DataObjectTest_Subclass");
		$this->assertEqual($getAllFields2['Field1'],6);
		$this->assertEqual($getAllFields2['Field2'],'red');
		$this->assertEqual($getAllFields2['Field3'],'2006-01-10');
	}
	
	/**
	 * Test setCastedField
	 */
	function testSetCastedField() {
		// Test setting of a date field - different date formats
		$obj = new DataObjectTest_Subclass();
		
		$dates = array(
			"10 Jan 2006" => "2006-01-10",
			"10/3/2006" => "2006-03-10",
			"2006-01-01" => "2006-01-01",
		);
		
		foreach($dates as $input => $internal) {
			$obj->setCastedField("Field3", $input);
			$this->assertEqual($internal, $obj->Field3, "Couldn't convert $input to $internal - %s");
		}
	}
	
	/**
	 * Test get_one(), flush_cache()
	 */
	function testGetOneCache() {
		// Get an object
		$obj1 = DataObject::get_by_id("DataObjectTest_Class",6);
		$this->assertEqual(6,$obj1->ID);
		
		// Get the same object again, verify that DB query wasn't made
		DB::$lastQuery = null;
		$obj2 = DataObject::get_by_id("DataObjectTest_Class",6);
		$this->assertNull(DB::$lastQuery);
		$this->assertEqual(6,$obj2->ID);
		
		// Get an object with the same filter on a different class
		$obj3 = DataObject::get_by_id("DataObjectTest_Subclass",6);
		$this->assertNotNull(DB::$lastQuery);
		$this->assertEqual(6,$obj3->ID);
		
		// Flush the cache
		$obj1->flushCache();
		
		// Get the same object again, verify that DB query was made
		DB::$lastQuery = null;
		$obj4 = DataObject::get_by_id("DataObjectTest_Class",6);
		$this->assertNotNull(DB::$lastQuery);
		$this->assertEqual(6,$obj4->ID);
	}
}

/// These DataObjects are a "fire-test" for the DataObject system in general.

class DataObjectTest_Class extends DataObject {
	static $db = array(
		"Field1" => "Int",
		"Field2" => "Varchar",
	);
	static $has_one = array(
		"Link1" => "DataObjectTest_NoDefaults",
	);
	static $defaults = array(
		"Field2" => "happy",
	);
}
class DataObjectTest_Subclass extends DataObjectTest_Class {
	static $db = array(
		"Field3" => "Date",
		"AnotherField" => "Int",
		"VarcharField" => "Varchar",
		"DuplicateField" => "Int",
	);
	static $has_one = array(
		"Link2" => "DataObjectTest_FinalOne",
		"Link3" => "DataObjectTest_NoDefaults",
	);
	static $defaults = array(
		"AnotherField" => 7,
	);
}
class DataObjectTest_OtherSubclass extends DataObjectTest_Class {
	static $db = array(
		"DuplicateField" => "Int",
		"OtherField" => "Int",
	);
}

class DataObjectTest_NoDefaults extends DataObject {
	static $db = array(
		"Field1" => "Int",
		"Field2" => "Boolean",
	);
	
	function onBeforeDelete() {
		parent::onBeforeDelete();

		$this->Field1 = $this->Field2 * 3;
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();

		$this->Field1 = $this->Field2 * 5;
	}
}

class DataObjectTest_FinalOne extends DataObject {
	static $db = array(
		"Field1" => "Int",
	);
	
	// Broken onBeforeDelete
	function onBeforeDelete() {
		if(!$this->Field1) $this->Field1 = 490;
		
	}

}

class DataObjectTest_BrokenBeforeWrite extends DataObject {
	static $db = array(
		"Field1" => "Int",
	);

	// Broken onBeforeWrite
	function onBeforeWrite() {
		if(!$this->Field1) $this->Field1 = 490;
	}
}

?>