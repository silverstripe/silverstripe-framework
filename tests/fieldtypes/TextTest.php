<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TextTest extends SapphireTest {

	/**
	 * Test {@link Text->LimitCharacters()}
	 */
	function testLimitCharacters() {
		$cases = array(
			'The little brown fox jumped over the lazy cow.' => 'The little brown fox...',
			'<p>This is some text in a paragraph.</p>' => '<p>This is some text...'
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitCharacters());
		}
	}
	
	/**
	 * Test {@link Text->LimitWordCount()}
	 */
	function testLimitWordCount() {
		$cases = array(
			/* Standard words limited, ellipsis added if truncated */
			'The little brown fox jumped over the lazy cow.' => 'The little brown...',
			' This text has white space around the ends ' => 'This text has...',
		
			/* Words less than the limt word count don't get truncated, ellipsis not added */
			'Two words' => 'Two words',	// Two words shouldn't have an ellipsis
			'One' => 'One',	// Neither should one word
			'' => '',	// No words produces nothing!
			
			/* HTML tags get stripped out, leaving the raw text */
			'<p>Text inside a paragraph tag should also work</p>' => 'Text inside a...',
			'<p><span>Text nested inside another tag should also work</span></p>' => 'Text nested inside...',
			'<p>Two words</p>' => 'Two words'
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitWordCount(3));
		}
	}

	/**
	 * Test {@link Text->LimitWordCountXML()}
	 */
	function testLimitWordCountXML() {
		$cases = array(
			'<p>Stuff & stuff</p>' => 'Stuff &amp;...',
			"Stuff\nBlah Blah Blah" => "Stuff<br />Blah Blah...",
			"Stuff<Blah Blah" => "Stuff&lt;Blah Blah",
			"Stuff>Blah Blah" => "Stuff&gt;Blah Blah"
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitWordCountXML(3));
		}
	}
	
}
?>