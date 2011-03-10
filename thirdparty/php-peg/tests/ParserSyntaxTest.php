<?php

require_once "ParserTestBase.php";

class ParserSyntaxTest extends ParserTestBase {

	public function testBasicRuleSyntax() {
		$parser = $this->buildParser('
			/*!* BasicRuleSyntax
			Foo: "a" "b"
			Bar: "a"
				"b"
			Baz:
				"a" "b"
			Qux:
				"a"
				"b"
			*/
		');
		
		$parser->assertMatches('Foo', 'ab');
		$parser->assertMatches('Bar', 'ab');
		$parser->assertMatches('Baz', 'ab');
		$parser->assertMatches('Qux', 'ab');
	}
}