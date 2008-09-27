<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo Test that order of combine_files() is correct
 * @todo Figure out how to clear the modified state of Requirements class - might affect other tests.
 */
class RequirementsTest extends SapphireTest {
	
	static $html_template = '<html><head></head><body></body></html>';
	
	function testExternalUrls() {
		Requirements::javascript('http://www.mydomain.com/test.js');
		Requirements::javascript('https://www.mysecuredomain.com/test.js');
		Requirements::css('http://www.mydomain.com/test.css');
		Requirements::css('https://www.mysecuredomain.com/test.css');
		
		$html = Requirements::includeInHTML(false, self::$html_template);
		
		$this->assertTrue(
			(strpos($html, 'http://www.mydomain.com/test.js') !== false),
			'Load external javascript URL'
		);
		$this->assertTrue(
			(strpos($html, 'https://www.mysecuredomain.com/test.js') !== false), 
			'Load external secure javascript URL'
		);
		$this->assertTrue(
			(strpos($html, 'http://www.mydomain.com/test.css') !== false), 
			'Load external CSS URL'
		);
		$this->assertTrue(
			(strpos($html, 'https://www.mysecuredomain.com/test.css') !== false), 
			'Load external secure CSS URL'
		);
	}
	
	function testCombinedJavascript() {
		$this->setupCombinedRequirements();
		
		$combinedFilePath = Director::baseFolder() . '/' . 'bc.js';
		
		$html = Requirements::includeInHTML(false, self::$html_template);

		/* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
		$this->assertTrue((bool)preg_match('/src=".*\/bc\.js/', $html), 'combined javascript file is included in html header');
		
		/* COMBINED JAVASCRIPT FILE EXISTS */
		$this->assertTrue(file_exists($combinedFilePath), 'combined javascript file exists');
		
		/* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false), 'combined javascript has correct content');
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('c')") !== false), 'combined javascript has correct content');
		
		/* COMBINED FILES ARE NOT INCLUDED TWICE */
		$this->assertFalse((bool)preg_match('/src=".*\/b\.js/', $html), 'combined files are not included twice');
		$this->assertFalse((bool)preg_match('/src=".*\/c\.js/', $html), 'combined files are not included twice');
		
		/* NORMAL REQUIREMENTS ARE STILL INCLUDED */
		$this->assertTrue((bool)preg_match('/src=".*\/a\.js/', $html), 'normal requirements are still included');

		Requirements::delete_combined_files('bc.js');
	}
	
	function testBlockedCombinedJavascript() {
		$combinedFilePath = Director::baseFolder() . '/' . 'bc.js';

		/* BLOCKED COMBINED FILES ARE NOT INCLUDED */
		$this->setupCombinedRequirements();
		Requirements::block('bc.js');
		Requirements::delete_combined_files('bc.js');

		clearstatcache(); // needed to get accurate file_exists() results
		$html = Requirements::includeInHTML(false, self::$html_template);

		$this->assertFalse((bool)preg_match('/src=".*\/bc\.js/', $html), 'blocked combined files are not included ');
		Requirements::unblock('bc.js');

		/* BLOCKED UNCOMBINED FILES ARE NOT INCLUDED */
		// need to re-add requirements, as Requirements::process_combined_includes() alters the
		// original arrays grml...
		$this->setupCombinedRequirements();
		Requirements::block('sapphire/tests/forms/b.js');
		Requirements::delete_combined_files('bc.js');
		clearstatcache(); // needed to get accurate file_exists() results
		$html = Requirements::includeInHTML(false, self::$html_template);
		$this->assertFalse((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false), 'blocked uncombined files are not included');
		Requirements::unblock('b.js');
		
		/* A SINGLE FILE CAN'T BE INCLUDED IN TWO COMBINED FILES */
		$this->setupCombinedRequirements();
		clearstatcache(); // needed to get accurate file_exists() results

		// This throws a notice-level error, so we prefix with @
		@Requirements::combine_files(
			'ac.js',
			array(
				'sapphire/tests/forms/a.js',
				'sapphire/tests/forms/c.js'
			)
		);

		$combinedFiles = Requirements::get_combine_files();
		$this->assertEquals(
			array_keys($combinedFiles),
			array('bc.js'),
			"A single file can't be included in two combined files"
		);
		
		Requirements::delete_combined_files('bc.js');
	}
	
	/**
	 * This is a bit of a hack, as it alters the Requirements
	 * statics globally for all tests.
	 * 
	 * @todo Refactor Requirements to work on test instance level
	 */
	protected function setupCombinedRequirements() {
		Requirements::clear();
		
		// clearing all previously generated requirements (just in case)
		Requirements::clear_combined_files();
		Requirements::delete_combined_files('bc.js');
		
		// require files normally (e.g. called from a FormField instance)
		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/a.js');
		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/b.js');
		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/c.js');
		
		// require two of those files as combined includes
		Requirements::combine_files(
			'bc.js',
			array(
				SAPPHIRE_DIR . '/tests/forms/b.js',
				SAPPHIRE_DIR . '/tests/forms/c.js'
			)
		);
	}
	
}
?>