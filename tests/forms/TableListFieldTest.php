<?php

class TableListFieldTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/forms/TableListFieldTest.yml';
	
	function testCanReferenceCustomMethodsAndFiledsOnObject() {
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		
		$result = $table->FieldHolder();
		
		// Do a quick check to ensure that some of the D() and getE() values got through
		$this->assertRegExp('/>\s*a2\s*</', $result);
		$this->assertRegExp('/>\s*a2\/b2\/c2\s*</', $result);
		$this->assertRegExp('/>\s*a2-e</', $result);
	}
}

class TableListFieldTest_Obj extends DataObject implements TestOnly {
	static $db = array(
		"A" => "Varchar",
		"B" => "Varchar",
		"C" => "Varchar",
	);
	
	function D() {
		return $this->A . '/' . $this->B . '/' . $this->C;
	}
	
	function getE() {
		return $this->A . '-e';
	}
   
}
