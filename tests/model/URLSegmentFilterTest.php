<?php
/**
 * @package framework
 * @subpackage tests
 */
class URLSegmentFilterTest extends SapphireTest {
	
	protected $usesDatabase = false;
	
	public function testReplacesCommonEnglishSymbols() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(false);
		$this->assertEquals(
			'john-and-spencer', 
			$f->filter('John & Spencer')
		);
	}
	
	public function testReplacesWhitespace() {
		$f = new URLSegmentFilter();
		$f->setAllowMultibyte(false);
		$this->assertEquals(
			'john-and-spencer', 
			$f->filter('John and Spencer')
		);
		$this->assertEquals(
			'john-and-spencer', 
			$f->filter('John+and+Spencer')
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

	public function testReplacesCommonNonAsciiCharacters() {
		$f = new URLSegmentFilter();
		$this->assertEquals(
			urlencode('aa1-'),
			$f->filter('Aa1~!@#$%^*()_`-=;\':"[]\{}|,./<>?')
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

	public function testReplacesDots() {
		$filter = new URLSegmentFilter();
		$this->assertEquals('url-contains-dot', $filter->filter('url-contains.dot'));
	}

}
