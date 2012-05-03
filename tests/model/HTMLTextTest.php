<?php
/**
 * @package framework
 * @subpackage tests
 */
class HTMLTextTest extends SapphireTest {
	
	/**
	 * Test {@link HTMLText->LimitCharacters()}
	 */
	function testLimitCharacters() {
		$cases = array(
			'The little brown fox jumped over the lazy cow.' => 'The little brown fox...',
			'<p>This is some text in a paragraph.</p>' => 'This is some text in...',
			'This text contains &amp; in it' => 'This text contains &amp;...'
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new HTMLText('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitCharacters());
		}
	}
	
	function testSummaryBasics() {
		$cases = array(
			'<h1>Should not take header</h1><p>Should take paragraph</p>' => 'Should take paragraph',
			'<p>Should strip <b>tags, but leave</b> text</p>' => 'Should strip tags, but leave text',
			'<p>Unclosed tags <br>should not phase it</p>' => 'Unclosed tags should not phase it',
			'<p>Second paragraph</p><p>should not cause errors or appear in output</p>' => 'Second paragraph',
			'<img src="hello" /><p>Second paragraph</p><p>should not cause errors or appear in output</p>' => 'Second paragraph',
			'  <img src="hello" /><p>Second paragraph</p><p>should not cause errors or appear in output</p>' => 'Second paragraph',
			'<p><img src="remove me">example <img src="include me">text words hello<img src="hello"></p>' => 'example text words hello',
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new HTMLText('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->Summary());
		}
	}

	function testSummaryLimits() {
		$cases = array(
			'<p>A long paragraph should be cut off if limit is set</p>' => 'A long paragraph should be...',
			'<p>No matter <i>how many <b>tags</b></i> are in it</p>' => 'No matter how many tags...',
			'<p>A sentence is. nicer than hard limits</p>' => 'A sentence is.',
			'<p>But not. If it\'s too short</p>' => 'But not. If it\'s too...'
		);
		
		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new HTMLText('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->Summary(5, 3, '...'));
		}
	}

	function testSummaryEndings() {
		$cases = array(
			'...', ' -> more', ''
		);
		
		$orig = '<p>Cut it off, cut it off</p>';
		$match = 'Cut it off, cut';
		
		foreach($cases as $add) {
			$textObj = new HTMLText();
			$textObj->setValue($orig);
			$this->assertEquals($match.$add, $textObj->Summary(4, 0, $add));
		}
	}

	function testSummaryFlexTooBigShouldNotCauseError() {
		$orig = '<p>Cut it off, cut it off</p>';
		$match = 'Cut it off, cut';
		
		$textObj = new HTMLText();
		$textObj->setValue($orig);
		$this->assertEquals($match, $textObj->Summary(4, 10, ''));
	}
	
	function testSummaryInvalidHTML() {
		$cases = array(
			'It\'s got a <p<> tag, but<p junk true>This doesn\'t <a id="boo">make</b class="wa"> < ><any< sense</p>' => 'This doesn\'t make any',
			'This doesn\'t <a style="much horray= true>even</b> < ><have< a <i>p tag' => 'This doesn\'t even have'
		);
		
		foreach($cases as $orig => $match) {
			$textObj = new HTMLText();
			$textObj->setValue($orig);
			$this->assertEquals($match, $textObj->Summary(4, 0, ''));
		}
	}

	function testFirstSentence() {
		$many = str_repeat('many ', 100);
		$cases = array(
			'<h1>should ignore</h1><p>First sentence. Second sentence.</p>' => 'First sentence.',
			'<h1>should ignore</h1><p>First Mr. sentence. Second sentence.</p>' => 'First Mr. sentence.',
			"<h1>should ignore</h1><p>Sentence with {$many}words. Second sentence.</p>" => "Sentence with {$many}words.",
		);
		
		foreach($cases as $orig => $match) {
			$textObj = new HTMLText();
			$textObj->setValue($orig);
			$this->assertEquals($match, $textObj->FirstSentence());
		}
	}	

	public function testRAW() {
		$data = DBField::create_field('HTMLText', 'This &amp; This');
		$this->assertEquals($data->RAW(), 'This &amp; This');

		$data = DBField::create_field('HTMLText', 'This & This');
		$this->assertEquals($data->RAW(), 'This & This');
	}
	
	public function testXML() {
		$data = DBField::create_field('HTMLText', 'This & This');
		$this->assertEquals($data->XML(), 'This &amp; This');
	}

	public function testHTML() {
		$data = DBField::create_field('HTMLText', 'This & This');
		$this->assertEquals($data->HTML(), 'This &amp; This');
	}

	public function testJS() {
		$data = DBField::create_field('HTMLText', '"this is a test"');
		$this->assertEquals($data->JS(), '\"this is a test\"');
	}

	public function testATT() {
		$data = DBField::create_field('HTMLText', '"this is a test"');
		$this->assertEquals($data->ATT(), '&quot;this is a test&quot;');
	}
}
