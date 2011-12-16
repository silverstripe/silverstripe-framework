<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class URLSegmentFilterTest extends SapphireTest {
	
	function testReplacesCommonEnglishSymbols() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(false);
		$this->assertEquals(
			'john-and-spencer', 
			$f->filter('John & Spencer')
		);
	}
	
	function testTransliteratesNonAsciiUrls() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(false);
		$this->assertEquals(
			'broetchen', 
			$f->filter('Brötchen')
		);
	}
	
	function testRetainsNonAsciiUrlsWithAllowMultiByteOption() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(true);
		$this->assertEquals(
			'brötchen', 
			$f->filter('Brötchen')
		);
	}
	
}