<?php
/**
 * @package framework
 * @subpackage tests
 */
class TreeDropdownFieldTest extends SapphireTest {

	protected static $fixture_file = 'TreeDropdownFieldTest.yml';

	public function testTreeSearch(){

		$field = new TreeDropdownField('TestTree', 'Test tree', 'Folder');

		// case insensitive search against keyword 'sub' for folders
		$request = new SS_HTTPRequest('GET','url',array('search'=>'sub'));
		$tree = $field->tree($request);
		
		$folder1 = $this->objFromFixture('Folder','folder1');
		$folder1Subfolder1 = $this->objFromFixture('Folder','folder1-subfolder1');

		$parser = new CSSContentParser($tree);
		$cssPath = 'ul.tree li#selector-TestTree-'.$folder1->ID.' li#selector-TestTree-'.$folder1Subfolder1->ID.' a span.item';
		$firstResult = $parser->getBySelector($cssPath);
		$this->assertEquals(
			(string)$firstResult[0], 
			$folder1Subfolder1->Name, 
			$folder1Subfolder1->Name.' is found, nested under '.$folder1->Name
		);

		$subfolder = $this->objFromFixture('Folder','subfolder');
		$cssPath = 'ul.tree li#selector-TestTree-'.$subfolder->ID.' a span.item';
		$secondResult = $parser->getBySelector($cssPath);
		$this->assertEquals(
			(string)$secondResult[0], 
			$subfolder->Name, 
			$subfolder->Name.' is found at root level'
		);

		// other folders which don't contain the keyword 'sub' are not returned in search results
		$folder2 = $this->objFromFixture('Folder','folder2');
		$cssPath = 'ul.tree li#selector-TestTree-'.$folder2->ID.' a span.item';
		$noResult = $parser->getBySelector($cssPath);
		$this->assertEquals(
			$noResult, 
			array(), 
			$folder2.' is not found'
		);

		$field = new TreeDropdownField('TestTree', 'Test tree', 'File');

		// case insensitive search against keyword 'sub' for files
		$request = new SS_HTTPRequest('GET','url',array('search'=>'sub'));
		$tree = $field->tree($request);

		$parser = new CSSContentParser($tree);

		// Even if we used File as the source object, folders are still returned because Folder is a File
		$cssPath = 'ul.tree li#selector-TestTree-'.$folder1->ID.' li#selector-TestTree-'.$folder1Subfolder1->ID.' a span.item';
		$firstResult = $parser->getBySelector($cssPath);
		$this->assertEquals(
			(string)$firstResult[0], 
			$folder1Subfolder1->Name, 
			$folder1Subfolder1->Name.' is found, nested under '.$folder1->Name
		);

		// Looking for two files with 'sub' in their name, both under the same folder
		$file1 = $this->objFromFixture('File','subfolderfile1');
		$file2 = $this->objFromFixture('File','subfolderfile2');
		$cssPath = 'ul.tree li#selector-TestTree-'.$subfolder->ID.' li#selector-TestTree-'.$file1->ID.' a';
		$firstResult = $parser->getBySelector($cssPath);
		$this->assertGreaterThan(
			0, 
			count($firstResult), 
			$file1->Name.' with ID '.$file1->ID.' is in search results'
		);
		$this->assertEquals(
			(string)$firstResult[0], 
			$file1->Name, 
			$file1->Name.' is found nested under '.$subfolder->Name
		);

		$cssPath = 'ul.tree li#selector-TestTree-'.$subfolder->ID.' li#selector-TestTree-'.$file2->ID.' a';
		$secondResult = $parser->getBySelector($cssPath);
		$this->assertGreaterThan(
			0,
			count($secondResult), 
			$file2->Name.' with ID '.$file2->ID.' is in search results'
		);
		$this->assertEquals(
			(string)$secondResult[0], 
			$file2->Name, 
			$file2->Name.' is found nested under '.$subfolder->Name
		);

		// other files which don't include 'sub' are not returned in search results
		$file3 = $this->objFromFixture('File','asdf');
		$cssPath = 'ul.tree li#selector-TestTree-'.$file3->ID;
		$noResult = $parser->getBySelector($cssPath);
		$this->assertEquals(
			$noResult, 
			array(), 
			$file3->Name.' is not found'
		);
	}

}
	