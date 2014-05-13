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

	public function testExternalUrls() {
		Config::inst()->update('Requirements', 'combined_files_enabled', true);

		Requirements::javascript('http://www.mydomain.com/test.js');
		Requirements::javascript('https://www.mysecuredomain.com/test.js');
		Requirements::javascript('//scheme-relative.example.com/test.js');
		Requirements::css('http://www.mydomain.com/test.css');
		Requirements::css('https://www.mysecuredomain.com/test.css');
		Requirements::css('//scheme-relative.example.com/test.css');
		
		$backend = Injector::inst()->get('Requirements_Backend');
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

		// clearing all previously generated requirements (just in case)
		$backend->clearCombinedFiles();
		$backend->deleteCombinedFiles('RequirementsTest_bc.js');

		// require files normally (e.g. called from a FormField instance)
		$backend->javascript($basePath . '/RequirementsTest_a.js');
		$backend->javascript($basePath . '/RequirementsTest_b.js');
		$backend->javascript($basePath . '/RequirementsTest_c.js');

		// require two of those files as combined includes
		$backend->combineFiles(
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
	
			// clearing all previously generated requirements (just in case)
			$backend->clearCombinedFiles();
			$backend->deleteCombinedFiles('RequirementsTest_bc.js');
	
			// require files as combined includes
			$backend->combineFiles(
				'RequirementsTest_bc.js',
				array(
					$basePath . '/RequirementsTest_b.js',
					$basePath . '/RequirementsTest_c.js'
				)
			);
		}

	public function testCombinedJavascript() {
		Config::inst()->update('Requirements', 'combined_files_enabled', true);
		Config::inst()->update('Requirements', 'combined_files_folder', 'assets');
		
		$backend = new RequirementsHandler;
		$this->setupCombinedRequirements($backend);

		$combinedFilePath = Director::baseFolder() . '/assets/' . 'RequirementsTest_bc.js';

		$html = $backend->includeInHTML(false, self::$html_template);

		/* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_bc\.js/', $html),
			'combined javascript file is included in html header');

		/* COMBINED JAVASCRIPT FILE EXISTS */
		$this->assertTrue(file_exists($combinedFilePath),
			'combined javascript file exists');

		/* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false),
			'combined javascript has correct content');
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('c')") !== false),
			'combined javascript has correct content');

		/* COMBINED FILES ARE NOT INCLUDED TWICE */
		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_b\.js/', $html),
			'combined files are not included twice');
		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_c\.js/', $html),
			'combined files are not included twice');

		/* NORMAL REQUIREMENTS ARE STILL INCLUDED */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_a\.js/', $html),
			'normal requirements are still included');

		$backend->deleteCombinedFiles('RequirementsTest_bc.js');
		
		$this->setupCombinedNonrequiredRequirements($backend);

		$combinedFilePath = Director::baseFolder() . '/assets/' . 'RequirementsTest_bc.js';

		$html = $backend->includeInHTML(false, self::$html_template);

		/* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
		$this->assertTrue((bool)preg_match('/src=".*\/RequirementsTest_bc\.js/', $html),
			'combined javascript file is included in html header');

		/* COMBINED JAVASCRIPT FILE EXISTS */
		$this->assertTrue(file_exists($combinedFilePath),
			'combined javascript file exists');

		/* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false),
			'combined javascript has correct content');
		$this->assertTrue((strpos(file_get_contents($combinedFilePath), "alert('c')") !== false),
			'combined javascript has correct content');

		/* COMBINED FILES ARE NOT INCLUDED TWICE */
		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_b\.js/', $html),
			'combined files are not included twice');
		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_c\.js/', $html),
			'combined files are not included twice');

		$backend->deleteCombinedFiles('RequirementsTest_bc.js');
	}

	public function testCombinedCss() {
		Config::inst()->update('Requirements', 'combined_files_enabled', true);

		$basePath = $this->getCurrentRelativePath();
		$backend = new RequirementsHandler;

		$backend->combineFiles(
			'print.css',
			array(
				$basePath . '/RequirementsTest_print_a.css',
				$basePath . '/RequirementsTest_print_b.css'
			),
			'print'
		);

		$html = $backend->includeInHTML(false, self::$html_template);

		$this->assertTrue((bool)preg_match('/href=".*\/print\.css/', $html), 'Print stylesheets have been combined.');
		$this->assertTrue((bool)preg_match(
			'/media="print/', $html),
			'Combined print stylesheet retains the media parameter'
		);
	}

	public function testBlockedCombinedJavascript() {
		Config::inst()->update('Requirements', 'combined_files_enabled', true);
		Config::inst()->update('Requirements', 'combined_files_folder', 'assets');

		$basePath = $this->getCurrentRelativePath();
		
		$backend = new RequirementsHandler;
		$combinedFilePath = Director::baseFolder() . '/assets/' . 'RequirementsTest_bc.js';

		/* BLOCKED COMBINED FILES ARE NOT INCLUDED */
		$this->setupCombinedRequirements($backend);
		$backend->block('RequirementsTest_bc.js');
		$backend->deleteCombinedFiles('RequirementsTest_bc.js');

		clearstatcache(); // needed to get accurate file_exists() results
		$html = $backend->includeInHTML(false, self::$html_template);

		$this->assertFalse((bool)preg_match('/src=".*\/RequirementsTest_bc\.js/', $html),
			'blocked combined files are not included ');
		$backend->unblock('RequirementsTest_bc.js');

		/* BLOCKED UNCOMBINED FILES ARE NOT INCLUDED */
		$this->setupCombinedRequirements($backend);
		$backend->block($basePath .'/RequirementsTest_b.js');
		$backend->deleteCombinedFiles('RequirementsTest_bc.js');
		clearstatcache(); // needed to get accurate file_exists() results
		$html = $backend->includeInHTML(false, self::$html_template);
		$this->assertFalse((strpos(file_get_contents($combinedFilePath), "alert('b')") !== false),
			'blocked uncombined files are not included');
		$backend->unblock('RequirementsTest_b.js');

		/* A SINGLE FILE CAN'T BE INCLUDED IN TWO COMBINED FILES */
		$this->setupCombinedRequirements($backend);
		clearstatcache(); // needed to get accurate file_exists() results

		// This throws a notice-level error, so we prefix with @
		@$backend->combineFiles(
			'RequirementsTest_ac.js',
			array(
				$basePath . '/RequirementsTest_a.js',
				$basePath . '/RequirementsTest_c.js'
			)
		);

		$combinedFiles = $backend->getCombineFiles();
		$this->assertEquals(
			array_keys($combinedFiles),
			array('RequirementsTest_bc.js'),
			"A single file can't be included in two combined files"
		);
		
		$backend->deleteCombinedFiles('RequirementsTest_bc.js');
	}

	public function testArgsInUrls() {
		Config::inst()->update('Requirements', 'combined_files_enabled', true);
		$basePath = $this->getCurrentRelativePath();		
		$backend = new RequirementsHandler;

		$backend->javascript($basePath . '/RequirementsTest_a.js?test=1&test=2&test=3');
		$backend->css($basePath . '/RequirementsTest_a.css?test=1&test=2&test=3');
		$backend->deleteCombinedFiles('RequirementsTest_bc.js');

		$html = $backend->includeInHTML(false, self::$html_template);

		/* Javascript has correct path */
		$this->assertTrue(
			(bool)preg_match('/src=".*\/RequirementsTest_a\.js\?m=\d\d+&amp;test=1&amp;test=2&amp;test=3/',$html),
			'javascript has correct path');

		/* CSS has correct path */
		$this->assertTrue(
			(bool)preg_match('/href=".*\/RequirementsTest_a\.css\?m=\d\d+&amp;test=1&amp;test=2&amp;test=3/',$html),
			'css has correct path');
	}

	public function testRequirementsBackend() {
		$basePath = $this->getCurrentRelativePath();
		
		$backend = new RequirementsHandler();
		$backend->javascript($basePath . '/a.js');

		$this->assertTrue(count($backend->get_javascript()) == 1,
			"There should be only 1 file included in required javascript.");
		$this->assertTrue(in_array($basePath . '/a.js', $backend->get_javascript()),
			"a.js should be included in required javascript.");

		$backend->javascript($basePath . '/b.js');
		$this->assertTrue(count($backend->get_javascript()) == 2,
			"There should be 2 files included in required javascript.");

		$backend->block($basePath . '/a.js');
		$this->assertTrue(count($backend->get_javascript()) == 1,
			"There should be only 1 file included in required javascript.");
		$this->assertFalse(in_array($basePath . '/a.js', $backend->get_javascript()),
			"a.js should not be included in required javascript after it has been blocked.");
		$this->assertTrue(in_array($basePath . '/b.js', $backend->get_javascript()),
			"b.js should be included in required javascript.");

		$backend->css($basePath . '/a.css');
		$this->assertTrue(count($backend->get_css()) == 1,
			"There should be only 1 file included in required css.");
		$this->assertArrayHasKey($basePath . '/a.css', $backend->get_css(),
			"a.css should be in required css.");

		$backend->block($basePath . '/a.css');
		$this->assertTrue(count($backend->get_css()) == 0,
			"There should be nothing in required css after file has been blocked.");
	}

	public function testConditionalTemplateRequire() {
		$basePath = $this->getCurrentRelativePath();
		// we're asserting "framework", so set the relative path accordingly in case FRAMEWORK_DIR was changed
		// to something else
		$basePath = 'framework' . substr($basePath, strlen(FRAMEWORK_DIR));

		$backend = new RequirementsHandler();
		Injector::inst()->registerService($backend, 'Requirements_Backend');
		$data = new ArrayData(array(
			'FailTest' => true,
		));
		$data->renderWith('RequirementsTest_Conditionals');
		$this->assertFileIncluded($backend, 'css', $basePath .'/RequirementsTest_a.css');
		$this->assertFileIncluded($backend, 'js',
			array($basePath .'/RequirementsTest_b.js', $basePath .'/RequirementsTest_c.js'));
		$this->assertFileNotIncluded($backend, 'js', $basePath .'/RequirementsTest_a.js');
		$this->assertFileNotIncluded($backend, 'css',
			array($basePath .'/RequirementsTest_b.css', $basePath .'/RequirementsTest_c.css'));
		$backend->clear();
		$data = new ArrayData(array(
			'FailTest' => false,
		));
		$data->renderWith('RequirementsTest_Conditionals');
		$this->assertFileNotIncluded($backend, 'css', $basePath .'/RequirementsTest_a.css');
		$this->assertFileNotIncluded($backend, 'js',
			array($basePath .'/RequirementsTest_b.js', $basePath .'/RequirementsTest_c.js'));
		$this->assertFileIncluded($backend, 'js', $basePath .'/RequirementsTest_a.js');
		$this->assertFileIncluded($backend, 'css',
			array($basePath .'/RequirementsTest_b.css', $basePath .'/RequirementsTest_c.css'));
	}

	public function testJsWriteToBody() {
		$backend = new RequirementsHandler();
		$backend->javascript('http://www.mydomain.com/test.js');

		// Test matching with HTML5 <header> tags as well
		$template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';		

		Config::inst()->update('Requirements', 'write_js_to_body', false);
		$html = $backend->includeInHTML(false, $template);
		$this->assertContains('<head><script', $html);

		Config::inst()->update('Requirements', 'write_js_to_body', true);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotContains('<head><script', $html);
		$this->assertContains('</script></body>', $html);
	}

	public function testIncludedJsIsNotCommentedOut() {
		$template = '<html><head></head><body><!--<script>alert("commented out");</script>--></body></html>';
		$backend = new RequirementsHandler();
		$backend->javascript($this->getCurrentRelativePath() . '/RequirementsTest_a.js');
		$html = $backend->includeInHTML(false, $template);
		//wiping out commented-out html
		$html = preg_replace('/<!--(.*)-->/Uis', '', $html);
		$this->assertContains("RequirementsTest_a.js", $html);
	}

	public function testCommentedOutScriptTagIsIgnored() {
		$template = '<html><head></head><body><!--<script>alert("commented out");</script>-->'
			. '<h1>more content</h1></body></html>';
		$backend = new RequirementsHandler();
		Config::inst()->update('Requirements', 'suffix_requirements', false);
		$src = $this->getCurrentRelativePath() . '/RequirementsTest_a.js';
		$backend->javascript($src);
		$html = $backend->includeInHTML(false, $template);
		$this->assertEquals('<html><head></head><body><!--<script>alert("commented out");</script>-->'
			. '<h1>more content</h1><script type="text/javascript" src="/' . $src . '"></script></body></html>', $html);
	}

	public function testForceJsToBottom() {
		$backend = new RequirementsHandler();
		$backend->javascript('http://www.mydomain.com/test.js');

		// Test matching with HTML5 <header> tags as well
		$template = '<html><head></head><body><header>My header</header><p>Body<script></script></p></body></html>';

		// The expected outputs
		$JsInHead = "<html><head><script type=\"text/javascript\" src=\"http://www.mydomain.com/test.js\">"
			. "</script>\n</head><body><header>My header</header><p>Body<script></script></p></body></html>";
		$JsInBody = "<html><head></head><body><header>My header</header><p>Body<script type=\"text/javascript\""
			. " src=\"http://www.mydomain.com/test.js\"></script><script></script></p></body></html>";
		$JsAtEnd  = "<html><head></head><body><header>My header</header><p>Body<script></script></p><script "
			. "type=\"text/javascript\" src=\"http://www.mydomain.com/test.js\"></script></body></html>";


		// Test if the script is before the head tag, not before the body.
		// Expected: $JsInHead
		Config::inst()->update('Requirements', 'write_js_to_body', false);
		Config::inst()->update('Requirements', 'force_js_to_bottom', false);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotEquals($JsInBody, $html);
		$this->assertNotEquals($JsAtEnd, $html);
		$this->assertEquals($JsInHead, $html);

		// Test if the script is before the first <script> tag, not before the body.
		// Expected: $JsInBody
		Config::inst()->update('Requirements', 'write_js_to_body', true);
		Config::inst()->update('Requirements', 'force_js_to_bottom', false);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotEquals($JsAtEnd, $html);
		$this->assertEquals($JsInBody, $html);

		// Test if the script is placed just before the closing bodytag, with write-to-body false.
		// Expected: $JsAtEnd
		Config::inst()->update('Requirements', 'write_js_to_body', false);
		Config::inst()->update('Requirements', 'force_js_to_bottom', true);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotEquals($JsInHead, $html);
		$this->assertNotEquals($JsInBody, $html);
		$this->assertEquals($JsAtEnd, $html);

		// Test if the script is placed just before the closing bodytag, with write-to-body true.
		// Expected: $JsAtEnd
		Config::inst()->update('Requirements', 'write_js_to_body', true);
		Config::inst()->update('Requirements', 'force_js_to_bottom', true);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotEquals($JsInHead, $html);
		$this->assertNotEquals($JsInBody, $html);
		$this->assertEquals($JsAtEnd, $html);
	}

	public function testSuffix() {
		$template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';
		$basePath = $this->getCurrentRelativePath();

		$backend = new RequirementsHandler;

		$backend->javascript($basePath .'/RequirementsTest_a.js');
		$backend->javascript($basePath .'/RequirementsTest_b.js?foo=bar&bla=blubb');
		$backend->css($basePath .'/RequirementsTest_a.css');
		$backend->css($basePath .'/RequirementsTest_b.css?foo=bar&bla=blubb');

		Config::inst()->update('Requirements', 'suffix_requirements', true);
		$html = $backend->includeInHTML(false, $template);
		$this->assertRegexp('/RequirementsTest_a\.js\?m=[\d]*"/', $html);
		$this->assertRegexp('/RequirementsTest_b\.js\?m=[\d]*&amp;foo=bar&amp;bla=blubb"/', $html);
		$this->assertRegexp('/RequirementsTest_a\.css\?m=[\d]*"/', $html);
		$this->assertRegexp('/RequirementsTest_b\.css\?m=[\d]*&amp;foo=bar&amp;bla=blubb"/', $html);

		Config::inst()->update('Requirements', 'suffix_requirements', false);
		$html = $backend->includeInHTML(false, $template);
		$this->assertNotContains('RequirementsTest_a.js=', $html);
		$this->assertNotRegexp('/RequirementsTest_a\.js\?m=[\d]*"/', $html);
		$this->assertNotRegexp('/RequirementsTest_b\.js\?m=[\d]*&amp;foo=bar&amp;bla=blubb"/', $html);
		$this->assertNotRegexp('/RequirementsTest_a\.css\?m=[\d]*"/', $html);
		$this->assertNotRegexp('/RequirementsTest_b\.css\?m=[\d]*&amp;foo=bar&amp;bla=blubb"/', $html);
	}

	public function assertFileIncluded($backend, $type, $files) {
		$type = strtolower($type);
		switch (strtolower($type)) {
			case 'css':
				$method = 'get_css';
				$type = 'CSS';
				break;
			case 'js':
			case 'javascript':
			case 'script':
				$method = 'get_javascript';
				$type = 'JavaScript';
				break;
		}
		$includedFiles = $backend->$method();

		// Workaround for inconsistent return formats
		if($method == 'get_javascript') {
			$includedFiles = array_combine(array_values($includedFiles), array_values($includedFiles));
		}

		if(is_array($files)) {
			$failedMatches = array();
			foreach ($files as $file) {
				if(!array_key_exists($file, $includedFiles)) {
					$failedMatches[] = $file;
				}
			}
			$this->assertTrue(
				(count($failedMatches) == 0),
				"Failed asserting the $type files '"
				. implode("', '", $failedMatches)
				. "' have exact matches in the required elements:\n'"
				. implode("'\n'", array_keys($includedFiles)) . "'"
			);
		} else {
			$this->assertTrue(
				(array_key_exists($files, $includedFiles)),
				"Failed asserting the $type file '$files' has an exact match in the required elements:\n'"
				. implode("'\n'", array_keys($includedFiles)) . "'"
			);
		}
	}

	public function assertFileNotIncluded($backend, $type, $files) {
		$type = strtolower($type);
		switch ($type) {
			case 'css':
				$method = 'get_css';
				$type = 'CSS';
				break;
			case 'js':
			case 'get_javascript':
			case 'script':
				$method = 'get_javascript';
				$type = 'JavaScript';
				break;
		}
		$includedFiles = $backend->$method();

		// Workaround for inconsistent return formats
		if($method == 'get_javascript') {
			$includedFiles = array_combine(array_values($includedFiles), array_values($includedFiles));
		}

		if(is_array($files)) {
			$failedMatches = array();
			foreach ($files as $file) {
				if(array_key_exists($file, $includedFiles)) {
					$failedMatches[] = $file;
				}
			}
			$this->assertTrue(
				(count($failedMatches) == 0),
				"Failed asserting the $type files '"
				. implode("', '", $failedMatches)
				. "' do not have exact matches in the required elements:\n'"
				. implode("'\n'", array_keys($includedFiles)) . "'"
			);
		} else {
			$this->assertFalse(
				(array_key_exists($files, $includedFiles)),
				"Failed asserting the $type file '$files' does not have an exact match in the required elements:"
						. "\n'" . implode("'\n'", array_keys($includedFiles)) . "'"
			);
		}
	}

	/**
	 * Test the placeholder replacement for RequirementsHandler->getCombinedFilesFolder()
	 */
	public function testGetCombinedFilesFolder() {
		Config::inst()->update('Requirements', 'combined_files_folder', '$AssetsDir/foo');

		$this->assertEquals(
			ASSETS_DIR . '/foo',
			Requirements::backend()->getCombinedFilesFolder(),
			'getCombinedFilesFolder() replaces $AssetsDir with the ASSETS_DIR constant'
		);
	}

	/**
	 * Test that overriding the default RequirementsHandler works as expected
	 */
	public function testCustomRequirementsBackend() {
		Injector::inst()->load(array('Requirements_Backend' => 'RequirementsTestHandler'));

		$this->assertEquals(-1, Requirements::get_combine_files());
	}

}

class RequirementsTestHandler extends RequirementsHandler implements TestOnly {

	public function getCombineFiles() {
		return -1;
	}

}
