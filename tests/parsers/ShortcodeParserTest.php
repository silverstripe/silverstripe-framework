<?php
/**
 * @package framework
 * @subpackage tests
 */
class ShortcodeParserTest extends SapphireTest {

	protected $arguments, $contents, $tagName, $parser;
	protected $extra = array();

	public function setUp() {
		ShortcodeParser::get('test')->register('test_shortcode', array($this, 'shortcodeSaver'));
		$this->parser = ShortcodeParser::get('test');

		parent::setUp();
	}

	public function tearDown() {
		ShortcodeParser::get('test')->unregister('test_shortcode');

		parent::tearDown();
	}

	/**
	 * Tests that valid short codes that have not been registered are not replaced.
	 */
	public function testNotRegisteredShortcode() {
		ShortcodeParser::$error_behavior = ShortcodeParser::STRIP;

		$this->assertEquals(
			'',
			$this->parser->parse('[not_shortcode]')
		);

		$this->assertEquals(
			'<img class="">',
			$this->parser->parse('<img class="[not_shortcode]">')
		);

		ShortcodeParser::$error_behavior = ShortcodeParser::WARN;

		$this->assertEquals(
			'<strong class="warning">[not_shortcode]</strong>',
			$this->parser->parse('[not_shortcode]')
		);

		ShortcodeParser::$error_behavior = ShortcodeParser::LEAVE;

		$this->assertEquals('[not_shortcode]',
			$this->parser->parse('[not_shortcode]'));
		$this->assertEquals('[not_shortcode /]',
			$this->parser->parse('[not_shortcode /]'));
		$this->assertEquals('[not_shortcode,foo="bar"]',
			$this->parser->parse('[not_shortcode,foo="bar"]'));
		$this->assertEquals('[not_shortcode]a[/not_shortcode]',
			$this->parser->parse('[not_shortcode]a[/not_shortcode]'));
		$this->assertEquals('[/not_shortcode]',
			$this->parser->parse('[/not_shortcode]'));

		$this->assertEquals(
			'<img class="[not_shortcode]">',
			$this->parser->parse('<img class="[not_shortcode]">')
		);
	}

	public function testSimpleTag() {
		$tests = array(
			'[test_shortcode]',
			'[test_shortcode ]', '[test_shortcode,]', '[test_shortcode, ]'.
			'[test_shortcode/]', '[test_shortcode /]', '[test_shortcode,/]', '[test_shortcode, /]'
		);

		foreach($tests as $test) {
			$this->parser->parse($test);

			$this->assertEquals(array(), $this->arguments, $test);
			$this->assertEquals('', $this->contents, $test);
			$this->assertEquals('test_shortcode', $this->tagName, $test);
		}
	}

	public function testOneArgument() {
		$tests = array (
			'[test_shortcode foo="bar"]', '[test_shortcode,foo="bar"]',
			"[test_shortcode foo='bar']", "[test_shortcode,foo='bar']",
			'[test_shortcode  foo  =  "bar"  /]', '[test_shortcode,  foo  =  "bar"  /]'
		);

		foreach($tests as $test) {
			$this->parser->parse($test);

			$this->assertEquals(array('foo' => 'bar'), $this->arguments, $test);
			$this->assertEquals('', $this->contents, $test);
			$this->assertEquals('test_shortcode', $this->tagName, $test);
		}
	}

	public function testMultipleArguments() {
		$this->parser->parse('[test_shortcode foo = "bar",bar=\'foo\', baz="buz"]');

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
		$this->assertEquals(
			'[test_shortcode]',
			$this->parser->parse('[[test_shortcode]]')
		);

		$this->assertEquals(
			'[test_shortcode /]',
			$this->parser->parse('[[test_shortcode /]]')
		);

		$this->assertEquals(
			'[test_shortcode]content[/test_shortcode]',
			$this->parser->parse('[[test_shortcode]content[/test_shortcode]]'
		));

		$this->assertEquals(
			'[test_shortcode]content',
			$this->parser->parse('[[test_shortcode]][test_shortcode]content[/test_shortcode]')
		);

		$this->assertEquals(
			'[test_shortcode]content[/test_shortcode]content2',
			$this->parser->parse('[[test_shortcode]content[/test_shortcode]][test_shortcode]content2[/test_shortcode]'
		));

		$this->assertEquals(
			'[[Doesnt strip double [ character if not a shortcode',
			$this->parser->parse('[[Doesnt strip double [ character if not a [test_shortcode]shortcode[/test_shortcode]'
		));

		$this->assertEquals(
			'[[Doesnt shortcode get confused by double ]] characters',
			$this->parser->parse(
				'[[Doesnt [test_shortcode]shortcode[/test_shortcode] get confused by double ]] characters')
		);
	}

	public function testUnquotedArguments() {
		$this->assertEquals('', $this->parser->parse('[test_shortcode,foo=bar!,baz = buz123]'));
		$this->assertEquals(array('foo' => 'bar!', 'baz' => 'buz123'), $this->arguments);
	}

	public function testSpacesForDelimiter() {
		$this->assertEquals('', $this->parser->parse('[test_shortcode foo=bar! baz = buz123]'));
		$this->assertEquals(array('foo' => 'bar!', 'baz' => 'buz123'), $this->arguments);
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

	protected function assertEqualsIgnoringWhitespace($a, $b, $message = null) {
		$this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b), $message);
	}

	public function testtExtractBefore()
	{
		// Left extracts to before the current block
		$this->assertEqualsIgnoringWhitespace(
			'Code<div>FooBar</div>',
			$this->parser->parse('<div>Foo[test_shortcode class=left]Code[/test_shortcode]Bar</div>')
		);

		// Even if the immediate parent isn't a the current block
		$this->assertEqualsIgnoringWhitespace(
			'Code<div>Foo<b>BarBaz</b>Qux</div>',
			$this->parser->parse('<div>Foo<b>Bar[test_shortcode class=left]Code[/test_shortcode]Baz</b>Qux</div>')
		);
	}

	public function testExtractSplit()
	{
		$this->markTestSkipped(
			'Feature disabled due to https://github.com/silverstripe/silverstripe-framework/issues/5987'
		);

		// Center splits the current block
		$this->assertEqualsIgnoringWhitespace(
			'<div>Foo</div>Code<div>Bar</div>',
			$this->parser->parse('<div>Foo[test_shortcode class=center]Code[/test_shortcode]Bar</div>')
		);

		// Even if the immediate parent isn't a the current block
		$this->assertEqualsIgnoringWhitespace(
			'<div>Foo<b>Bar</b></div>Code<div><b>Baz</b>Qux</div>',
			$this->parser->parse('<div>Foo<b>Bar[test_shortcode class=center]Code[/test_shortcode]Baz</b>Qux</div>')
		);
	}

	public function testExtractNone() {
		// No class means don't extract
		$this->assertEqualsIgnoringWhitespace(
			'<div>FooCodeBar</div>',
			$this->parser->parse('<div>Foo[test_shortcode]Code[/test_shortcode]Bar</div>')
		);
	}

	public function testShortcodesInsideScriptTag() {
		$this->assertEqualsIgnoringWhitespace(
			'<script>hello</script>',
			$this->parser->parse('<script>[test_shortcode]hello[/test_shortcode]</script>')
		);
	}

	public function testFalseyArguments() {
		$this->parser->parse('<p>[test_shortcode falsey=0]');

		$this->assertEquals(array(
			'falsey' => '',
		), $this->arguments);
	}

	public function testNumericShortcodes() {
		$this->assertEqualsIgnoringWhitespace(
			'[2]',
			$this->parser->parse('[2]')
		);
		$this->assertEqualsIgnoringWhitespace(
			'<script>[2]</script>',
			$this->parser->parse('<script>[2]</script>')
		);

		$this->parser->register('2', function($attributes, $content, $parser, $tag, $extra) {
			return 'this is 2';
		});

		$this->assertEqualsIgnoringWhitespace(
			'this is 2',
			$this->parser->parse('[2]')
		);
		$this->assertEqualsIgnoringWhitespace(
			'<script>this is 2</script>',
			$this->parser->parse('<script>[2]</script>')
		);

		$this->parser->unregister('2');
	}

	public function testExtraContext() {
		$this->parser->parse('<a href="[test_shortcode]">Test</a>');

		$this->assertInstanceOf('DOMNode', $this->extra['node']);
		$this->assertInstanceOf('DOMElement', $this->extra['element']);
		$this->assertEquals($this->extra['element']->tagName, 'a');
	}

	public function testNoParseAttemptIfNoCode() {
		$stub = $this->getMockBuilder('ShortcodeParser')
			->setMethods(array('replaceElementTagsWithMarkers'))
			->getMock();
		$stub->register('test', function() {
			return '';
		});

		$stub->expects($this->never())
			->method('replaceElementTagsWithMarkers')->will($this->returnValue(array('', '')));

		$stub->parse('<p>test</p>');
	}

	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * Stores the result of a shortcode parse in object properties for easy testing access.
	 */
	public function shortcodeSaver($arguments, $content, $parser, $tagName, $extra) {
		$this->arguments = $arguments;
		$this->contents = $content;
		$this->tagName = $tagName;
		$this->extra = $extra;

		return $content;
	}

}
