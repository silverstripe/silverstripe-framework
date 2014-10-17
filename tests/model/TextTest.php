<?php
/**
 * @package framework
 * @subpackage tests
 */
class TextTest extends SapphireTest {

	/**
	 * Test {@link Text->LimitCharacters()}
	 */
	public function testLimitCharacters() {
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
	 * Test {@link Text->LimitCharactersToClosestWord()}
	 */
	public function testLimitCharactersToClosestWord() {
		$cases = array(
			/* Standard words limited, ellipsis added if truncated */
			'Lorem ipsum dolor sit amet' => 'Lorem ipsum dolor sit...',

			/* Complete words less than the character limit don't get truncated, ellipsis not added */
			'Lorem ipsum' => 'Lorem ipsum',
			'Lorem' => 'Lorem',
			'' => '',	// No words produces nothing!

			/* HTML tags get stripped out, leaving the raw text */
			'<p>Lorem ipsum dolor sit amet</p>' => 'Lorem ipsum dolor sit...',
			'<p><span>Lorem ipsum dolor sit amet</span></p>' => 'Lorem ipsum dolor sit...',
			'<p>Lorem ipsum</p>' => 'Lorem ipsum',

			/* HTML entities are treated as a single character */
			'Lorem &amp; ipsum dolor sit amet' => 'Lorem &amp; ipsum dolor...'
		);

		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitCharactersToClosestWord(24));
		}
	}

	/**
	 * Test {@link Text->LimitWordCount()}
	 */
	public function testLimitWordCount() {
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
	public function testLimitWordCountXML() {
		$cases = array(
			'<p>Stuff & stuff</p>' => 'Stuff &amp;...',
			"Stuff\nBlah Blah Blah" => "Stuff\nBlah Blah...",
			"Stuff<Blah Blah" => "Stuff&lt;Blah Blah",
			"Stuff>Blah Blah" => "Stuff&gt;Blah Blah"
		);

		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitWordCountXML(3));
		}
	}

	/**
	 * Test {@link Text->LimitSentences()}
	 */
	public function testLimitSentences() {
		$cases = array(
			'' => '',
			'First sentence.' => 'First sentence.',
			'First sentence. Second sentence' => 'First sentence. Second sentence.',
			'<p>First sentence.</p>' => 'First sentence.',
			'<p>First sentence. Second sentence. Third sentence</p>' => 'First sentence. Second sentence.',
			'<p>First sentence. <em>Second sentence</em>. Third sentence</p>' => 'First sentence. Second sentence.',
			'<p>First sentence. <em class="dummyClass">Second sentence</em>. Third sentence</p>'
				=> 'First sentence. Second sentence.'
		);

		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->LimitSentences(2));
		}
	}

	public function testFirstSentance() {
		$cases = array(
			'' => '',
			'First sentence.' => 'First sentence.',
			'First sentence. Second sentence' => 'First sentence.',
			'First sentence? Second sentence' => 'First sentence?',
			'First sentence! Second sentence' => 'First sentence!',
			'<p>First sentence.</p>' => 'First sentence.',
			'<p>First sentence. Second sentence. Third sentence</p>' => 'First sentence.',
			'<p>First sentence. <em>Second sentence</em>. Third sentence</p>' => 'First sentence.',
			'<p>First sentence. <em class="dummyClass">Second sentence</em>. Third sentence</p>'
				=> 'First sentence.'
		);

		foreach($cases as $originalValue => $expectedValue) {
			$textObj = new Text('Test');
			$textObj->setValue($originalValue);
			$this->assertEquals($expectedValue, $textObj->FirstSentence());
		}
	}

	/**
	 * Test {@link Text->BigSummary()}
	 */
	public function testBigSummaryPlain() {
		$cases = array(
			'<p>This text has multiple sentences. Big Summary uses this to split sentences up.</p>'
				=> 'This text has multiple...',
			'This text does not have multiple sentences' => 'This text does not...',
			'Very short' => 'Very short',
			'' => ''
		);

		foreach($cases as $originalValue => $expectedValue) {
			$textObj = DBField::create_field('Text', $originalValue);
			$this->assertEquals($expectedValue, $textObj->BigSummary(4, true));
		}
	}

	/**
	 * Test {@link Text->BigSummary()}
	 */
	public function testBigSummary() {
		$cases = array(
			'<strong>This</strong> text has multiple sentences. Big Summary uses this to split sentences up.</p>'
				=> '<strong>This</strong> text has multiple...',
			'This text does not have multiple sentences' => 'This text does not...',
			'Very short' => 'Very short',
			'' => ''
		);

		foreach($cases as $originalValue => $expectedValue) {
			$textObj = DBField::create_field('Text', $originalValue);
			$this->assertEquals($expectedValue, $textObj->BigSummary(4, false));
		}
	}

	public function testContextSummary() {
		$testString1 = '<p>This is some text. It is a test</p>';
		$testKeywords1 = 'test';

		$testString2 = '<p>This is some test text. Test test what if you have multiple keywords.</p>';
		$testKeywords2 = 'some test';

		$testString3 = '<p>A dog ate a cat while looking at a Foobar</p>';
		$testKeyword3 = 'a';
		$testKeyword3a = 'ate';

		$textObj = DBField::create_field('Text', $testString1, 'Text');

		$this->assertEquals(
			'... text. It is a <span class="highlight">test</span>...',
			$textObj->ContextSummary(20, $testKeywords1)
		);

		$textObj->setValue($testString2);

		$this->assertEquals(
			'This is <span class="highlight">some</span> <span class="highlight">test</span> text.'
				. ' <span class="highlight">test</span> <span class="highlight">test</span> what if you have...',
			$textObj->ContextSummary(50, $testKeywords2)
		);

		$textObj->setValue($testString3);

		// test that it does not highlight too much (eg every a)
		$this->assertEquals(
			'A dog ate a cat while looking at a Foobar',
			$textObj->ContextSummary(100, $testKeyword3)
		);

		// it should highlight 3 letters or more.
		$this->assertEquals(
			'A dog <span class="highlight">ate</span> a cat while looking at a Foobar',
			$textObj->ContextSummary(100, $testKeyword3a)
		);
	}

	public function testRAW() {
		$data = DBField::create_field('Text', 'This &amp; This');
		$this->assertEquals($data->RAW(), 'This &amp; This');
	}

	public function testXML() {
		$data = DBField::create_field('Text', 'This & This');
		$this->assertEquals($data->XML(), 'This &amp; This');
	}

	public function testHTML() {
		$data = DBField::create_field('Text', 'This & This');
		$this->assertEquals($data->HTML(), 'This &amp; This');
	}

	public function testJS() {
		$data = DBField::create_field('Text', '"this is a test"');
		$this->assertEquals($data->JS(), '\"this is a test\"');
	}

	public function testATT() {
		$data = DBField::create_field('Text', '"this is a test"');
		$this->assertEquals($data->ATT(), '&quot;this is a test&quot;');
	}
}
