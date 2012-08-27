<?php
/**
 * @package framework
 * @subpackage tests
 * 
 * @todo Test that order of combine_files() is correct
 * @todo Figure out how to clear the modified state of Requirements class - might affect other tests.
 */
class RequirementsTest extends SapphireTest {
	
	static $html_template = '<html><head></head><body></body></html>';
	
	static $old_requirements = null;
	
	function testExternalUrls() {
		$backend = new Requirements_Backend;
		$backend->set_combined_files_enabled(true);

		$backend->javascript('http://www.mydomain.com/test.js');
		$backend->javascript('https://www.mysecuredomain.com/test.js');
		$backend->javascript('//scheme-relative.example.com/test.js');
		$backend->css('http://www.mydomain.com/test.css');
		$backend->css('https://www.mysecuredomain.com/test.css');
		$backend->css('//scheme-relative.example.com/test.css');
		
		$html = $backend->includeInHTML(false, self::$html_template);
		
		$this->assertTrue(
			(strpos($html, 'http://www.mydomain.com/test.js') !== false),
			'Load external javascript URL'
		);
		$this->assertTrue(
			(strpos($html, 'https://www.mysecuredomain.com/test.js') !== false), 
			'Load external secure javascript URL'
		);
		$this->assertTrue(
			(strpos($html, '//scheme-relative.example.com/test.js') !== false), 
			'Load external scheme-relative javascript URL'
		);
		$this->assertTrue(
			(strpos($html, 'http://www.mydomain.com/test.css') !== false), 
			'Load external CSS URL'
		);
		$this->assertTrue(
			(strpos($html, 'https://www.mysecuredomain.com/test.css') !== false), 
			'Load external secure CSS URL'
		);
		$this->assertTrue(
			(strpos($html, '//scheme-relative.example.com/test.css') !== false), 
			'Load scheme-relative CSS URL'
		);
	}

	protected function setupCombinedRequirements($backend) {
		$basePath = $this->getCurrentRelativePath();
		
		$backend->clear();
		$backend->setCombinedFilesFolder('assets');

		// clearing all previously generated requirements (just in case)
		$backend->clear_combined_files();
		$backend->delete_combined_files('RequirementsTest_bc.js');

		// require files normally (e.g. called from a FormField instance)
		$backend->javascript($basePath . '/RequirementsTest_a.js');
		$backend->javascript($basePath . '/RequirementsTest_b.js');
		$backend->javascript($basePath . '/RequirementsTest_c.js');

		// require two of those files as combined includes
		$backend->combine_files(
			'RequirementsTest_bc.js',
			array(
				$basePath . '/RequirementsTest_b.js',
				$basePath . '/RequirementsTest_c.js'
			)
		);
	}
	
	protected function setupCombinedNonrequiredRequirements($backend) {
			$basePath = $this->getCurrentRelativePath();
		
			$backend->clear();
			$backend->setCombinedFilesFolder('assets');
	
			// clearing all previously generated requirements (just in case)
			$backend->clear_combined_files();
			$backend->delete_combined_files('RequirementsTest_bc.js');
	
			// require files as combined includes
			$backend->combine_files(
				'RequirementsTest_bc.js',
				array(
					$basePath . '/RequirementsTest_b.js',
					$basePath . '/RequirementsTest_c.js'
				)
			);
		}

	function testCombinedJavascript() {
		$backend = new Requirements_Backend;
		$backend->set_combined_files_enabled(true);
		$backend->setCombinedFilesFolder('assets');

		$this->setupCombinedRequirements($backend);
		
		$combinedFilePath = Director::baseFolder() . '/assets/' . 'RequirementsTest_bc.js';

		$html = $backend->includeInHTML(false, self::$html_template);

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

		$backend->delete_combined_files('RequirementsTest_bc.js');
		
		// Then do it again, this time not requiring the files beforehand
		$backend = new Requirements_Backend;
		$backend->set_combined_files_enabled(true);
		$backend->setCombinedFilesFolder('assets');

		$this->setupCombinedNonrequiredRequirements($backend);
		
		$combinedFilePath = Director::baseFolder() . '/assets/' . 'RequirementsTest_bc.js';

		$html = $backend->includeInHTML(false, self::$html_template);

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

		$backend->delete_combined_files('RequirementsTest_bc.js');
	}
	
	function testBlockedCombinedJavascript() {
		$basePath = $this->getCurrentRelativePath();
		
		$backend = new Requirements_Backend;
		$backend->set_combined_files_enabled(true);
		$backend->setCombinedFilesFolder('assets');
		$combinedFilePath = Director::baseFolder() . '/assets/' . 'RequirementsTest_bc.js';

		/* BLOCKED COMBINED FILES ARE NOT INCLUDED */
		$this->setupCombinedRequirements($backend);
		$backend->block('RequirementsTest_bc.js');
		$backend->delete_combined_files('RequirementsTest_bc.js');

		clearstatcache(); // needed to get accurate file_exists() results
		$html = $backend->includeInHTML(false, self::$html_template);

		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_bc\.js/', $html), 'blocked combined files are not included ');
		$backend->unblock('RequirementsTest_bc.js');

		/* BLOCKED UNCOMBINED FILES ARE NOT INCLUDED */
		$this->setupCombinedRequirements($backend);
		$backend->block($basePath .'/RequirementsTest_b.js');
		$backend->delete_combined_files('RequirementsTest_bc.js');
		clearstatcache(); // needed to get accurate file_exists() results
		$html = $backend->includeInHTML(false, self::$html_template);
		$this->assertFalse((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false), 'blocked uncombined files are not included');
		$backend->unblock('RequirementsTest_b.js');
		
		/* A SINGLE FILE CAN'T BE INCLUDED IN TWO COMBINED FILES */
		$this->setupCombinedRequirements($backend);
		clearstatcache(); // needed to get accurate file_exists() results

		// This throws a notice-level error, so we prefix with @
		@$backend->combine_files(
			'RequirementsTest_ac.js',
			array(
				$basePath . '/RequirementsTest_a.js',
				$basePath . '/RequirementsTest_c.js'
			)
		);

		$combinedFiles = $backend->get_combine_files();
		$this->assertEquals(
			array_keys($combinedFiles),
			array('RequirementsTest_bc.js'),
			"A single file can't be included in two combined files"
		);
		
		$backend->delete_combined_files('RequirementsTest_bc.js');
	}
	
	function testArgsInUrls() {
		$basePath = $this->getCurrentRelativePath();
		
		$backend = new Requirements_Backend;
		$backend->set_combined_files_enabled(true);

		$backend->javascript($basePath . '/RequirementsTest_a.js?test=1&test=2&test=3');
		$backend->css($basePath . '/RequirementsTest_a.css?test=1&test=2&test=3');
		$backend->delete_combined_files('RequirementsTest_bc.js');

		$html = $backend->includeInHTML(false, self::$html_template);

		/* Javascript has correct path */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_a\.js\?m=\d\d+&test=1&test=2&test=3/', $html), 'javascript has correct path'); 

		/* CSS has correct path */
		$this->assertTrue((bool)preg_match('/href=".*\/RequirementsTest_a\.css\?m=\d\d+&test=1&test=2&test=3/', $html), 'css has correct path'); 
	}
	
	function testRequirementsBackend() {
		$basePath = $this->getCurrentRelativePath();
		
		$backend = new Requirements_Backend();
		$backend->javascript($basePath . '/a.js');
		
		$this->assertTrue(count($backend->get_javascript()) == 1, "There should be only 1 file included in required javascript.");
		$this->assertTrue(in_array($basePath . '/a.js', $backend->get_javascript()), "a.js should be included in required javascript.");
		
		$backend->javascript($basePath . '/b.js');
		$this->assertTrue(count($backend->get_javascript()) == 2, "There should be 2 files included in required javascript.");
		
		$backend->block($basePath . '/a.js');
		$this->assertTrue(count($backend->get_javascript()) == 1, "There should be only 1 file included in required javascript.");
		$this->assertFalse(in_array($basePath . '/a.js', $backend->get_javascript()), "a.js should not be included in required javascript after it has been blocked.");
		$this->assertTrue(in_array($basePath . '/b.js', $backend->get_javascript()), "b.js should be included in required javascript.");
		
		$backend->css($basePath . '/a.css');
		$this->assertTrue(count($backend->get_css()) == 1, "There should be only 1 file included in required css.");
		$this->assertArrayHasKey($basePath . '/a.css', $backend->get_css(), "a.css should be in required css.");
		
		$backend->block($basePath . '/a.css');
		$this->assertTrue(count($backend->get_css()) == 0, "There should be nothing in required css after file has been blocked.");
	}

	function testConditionalTemplateRequire() {
		$basePath = $this->getCurrentRelativePath();
		// we're asserting "framework", so set the relative path accordingly in case FRAMEWORK_DIR was changed to something else
		$basePath = 'framework' . substr($basePath, strlen(FRAMEWORK_DIR));

		$backend = new RequirementsTest_Backend();
		$holder = Requirements::backend();
		Requirements::set_backend($backend);
		$data = new ArrayData(array(
			'FailTest' => true,
		));
		$data->renderWith('RequirementsTest_Conditionals');
		$backend->assertFileIncluded('css', $basePath .'/RequirementsTest_a.css');
		$backend->assertFileIncluded('js', array($basePath .'/RequirementsTest_b.js', $basePath .'/RequirementsTest_c.js'));
		$backend->assertFileNotIncluded('js', $basePath .'/RequirementsTest_a.js');
		$backend->assertFileNotIncluded('css', array($basePath .'/RequirementsTest_b.css', $basePath .'/RequirementsTest_c.css'));
		$backend->clear();
		$data = new ArrayData(array(
			'FailTest' => false,
		));
		$data->renderWith('RequirementsTest_Conditionals');
		$backend->assertFileNotIncluded('css', $basePath .'/RequirementsTest_a.css');
		$backend->assertFileNotIncluded('js', array($basePath .'/RequirementsTest_b.js', $basePath .'/RequirementsTest_c.js'));
		$backend->assertFileIncluded('js', $basePath .'/RequirementsTest_a.js');
		$backend->assertFileIncluded('css', array($basePath .'/RequirementsTest_b.css', $basePath .'/RequirementsTest_c.css'));
		Requirements::set_backend($holder);
	}

	function testJsWriteToBody() {
		$backend = new Requirements_Backend();
		$backend->javascript('http://www.mydomain.com/test.js');

		// Test matching with HTML5 <header> tags as well
		$template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';
		
		$backend->set_write_js_to_body(false);
		$html = $backend->includeInHTML(false, $template);
		$this->assertContains('<head><script', $html);

		$backend->set_write_js_to_body(true);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotContains('<head><script', $html);
		$this->assertContains('</script></body>', $html);

	}
}

class RequirementsTest_Backend extends Requirements_Backend implements TestOnly {
	function assertFileIncluded($type, $files) {
		$type = strtolower($type);
		switch (strtolower($type)) {
			case 'css':
				$var = 'css';
				$type = 'CSS';
				break;
			case 'js':
			case 'javascript':
			case 'script':
				$var = 'javascript';
				$type = 'JavaScript';
				break;
		}
		if(is_array($files)) {
			$failedMatches = array();
			foreach ($files as $file) {
				if(!array_key_exists($file, $this->$var)) {
					$failedMatches[] = $file;
				}
			}
			if(count($failedMatches) > 0) throw new PHPUnit_Framework_AssertionFailedError(
				"Failed asserting the $type files '"
				. implode("', '", $failedMatches)
				. "' have exact matches in the required elements:\n'"
				. implode("'\n'", array_keys($this->$var)) . "'"
			);
		} else {
			if(!array_key_exists($files, $this->$var)) {
				throw new PHPUnit_Framework_AssertionFailedError(
					"Failed asserting the $type file '$files' has an exact match in the required elements:\n'"
					. implode("'\n'", array_keys($this->$var)) . "'"
				);
			}
		}
	}
  	
	function assertFileNotIncluded($type, $files) {
		$type = strtolower($type);
		switch ($type) {
			case 'css':
				$var = 'css';
				$type = 'CSS';
				break;
			case 'js':
			case 'javascript':
			case 'script':
				$var = 'javascript';
				$type = 'JavaScript';
				break;
		}
		if(is_array($files)) {
			$failedMatches = array();
			foreach ($files as $file) {
				if(array_key_exists($file, $this->$var)) {
					$failedMatches[] = $file;
				}
			}
			if(count($failedMatches) > 0) throw new PHPUnit_Framework_AssertionFailedError(
				"Failed asserting the $type files '"
				. implode("', '", $failedMatches)
				. "' do not have exact matches in the required elements:\n'"
				. implode("'\n'", array_keys($this->$var)) . "'"
			);
		} else {
			if(array_key_exists($files, $this->$var)) {
				throw new PHPUnit_Framework_AssertionFailedError(
					"Failed asserting the $type file '$files' does not have an exact match in the required elements:\n'"
					. implode("'\n'", array_keys($this->$var)) . "'"
				);
			}
		}
	}
}
