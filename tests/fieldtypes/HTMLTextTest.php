<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class HTMLTextTest extends SapphireTest {
	
	/**
	 * Test {@link Text->LimitCharacters()}
	 */
	function testLimitCharacters() {
		$cases = array(
			'The little brown fox jumped over the lazy cow.' => 'The little brown fox...',
			'<p>This is some text in a paragraph.</p>' => 'This is some text in...'
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new HTMLText('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitCharacters());
		}
	}
	
}
?>