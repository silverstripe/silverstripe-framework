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
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldSet(
			$table
		), new FieldSet());
		
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
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldSet(
			$table
		), new FieldSet());

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
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldSet(
			$table
		), new FieldSet());

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
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldSet(
			$table
		), new FieldSet());

		$table->ShowPagination = true;
		$table->PageSize = 2;
		$_REQUEST['ctf']['Tester']['start'] = 2;
		
		$items = $table->sourceItems();
		$this->assertNotNull($items);

		$itemMap = $items->toDropdownMap("ID", "A") ;
		$this->assertEquals(array(3 => "a3", 4 => "a4"), $itemMap);
	}
	
	/**
	 * Get that visiting the field's URL returns the content of the field.
	 * This capability is used by ajax
	 */
	function testAjaxRefreshing() {
		$controller = new TableListFieldTest_TestController();
		$table = $controller->TestForm()->Fields()->First();

		$ajaxResponse = Director::test($table->Link())->getBody();

		// Check that the column headings have been rendered
        $this->assertRegExp('/<th[^>]*>\s*Col A\s*<\/th>/', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>\s*Col B\s*<\/th>/', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>\s*Col C\s*<\/th>/', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>\s*Col D\s*<\/th>/', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>\s*Col E\s*<\/th>/', $ajaxResponse);
	}
	
	function testCsvExport() {
		$table = new TableListField("Tester", "TableListFieldTest_CsvExport", array(
			"A" => "Col A",
			"B" => "Col B"
		));
		
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldSet(
			$table
		), new FieldSet());
		
		$csvResponse = $table->export();
		
		$csvOutput = $csvResponse->getBody();
		
		$this->assertNotEquals($csvOutput, false);
		
		// Create a temporary file and write the CSV to it.
		$csvFileName = tempnam(TEMP_FOLDER, 'csv-export');
		$csvFile = fopen($csvFileName, 'w');
		fwrite($csvFile, $csvOutput);
		fclose($csvFile);
		
		$csvFile = fopen($csvFileName, 'r');
		$csvRow = fgetcsv($csvFile);
		$this->assertEquals(
			$csvRow,
			array('Col A', 'Col B')
		);
		
		$csvRow = fgetcsv($csvFile);
		$this->assertEquals(
			$csvRow,
			array('"A field, with a comma"', 'A second field')
		);
		
		fclose($csvFile);
		
		unlink($csvFileName);
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

class TableListFieldTest_CsvExport extends DataObject implements TestOnly {
	static $db = array(
		"A" => "Varchar",
		"B" => "Varchar"
	);
}

class TableListFieldTest_TestController extends Controller {
	function Link() {
		return "TableListFieldTest_TestController/";
	}
	function TestForm() {
		$table = new TableListField("Table", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));

		// A TableListField must be inside a form for its links to be generated
		return new Form($this, "TestForm", new FieldSet(
			$table
		), new FieldSet());
	}
}