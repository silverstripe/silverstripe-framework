<?php

require_once "ParserTestBase.php";

class ParserInheritanceTest extends ParserTestBase {
	
	public function testBasicInheritance() {
		
		$parser = $this->buildParser('
			/*!* BasicInheritanceTestParser
			Foo: "a"
			Bar extends Foo
			*/
		');

		$this->assertTrue($parser->matches('Foo', 'a'));
		$this->assertTrue($parser->matches('Bar', 'a'));

		$this->assertFalse($parser->matches('Foo', 'b'));
		$this->assertFalse($parser->matches('Bar', 'b'));
	}

	
	public function testBasicInheritanceConstructFallback() {
		
		$parser = $this->buildParser('
			/*!* BasicInheritanceConstructFallbackParser
			Foo: "a"
				function __construct(&$res){ $res["test"] = "test"; }
			Bar extends Foo
			*/
		');

		$res = $parser->match('Foo', 'a');
		$this->assertEquals($res['test'], 'test');
		
		$res = $parser->match('Bar', 'a');
		$this->assertEquals($res['test'], 'test');
		
		$parser = $this->buildParser('
			/*!* BasicInheritanceConstructFallbackParser2
			Foo: "a"
				function __construct(&$res){ $res["testa"] = "testa"; }
			Bar extends Foo
				function __construct(&$res){ $res["testb"] = "testb"; }
			*/
		');

		$res = $parser->match('Foo', 'a');
		$this->assertArrayHasKey('testa', $res);
		$this->assertEquals($res['testa'], 'testa');
		$this->assertArrayNotHasKey('testb', $res);
		
		$res = $parser->match('Bar', 'a');
		$this->assertArrayHasKey('testb', $res);
		$this->assertEquals($res['testb'], 'testb');
		$this->assertArrayNotHasKey('testa', $res);
		
	}

	public function testBasicInheritanceStoreFallback() {
		
		$parser = $this->buildParser('
			/*!* BasicInheritanceStoreFallbackParser
			Foo: Pow:"a"
				function *(&$res, $sub){ $res["test"] = "test"; }
			Bar extends Foo
			*/
		');

		$res = $parser->match('Foo', 'a');
		$this->assertEquals($res['test'], 'test');
		
		$res = $parser->match('Bar', 'a');
		$this->assertEquals($res['test'], 'test');
		
		$parser = $this->buildParser('
			/*!* BasicInheritanceStoreFallbackParser2
			Foo: Pow:"a" Zap:"b"
				function *(&$res, $sub){ $res["testa"] = "testa"; }
			Bar extends Foo
				function *(&$res, $sub){ $res["testb"] = "testb"; }
			Baz extends Foo
				function Zap(&$res, $sub){ $res["testc"] = "testc"; }
			*/
		');

		$res = $parser->match('Foo', 'ab');
		$this->assertArrayHasKey('testa', $res);
		$this->assertEquals($res['testa'], 'testa');
		$this->assertArrayNotHasKey('testb', $res);
		
		$res = $parser->match('Bar', 'ab');
		$this->assertArrayHasKey('testb', $res);
		$this->assertEquals($res['testb'], 'testb');
		$this->assertArrayNotHasKey('testa', $res);

		$res = $parser->match('Baz', 'ab');
		$this->assertArrayHasKey('testa', $res);
		$this->assertEquals($res['testa'], 'testa');
		$this->assertArrayHasKey('testc', $res);
		$this->assertEquals($res['testc'], 'testc');
		$this->assertArrayNotHasKey('testb', $res);
	}

	public function testInheritanceByReplacement() {
		$parser = $this->buildParser('
			/*!* InheritanceByReplacementParser
			A: "a"
			B: "b"
			Foo: A B
			Bar extends Foo; B => A
			Baz extends Foo; A => ""
			*/
		');
		
		$parser->assertMatches('Foo', 'ab');
		$parser->assertMatches('Bar', 'aa');
		$parser->assertMatches('Baz', 'b');
	}
	
	
}