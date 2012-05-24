<?php
/**
 * @package framework
 * @subpackage tests
 */
class ShortcodeParserTest extends SapphireTest {
	
	protected $arguments, $contents, $tagName, $parser;
	
	public function setUp() {
		ShortcodeParser::get('test')->register('test_shortcode', array($this, 'shortcodeSaver'));
		$this->parser = ShortcodeParser::get('test');
		
		parent::setUp();
	}
	
	/**
	 * Tests that valid short codes that have not been registered are not replaced.
	 */
	public function testNotRegisteredShortcode() {
		$this->assertEquals('[not_shortcode]', $this->parser->parse('[not_shortcode]'));
		$this->assertEquals('[not_shortcode /]', $this->parser->parse('[not_shortcode /]'));
		$this->assertEquals('[not_shortcode,foo="bar"]', $this->parser->parse('[not_shortcode,foo="bar"]'));
		$this->assertEquals('[not_shortcode]a[/not_shortcode]', $this->parser->parse('[not_shortcode]a[/not_shortcode]'));
	}
	
	public function testSimpleTag() {
		$tests = array('[test_shortcode]', '[test_shortcode ]', '[test_shortcode,]', '[test_shortcode/]', '[test_shortcode /]');
		
		foreach($tests as $test) {
			$this->parser->parse($test);
			
			$this->assertEquals(array(), $this->arguments, $test);
			$this->assertEquals('', $this->contents, $test);
			$this->assertEquals('test_shortcode', $this->tagName, $test);
		}
	}
	
	public function testOneArgument() {
		$tests = array (
			'[test_shortcode,foo="bar"]',
			"[test_shortcode,foo='bar']",
			'[test_shortcode,foo  =  "bar"  /]'
		);
		
		foreach($tests as $test) {
			$this->parser->parse($test);
			
			$this->assertEquals(array('foo' => 'bar'), $this->arguments, $test);
			$this->assertEquals('', $this->contents, $test);
			$this->assertEquals('test_shortcode', $this->tagName, $test);
		}
	}
	
	public function testMultipleArguments() {
		$this->parser->parse('[test_shortcode,foo = "bar",bar=\'foo\',baz="buz"]');
		
		$this->assertEquals(array('foo' => 'bar', 'bar' => 'foo', 'baz' => 'buz'), $this->arguments);
		$this->assertEquals('', $this->contents);
		$this->assertEquals('test_shortcode', $this->tagName);
	}
	
	public function testEnclosing() {
		$this->parser->parse('[test_shortcode]foo[/test_shortcode]');
		
		$this->assertEquals(array(), $this->arguments);
		$this->assertEquals('foo', $this->contents);
		$this->assertEquals('test_shortcode', $this->tagName);
	}
	
	public function testEnclosingWithArguments() {
		$this->parser->parse('[test_shortcode,foo = "bar",bar=\'foo\',baz="buz"]foo[/test_shortcode]');
		
		$this->assertEquals(array('foo' => 'bar', 'bar' => 'foo', 'baz' => 'buz'), $this->arguments);
		$this->assertEquals('foo', $this->contents);
		$this->assertEquals('test_shortcode', $this->tagName);
	}
	
	public function testShortcodeEscaping() {
		$this->assertEquals('[test_shortcode]', $this->parser->parse('[[test_shortcode]]'));
		$this->assertEquals('[test_shortcode]content[/test_shortcode]', $this->parser->parse('[[test_shortcode]content[/test_shortcode]]'));
	}
	
	public function testUnquotedArguments() {
		$this->assertEquals('', $this->parser->parse('[test_shortcode,foo=bar,baz = buz]'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'buz'), $this->arguments);
	}
	
	public function testSpacesForDelimiter() {
		$this->assertEquals('', $this->parser->parse('[test_shortcode foo=bar baz = buz]'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'buz'), $this->arguments);
	}

	public function testSelfClosingTag() {
		$this->assertEquals (
			'morecontent',
			$this->parser->parse('[test_shortcode,id="1"/]more[test_shortcode,id="2"]content[/test_shortcode]'),
			'Assert that self-closing tags are respected during parsing.'
		);
		
		$this->assertEquals(2, $this->arguments['id']);
	}

	public function testConsecutiveTags() {
		$this->assertEquals('', $this->parser->parse('[test_shortcode][test_shortcode]'));
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * Stores the result of a shortcode parse in object properties for easy testing access.
	 */
	public function shortcodeSaver($arguments, $content = null, $parser, $tagName = null) {
		$this->arguments = $arguments;
		$this->contents  = $content;
		$this->tagName   = $tagName;
		
		return $content;
	}
	
}
