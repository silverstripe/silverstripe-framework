<?php
/**
 * @package framework
 * @subpackage tests
 */

class HTMLCleanerTest extends SapphireTest {
	
	function testHTMLClean() {
		$cleaner = HTMLCleaner::inst();

		if ($cleaner) {
			$this->assertEquals(
				$cleaner->cleanHTML('<p>wrong <b>nesting</i></p>' . "\n"),
				'<p>wrong <b>nesting</b></p>' . "\n",
				"HTML cleaned properly"
			);
			$this->assertEquals(
				$cleaner->cleanHTML('<p>unclosed paragraph' . "\n"),
				'<p>unclosed paragraph</p>' . "\n",
				"HTML cleaned properly"
			);
		} else {
			$this->markTestSkipped('No HTMLCleaner library available (tidy or HTMLBeautifier)');
		}
	}

}
