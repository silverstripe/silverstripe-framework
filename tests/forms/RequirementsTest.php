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
		
		$combinedFilePath = Director::baseFolder() . '/' . 'RequirementsTest_bc.js';
		
		$html = Requirements::includeInHTML(false, self::$html_template);

		/* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_bc\.js/', $html), 'combined javascript file is included in html header');
		
		/* COMBINED JAVASCRIPT FILE EXISTS */
		$this->assertTrue(file_exists($combinedFilePath), 'combined javascript file exists');
		
		/* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false), 'combined javascript has correct content');
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('c')") !== false), 'combined javascript has correct content');
		
		/* COMBINED FILES ARE NOT INCLUDED TWICE */
		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_b\.js/', $html), 'combined files are not included twice');
		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_c\.js/', $html), 'combined files are not included twice');
		
		/* NORMAL REQUIREMENTS ARE STILL INCLUDED */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_a\.js/', $html), 'normal requirements are still included');

		Requirements::delete_combined_files('RequirementsTest_bc.js');
	}
	
	function testBlockedCombinedJavascript() {
		$combinedFilePath = Director::baseFolder() . '/' . 'RequirementsTest_bc.js';

		/* BLOCKED COMBINED FILES ARE NOT INCLUDED */
		$this->setupCombinedRequirements();
		Requirements::block('RequirementsTest_bc.js');
		Requirements::delete_combined_files('RequirementsTest_bc.js');

		clearstatcache(); // needed to get accurate file_exists() results
		$html = Requirements::includeInHTML(false, self::$html_template);

		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_bc\.js/', $html), 'blocked combined files are not included ');
		Requirements::unblock('RequirementsTest_bc.js');

		/* BLOCKED UNCOMBINED FILES ARE NOT INCLUDED */
		// need to re-add requirements, as Requirements::process_combined_includes() alters the
		// original arrays grml...
		$this->setupCombinedRequirements();
		Requirements::block('sapphire/tests/forms/RequirementsTest_b.js');
		Requirements::delete_combined_files('RequirementsTest_bc.js');
		clearstatcache(); // needed to get accurate file_exists() results
		$html = Requirements::includeInHTML(false, self::$html_template);
		$this->assertFalse((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false), 'blocked uncombined files are not included');
		Requirements::unblock('RequirementsTest_b.js');
		
		/* A SINGLE FILE CAN'T BE INCLUDED IN TWO COMBINED FILES */
		$this->setupCombinedRequirements();
		clearstatcache(); // needed to get accurate file_exists() results

		// This throws a notice-level error, so we prefix with @
		@Requirements::combine_files(
			'RequirementsTest_ac.js',
			array(
				'sapphire/tests/forms/RequirementsTest_a.js',
				'sapphire/tests/forms/RequirementsTest_c.js'
			)
		);

		$combinedFiles = Requirements::get_combine_files();
		$this->assertEquals(
			array_keys($combinedFiles),
			array('RequirementsTest_bc.js'),
			"A single file can't be included in two combined files"
		);
		
		Requirements::delete_combined_files('RequirementsTest_bc.js');
	}
	
	function testArgsInUrls() {
		// Clear previous requirements
		Requirements::clear();

		// clearing all previously generated requirements (just in case)
		Requirements::clear_combined_files();
		Requirements::delete_combined_files('RequirementsTest_bc.js'); 

		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/RequirementsTest_a.js?test=1&test=2&test=3');
		Requirements::css(SAPPHIRE_DIR . '/tests/forms/RequirementsTest_a.css?test=1&test=2&test=3');

		$html = Requirements::includeInHTML(false, self::$html_template);

		/* Javascript has correct path */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_a\.js\?m=\d\d+&test=1&test=2&test=3/', $html), 'javascript has correct path'); 

		/* CSS has correct path */
		$this->assertTrue((bool)preg_match('/href=".*\/RequirementsTest_a\.css\?m=\d\d+&test=1&test=2&test=3/', $html), 'css has correct path'); 
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
		Requirements::delete_combined_files('RequirementsTest_bc.js');
		
		// require files normally (e.g. called from a FormField instance)
		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/RequirementsTest_a.js');
		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/RequirementsTest_b.js');
		Requirements::javascript(SAPPHIRE_DIR . '/tests/forms/RequirementsTest_c.js');
		
		// require two of those files as combined includes
		Requirements::combine_files(
			'RequirementsTest_bc.js',
			array(
				SAPPHIRE_DIR . '/tests/forms/RequirementsTest_b.js',
				SAPPHIRE_DIR . '/tests/forms/RequirementsTest_c.js'
			)
		);
	}
	
	function testRequirementsBackend() {
		$requirements = new Requirements_Backend();
		$requirements->javascript(SAPPHIRE_DIR . '/tests/forms/a.js');
		
		$this->assertTrue(count($requirements->get_javascript()) == 1, "There should be only 1 file included in required javascript.");
		$this->assertTrue(in_array(SAPPHIRE_DIR . '/tests/forms/a.js', $requirements->get_javascript()), "/test/forms/a.js should be included in required javascript.");
		
		$requirements->javascript(SAPPHIRE_DIR . '/tests/forms/b.js');
		$this->assertTrue(count($requirements->get_javascript()) == 2, "There should be 2 files included in required javascript.");
		
		$requirements->block(SAPPHIRE_DIR . '/tests/forms/a.js');
		$this->assertTrue(count($requirements->get_javascript()) == 1, "There should be only 1 file included in required javascript.");
		$this->assertFalse(in_array(SAPPHIRE_DIR . '/tests/forms/a.js', $requirements->get_javascript()), "/test/forms/a.js should not be included in required javascript after it has been blocked.");
		$this->assertTrue(in_array(SAPPHIRE_DIR . '/tests/forms/b.js', $requirements->get_javascript()), "/test/forms/b.js should be included in required javascript.");
		
		$requirements->css(SAPPHIRE_DIR . '/tests/forms/a.css');
		$this->assertTrue(count($requirements->get_css()) == 1, "There should be only 1 file included in required css.");
		$this->assertArrayHasKey(SAPPHIRE_DIR . '/tests/forms/a.css', $requirements->get_css(), "/tests/forms/a.css should be in required css.");
		
		$requirements->block(SAPPHIRE_DIR . '/tests/forms/a.css');
		$this->assertTrue(count($requirements->get_css()) == 0, "There should be nothing in required css after file has been blocked.");
		
		
		
	}
	
	
}
?>