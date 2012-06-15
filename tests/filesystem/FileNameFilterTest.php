<?php
/**
 * @package framework
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
		$filter->setTransliterator(new SS_Transliterator());
		$this->assertEquals(
			'Broetchen-fuer-alle-mit-Unterstrich.jpg', 
			$filter->filter($name)
		);
	}
	
	function testFilterWithCustomRules() {
		$name = 'Kuchen ist besser.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$filter->setReplacements(array('/[\s-]/' => '_'));
		$this->assertEquals(
			'Kuchen_ist_besser.jpg', 
			$filter->filter($name)
		);
	}
	
	function testFilterWithEmptyString() {
		$name = 'ö ö ö.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(new SS_Transliterator());
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
