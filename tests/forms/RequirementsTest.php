<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo Test that order of combine_files() is correct
 */
class RequirementsTest extends SapphireTest {
	
	static $html_template = '<html><head></head><body></body></html>';
	
	function testCombinedJavascript() {
		// require files normally (e.g. called from a FormField instance)
		Requirements::javascript('sapphire/tests/forms/a.js');
		Requirements::javascript('sapphire/tests/forms/b.js');
		Requirements::javascript('sapphire/tests/forms/c.js');
		
		// require two of those files as combined includes
		Requirements::combine_files(
			'bc.js',
			array(
				'sapphire/tests/forms/b.js',
				'sapphire/tests/forms/c.js'
			)
		);
		
		$combinedFilePath = Director::baseFolder() . '/' . 'bc.js';
		
		$html = Requirements::includeInHTML(false, self::$html_template);
		
		/* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
		$this->assertTrue((bool)preg_match('/src=".*\/bc\.js"/', $html));
		
		/* COMBINED JAVASCRIPT FILE EXISTS */
		$this->assertTrue(file_exists($combinedFilePath));
		
		/* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false));
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('c')") !== false));
		
		/* COMBINED FILES ARE NOT INCLUDED TWICE */
		$this->assertFalse((bool)preg_match('/src=".*\/b\.js"/', $html));
		$this->assertFalse((bool)preg_match('/src=".*\/c\.js"/', $html));
		
		/* NORMAL REQUIREMENTS ARE STILL INCLUDED */
		$this->assertTrue((bool)preg_match('/src=".*\/a\.js"/', $html));
		
		unlink($combinedFilePath);
		
	}
	
}
?>