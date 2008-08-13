<?php

class CheckboxSetFieldTest extends SapphireTest {
	
	function testSaveWithNothingSelected() {
		/* Set up a test data object */
		$one = new CheckboxSetFieldTest_Obj();
		$one->A = "A1";
		$one->write();
		
		/* Create a CheckboxSetField with nothing selected */
		$field = new CheckboxSetField("Relation", "Test field", DataObject::get("CheckboxSetFieldTest_Obj")->map());
		
		/* Saving should work */
		$field->saveInto($one);
		
		/* Nothing should go into CheckboxSetFieldTest_Obj_Relation */
		$this->assertNull(DB::query("SELECT * FROM CheckboxSetFieldTest_Obj_Relation")->value());	
	}

	function testSaveWithArrayValueSet() {
		/* Set up two test data object */
		$one = new CheckboxSetFieldTest_Obj();
		$one->A = "A1";
		$one->write();

		$two = new CheckboxSetFieldTest_Obj();
		$two->A = "A2";
		$two->write();
		
		/* Create a CheckboxSetField with 2 items selected.  Note that the array is in the format (key) => (selected) */
		$field = new CheckboxSetField("Relation", "Test field", DataObject::get("CheckboxSetFieldTest_Obj")->map());
		$field->setValue(array(
			1 => true,
			2 => true));
		
		/* Saving should work */
		$field->saveInto($one);
		
		/* Data shold be saved into CheckboxSetField */   
		$this->assertEquals(array($one->ID,$one->ID), DB::query("SELECT CheckboxSetFieldTest_ObjID FROM CheckboxSetFieldTest_Obj_Relation")->column());	
		$this->assertEquals(array(1,2), DB::query("SELECT ChildID FROM CheckboxSetFieldTest_Obj_Relation")->column());	
	}
	
}

class CheckboxSetFieldTest_Obj extends DataObject implements TestOnly {
	static $db = array(
		"A" => "Varchar",
		"B" => "Varchar",
	);
	
	static $many_many = array(
		"Relation" => "CheckboxSetFieldTest_Obj",
	);
	
}