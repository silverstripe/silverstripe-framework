<?php

class TableListFieldTest extends SapphireTest {
	static $fixture_file = 'TableListFieldTest.yml';

	protected $extraDataObjects = array(
		'TableListFieldTest_Obj',
		'TableListFieldTest_CsvExport',
	);
	
	function testCanReferenceCustomMethodsAndFieldsOnObject() {
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			$table
		), new FieldList());
		
		$result = $table->FieldHolder();
	
		// Do a quick check to ensure that some of the D() and getE() values got through
		$this->assertRegExp('/>\s*a2\s*</', $result);
		$this->assertRegExp('/>\s*a2\/b2\/c2\s*</', $result);
		$this->assertRegExp('/>\s*a2-e</', $result);
	}
	
	function testUnpaginatedSourceItemGeneration() {
		$item1 = $this->objFromFixture('TableListFieldTest_Obj', 'one');
		$item2 = $this->objFromFixture('TableListFieldTest_Obj', 'two');
		$item3 = $this->objFromFixture('TableListFieldTest_Obj', 'three');
		$item4 = $this->objFromFixture('TableListFieldTest_Obj', 'four');
		$item5 = $this->objFromFixture('TableListFieldTest_Obj', 'five');
		
		// In this simple case, the source items should just list all the data objects specified 
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			$table
		), new FieldList());
	
		$items = $table->sourceItems();
		$this->assertNotNull($items);
		
		$itemMap = $items->map("ID", "A") ;
		$this->assertEquals(array(
			$item1->ID => "a1", 
			$item2->ID => "a2", 
			$item3->ID => "a3", 
			$item4->ID => "a4", 
			$item5->ID => "a5"
		), $itemMap->toArray());
	}
	
	function testFirstPageOfPaginatedSourceItemGeneration() {
		$item1 = $this->objFromFixture('TableListFieldTest_Obj', 'one');
		$item2 = $this->objFromFixture('TableListFieldTest_Obj', 'two');
		$item3 = $this->objFromFixture('TableListFieldTest_Obj', 'three');
		$item4 = $this->objFromFixture('TableListFieldTest_Obj', 'four');
		$item5 = $this->objFromFixture('TableListFieldTest_Obj', 'five');
		
		// With pagination enabled, only the first page of items should be shown 
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			$table
		), new FieldList());
	
		$table->ShowPagination = true;
		$table->PageSize = 2;
		
		$items = $table->sourceItems();
		$this->assertNotNull($items);
	
		$itemMap = $items->map("ID", "A") ;
		$this->assertEquals(array(
			$item1->ID => "a1", 
			$item2->ID => "a2"
		), $itemMap->toArray());
	}
	
	function testSecondPageOfPaginatedSourceItemGeneration() {
		$item1 = $this->objFromFixture('TableListFieldTest_Obj', 'one');
		$item2 = $this->objFromFixture('TableListFieldTest_Obj', 'two');
		$item3 = $this->objFromFixture('TableListFieldTest_Obj', 'three');
		$item4 = $this->objFromFixture('TableListFieldTest_Obj', 'four');
		$item5 = $this->objFromFixture('TableListFieldTest_Obj', 'five');
		
		// With pagination enabled, only the first page of items should be shown
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			$table
		), new FieldList());
	
		$table->ShowPagination = true;
		$table->PageSize = 2;
		$_REQUEST['ctf']['Tester']['start'] = 2;
		
		$items = $table->sourceItems();
		$this->assertNotNull($items);
	
		$itemMap = $items->map("ID", "A") ;
		$this->assertEquals(array($item3->ID => "a3", $item4->ID => "a4"), $itemMap->toArray());
	}
	
	function testSelectOptionsAddRemove() {
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
		));
		$this->assertNull($table->SelectOptions(), 'Empty by default');
		
		$table->addSelectOptions(array("F"=>"FieldF", 'G'=>'FieldG'));
		$this->assertEquals($table->SelectOptions()->map('Key', 'Value'), array("F"=>"FieldF",'G'=>'FieldG'));
		
		$table->removeSelectOptions(array("F"));
		$this->assertEquals($table->SelectOptions()->map('Key', 'Value'), array("G"=>"FieldG"));		
	}
	
	function testSelectOptionsRendering() {
		$obj1 = $this->objFromFixture('TableListFieldTest_Obj', 'one');
		$obj2 = $this->objFromFixture('TableListFieldTest_Obj', 'two');
		$obj3 = $this->objFromFixture('TableListFieldTest_Obj', 'three');
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
		));
		$table->Markable = true;
		
		$table->addSelectOptions(array("F"=>"FieldF"));
		$tableHTML = $table->FieldHolder();
		$p = new CSSContentParser($tableHTML);
		$this->assertContains('rel="F"', $tableHTML);
		$tbody = $p->getByXpath('//tbody');
		$this->assertContains('markingcheckbox F', (string)$tbody[0]->tr[0]->td[0]['class']);
		$this->assertContains('markingcheckbox', (string)$tbody[0]->tr[1]->td[0]['class']);
		$this->assertContains('markingcheckbox F', (string)$tbody[0]->tr[2]->td[0]['class']);
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
        $this->assertRegExp('/<th[^>]*>.*Col A.*<\/th>/si', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>.*Col B.*<\/th>/si', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>.*Col C.*<\/th>/si', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>.*Col D.*<\/th>/si', $ajaxResponse);
        $this->assertRegExp('/<th[^>]*>.*Col E.*<\/th>/si', $ajaxResponse);
	}
	
	function testCsvExport() {
		$table = new TableListField("Tester", "TableListFieldTest_CsvExport", array(
			"A" => "Col A",
			"B" => "Col B"
		));
		
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			$table
		), new FieldList());
		
		$csvResponse = $table->export();
		
		$csvOutput = $csvResponse->getBody();
		
		$this->assertNotEquals($csvOutput, false);
		
		// Create a temporary file and write the CSV to it.
		$csvFileName = tempnam(TEMP_FOLDER, 'csv-export');
		$csvFile = fopen($csvFileName, 'wb');
		fwrite($csvFile, $csvOutput);
		fclose($csvFile);
		
		$csvFile = fopen($csvFileName, 'rb');
		$csvRow = fgetcsv($csvFile);
		$this->assertEquals(
			$csvRow,
			array('Col A', 'Col B')
		);
		
		// fgetcsv doesn't handle escaped quotes in the string in PHP 5.2, so we're asserting the
		// raw string instead.
		$this->assertEquals(
			'"\"A field, with a comma\"","A second field"',
			trim(fgets($csvFile))
		);
		
		fclose($csvFile);
		
		unlink($csvFileName);
	}

	function testLink() {
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			new TableListField("Tester", "TableListFieldTest_Obj", array(
				"A" => "Col A",
				"B" => "Col B",
				"C" => "Col C",
				"D" => "Col D",
				"E" => "Col E",
			))
		), new FieldList());

		$table = $form->Fields()->dataFieldByName('Tester');
		$this->assertEquals(
			$table->Link('test'),
			sprintf('TableListFieldTest_TestController/TestForm/field/Tester/test?SecurityID=%s', $form->Fields()->dataFieldByName('SecurityID')->Value())
		);
	}

	function testPreservedSortOptionsInPaginationLink() {
		$item1 = $this->objFromFixture('TableListFieldTest_Obj', 'one');
		$item2 = $this->objFromFixture('TableListFieldTest_Obj', 'two');
		$item3 = $this->objFromFixture('TableListFieldTest_Obj', 'three');
		$item4 = $this->objFromFixture('TableListFieldTest_Obj', 'four');
		$item5 = $this->objFromFixture('TableListFieldTest_Obj', 'five');
		
		/* With pagination enabled, only the first page of items should be shown */
		$table = new TableListField("Tester", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			$table
		), new FieldList());

		$table->ShowPagination = true;
		$table->PageSize = 2;
		
		// first page & sort A column by ASC
		$_REQUEST['ctf']['Tester']['start'] = 0; 
		$_REQUEST['ctf']['Tester']['sort'] = 'A';
		$this->assertContains('&ctf[Tester][sort]=A', $table->NextLink());
		$this->assertNotContains('ctf[Tester][dir]', $table->NextLink());
		$this->assertContains('&ctf[Tester][sort]=A', $table->LastLink());
		$this->assertNotContains('ctf[Tester][dir]', $table->LastLink());
		
		// second page & sort A column by ASC
		$_REQUEST['ctf']['Tester']['start'] = 2; 
		$this->assertContains('&ctf[Tester][sort]=A', $table->PrevLink());
		$this->assertNotContains('&ctf[Tester][dir]', $table->PrevLink());
		$this->assertContains('&ctf[Tester][sort]=A', $table->FirstLink());
		$this->assertNotContains('&ctf[Tester][dir]', $table->FirstLink());

		// first page & sort A column by DESC
		$_REQUEST['ctf']['Tester']['start'] = 0; 
		$_REQUEST['ctf']['Tester']['sort'] = 'A';
		$_REQUEST['ctf']['Tester']['dir'] = 'desc';
		$this->assertContains('&ctf[Tester][sort]=A', $table->NextLink());
		$this->assertContains('&ctf[Tester][dir]=desc', $table->NextLink());
		$this->assertContains('&ctf[Tester][sort]=A', $table->LastLink());
		$this->assertContains('&ctf[Tester][dir]=desc', $table->LastLink());
		
		// second page & sort A column by DESC
		$_REQUEST['ctf']['Tester']['start'] = 2; 
		$this->assertContains('&ctf[Tester][sort]=A', $table->PrevLink());
		$this->assertContains('&ctf[Tester][dir]=desc', $table->PrevLink());
		$this->assertContains('&ctf[Tester][sort]=A', $table->FirstLink());
		$this->assertContains('&ctf[Tester][dir]=desc', $table->FirstLink());
		
		unset($_REQUEST['ctf']);
	}

    /**
     * Check that a SS_List can be passed to TableListField
     */
	function testDataObjectSet() {
	    $one = new TableListFieldTest_Obj;
	    $one->A = "A-one";
	    $two = new TableListFieldTest_Obj;
	    $two->A = "A-two";
	    $three = new TableListFieldTest_Obj;
	    $three->A = "A-three";
	    
	    $list = new ArrayList(array($one, $two, $three));
	    
		// A TableListField must be inside a form for its links to be generated
		$form = new Form(new TableListFieldTest_TestController(), "TestForm", new FieldList(
			new TableListField("Tester", $list, array(
				"A" => "Col A",
				"B" => "Col B",
				"C" => "Col C",
				"D" => "Col D",
				"E" => "Col E",
			))
		), new FieldList());

		$table = $form->Fields()->dataFieldByName('Tester');
		$rendered = $table->FieldHolder();
		
		$this->assertContains('A-one', $rendered);
		$this->assertContains('A-two', $rendered);
		$this->assertContains('A-three', $rendered);
	}
}

class TableListFieldTest_Obj extends DataObject implements TestOnly {
	static $db = array(
		"A" => "Varchar",
		"B" => "Varchar",
		"C" => "Varchar",
		"F" => "Boolean",
	);
	static $default_sort = "A";
	
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
	static $default_sort = "A";
}

class TableListFieldTest_TestController extends Controller {
	function Link($action = null) {
		return Controller::join_links("TableListFieldTest_TestController/", $action);
	}
	function TestForm() {
		$table = new TableListField("Table", "TableListFieldTest_Obj", array(
			"A" => "Col A",
			"B" => "Col B",
			"C" => "Col C",
			"D" => "Col D",
			"E" => "Col E",
		));
		$table->disableSorting();

		// A TableListField must be inside a form for its links to be generated
		return new Form($this, "TestForm", new FieldList(
			$table
		), new FieldList());
	}
}
