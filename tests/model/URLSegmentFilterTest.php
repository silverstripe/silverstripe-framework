<?php
/**
 * @package framework
 * @subpackage tests
 */
class URLSegmentFilterTest extends SapphireTest {
	
	public function testReplacesCommonEnglishSymbols() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(false);
		$this->assertEquals(
			'john-and-spencer', 
			$f->filter('John & Spencer')
		);
	}
	
	public function testTransliteratesNonAsciiUrls() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(false);
		$this->assertEquals(
			'broetchen', 
			$f->filter('Brötchen')
		);
	}
	
	public function testRetainsNonAsciiUrlsWithAllowMultiByteOption() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(true);
		$this->assertEquals(
			urlencode('brötchen'), 
			$f->filter('Brötchen')
		);
	}

	public function testReplacements() {
		$f = new URLSegmentFilter();
		$this->assertEquals(
			'tim-and-struppi', 
			$f->filter('Tim&Struppi')
		);

		// Customize replacements
		$rs = $f->getReplacements();
		$rs['/&/u'] = '-und-';
		$f->setReplacements($rs);
		$this->assertEquals(
			'tim-und-struppi', 
			$f->filter('Tim&Struppi')
		);
	}
	
}
