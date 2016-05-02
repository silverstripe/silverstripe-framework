<?php
/**
 * @package framework
 * @subpackage tests
 */

class HTMLCleanerTest extends SapphireTest {

	public function testHTMLClean() {
		$cleaner = HTMLCleaner::inst();

		if ($cleaner) {
			$this->assertEquals(
				$cleaner->cleanHTML('<p>wrong <b>nesting</i></p>'),
				'<p>wrong <b>nesting</b></p>',
				"HTML cleaned properly"
			);
			$this->assertEquals(
				$cleaner->cleanHTML('<p>unclosed paragraph'),
				'<p>unclosed paragraph</p>',
				"HTML cleaned properly"
			);
		} else {
			$this->markTestSkipped('No HTMLCleaner library available (tidy or HTMLBeautifier)');
		}
	}

}
