<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class FileNameFilterTest extends SapphireTest {
	
	function testFilter() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$this->assertEquals(
			'Brtchen-fr-all-mit-Unterstrich.jpg', 
			$filter->filter($name)
		);
	}
	
	function testFilterWithTransliterator() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(Object::create('Transliterator'));
		$this->assertEquals(
			'Broetchen-fuer-alle-mit-Unterstrich.jpg', 
			$filter->filter($name)
		);
	}
	
	function testFilterWithCustomRules() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$filter->setReplacements(array('/[\s-]/' => '_'));
		$this->assertEquals(
			'Brötchen__für_allë_mit_Unterstrich!.jpg', 
			$filter->filter($name)
		);
	}
	
	function testFilterWithEmptyString() {
		$name = 'ö ö ö.jpg';
		$filter = new FileNameFilter();
		$result = $filter->filter($name);
		$this->assertFalse(
			empty($result)
		);
		$this->assertStringEndsWith(
			'.jpg', 
			$result
		);
		$this->assertGreaterThan(
			strlen('.jpg'), 
			strlen($result)
		);
	}
	
}