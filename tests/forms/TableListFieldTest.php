<?php

class TableListFieldTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/forms/TableListFieldTest.yml';
	
	function testCanReferenceCustomMethodsAndFieldsOnObject() {
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
	
	function testUnpaginatedSourceItemGeneration() {
		/* In this simple case, the source items should just list all the data objects specified */
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		$items = $table->sourceItems();
		$this->assertNotNull($items);
		
		$itemMap = $items->toDropdownMap("ID", "A") ;
		$this->assertEquals(array(1 => "a1", 2 => "a2", 3 => "a3", 4 => "a4", 5 => "a5"), $itemMap);
	}

	function testFirstPageOfPaginatedSourceItemGeneration() {
		/* With pagination enabled, only the first page of items should be shown */
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		$table->ShowPagination = true;
		$table->PageSize = 2;
		
		$items = $table->sourceItems();
		$this->assertNotNull($items);

		$itemMap = $items->toDropdownMap("ID", "A") ;
		$this->assertEquals(array(1 => "a1", 2 => "a2"), $itemMap);
	}
	
	function testSecondPageOfPaginatedSourceItemGeneration() {
		/* With pagination enabled, only the first page of items should be shown */
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		$table->ShowPagination = true;
		$table->PageSize = 2;
		$_REQUEST['ctf']['Tester']['start'] = 2;
		
		$items = $table->sourceItems();
		$this->assertNotNull($items);

		$itemMap = $items->toDropdownMap("ID", "A") ;
		$this->assertEquals(array(3 => "a3", 4 => "a4"), $itemMap);
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
