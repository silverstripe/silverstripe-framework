<?php
/**
 * @package framework
 * @subpackage tests
 */
class FileNameFilterTest extends SapphireTest {
	
	public function testFilter() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$this->assertEquals(
			'Brtchen-fr-all-mit-Unterstrich.jpg', 
			$filter->filter($name)
		);
	}
	
	public function testFilterWithTransliterator() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(new SS_Transliterator());
		$this->assertEquals(
			'Broetchen-fuer-alle-mit-Unterstrich.jpg', 
			$filter->filter($name)
		);
	}
	
	public function testFilterWithCustomRules() {
		$name = 'Kuchen ist besser.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$filter->setReplacements(array('/[\s-]/' => '_'));
		$this->assertEquals(
			'Kuchen_ist_besser.jpg', 
			$filter->filter($name)
		);
	}
	
	public function testFilterWithEmptyString() {
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

	function testUnderscoresStartOfNameRemoved() {
		$name = '_test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	function testDoubleUnderscoresStartOfNameRemoved() {
		$name = '__test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	function testDotsStartOfNameRemoved() {
		$name = '.test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	function testDoubleDotsStartOfNameRemoved() {
		$name = '..test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	function testMixedInvalidCharsStartOfNameRemoved() {
		$name = '..#@$#@$^__test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	function testWhitespaceRemoved() {
		$name = ' test doc.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-doc.txt', $filter->filter($name));
	}

	function testUnderscoresReplacedWithDashes() {
		$name = 'test_doc.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-doc.txt', $filter->filter($name));
	}

	function testNonAsciiCharsReplacedWithDashes() {
		$name = '!@#$%^test_123@##@$#%^.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-123.txt', $filter->filter($name));
	}

	function testDuplicateDashesRemoved() {
		$name = 'test--document.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-document.txt', $filter->filter($name));
	}

}
