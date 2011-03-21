<?php

require_once "ParserTestBase.php";

class ParserVariablesTest extends ParserTestBase {
	
	public function testBasicLiteralVariables() {
		$parser = $this->buildParser('
			/*!* BasicVariables
			Foo: Letter:"a" "$Letter"
			Bar: Letter:"b" "$Letter $Letter"
			Baz: Letter:"c" "$Letter a $Letter a"
			Qux: Letter:"d" "{$Letter}a{$Letter}a"
			*/
		');
		
		$parser->assertMatches('Foo', 'aa');
		$parser->assertMatches('Bar', 'bb b');
		$parser->assertMatches('Baz', 'cc a c a');
		$parser->assertMatches('Qux', 'ddada');
	}
	
	public function testRecurseOnVariables() {
		$parser = $this->buildParser('
			/*!* RecurseOnVariablesParser
			A: "a"
			B: "b"
			Foo: $Template
			Bar: Foo
				function __construct(&$res){ $res["Template"] = "A"; }
			Baz: Foo
				function __construct(&$res){ $res["Template"] = "B"; }
			*/
		');

		$parser->assertMatches('Bar', 'a');	$parser->assertDoesntMatch('Bar', 'b');
		$parser->assertMatches('Baz', 'b');	$parser->assertDoesntMatch('Baz', 'a');
	}
	
	public function testSetOnRuleVariables() {
		$parser = $this->buildParser('
			/*!* SetOnRuleVariablesParser
			A: "a"
			B: "b"
			Foo: $Template
			Bar (Template = A): Foo
			Baz (Template = B): Foo
			*/
		');

		$parser->assertMatches('Bar', 'a');
		$parser->assertMatches('Baz', 'b');
	}

}