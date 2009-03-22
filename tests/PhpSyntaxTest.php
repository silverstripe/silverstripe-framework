<?php

/**
 * Test the syntax of the PHP files with various settings
 */
class PhpSyntaxTest extends SapphireTest {
	function testShortTagsOffWillWork() {
		// Ignore this test completely if running the test suite on windows
		// TODO: Make it work on all platforms, by building an alternative to find | grep.
		$returnCode = 0;
		$output = array();
		exec("which find && which grep && which php", $output, $returnCode);
		if($returnCode != 0) return;

		$settingTests = array('short_open_tag=Off','short_open_tag=On -d asp_tags=On');
		
		$files = $this->getAllFiles('php');
		$files[] = '../sapphire/dev/install/config-form.html';
		
		foreach($files as $i => $file) {
			$CLI_file = escapeshellarg($file);
			foreach($settingTests as $settingTest) {
				$returnCode = 0;
				$output = array();
				exec("php -l -d $settingTest $CLI_file", $output, $returnCode);
				$this->assertEquals(0, $returnCode, "Syntax error parsing $CLI_file with setting $settingTest:\n" . implode("\n", $output));
			}
		}
	}
	
	function getAllFiles($ext = 'php') {
		// TODO: Unix only
		$CLI_regexp = escapeshellarg("\.$ext\$");
		return explode("\n", trim(`find .. | grep $CLI_regexp`));
	}
}