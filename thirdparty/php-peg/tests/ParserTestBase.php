<?php


$base = dirname(dirname(__FILE__));

include "$base/Compiler.php";
include "$base/Parser.php";

class ParserTestWrapper {
	
	function __construct($testcase, $class){
		$this->testcase = $testcase;
		$this->class = $class;
	}

	function match($method, $string, $allowPartial = false){
		$class = $this->class;
		$func = 'match_'.$method;
		
		$parser = new $class($string);
		$res = $parser->$func();
		return ($allowPartial || $parser->pos == strlen($string)) ? $res : false;
	}
	
	function matches($method, $string, $allowPartial = false){
		return $this->match($method, $string, $allowPartial) !== false;
	}
	
	function assertMatches($method, $string, $message = null){
		$this->testcase->assertTrue($this->matches($method, $string), $message ? $message : "Assert parser method $method matches string $string");
	}
	
	function assertDoesntMatch($method, $string, $message = null){
		$this->testcase->assertFalse($this->matches($method, $string), $message ? $message : "Assert parser method $method doesn't match string $string");
	}
}

class ParserTestBase extends PHPUnit_Framework_TestCase {
	
	function buildParser($parser) {
		$class = 'Parser'.sha1($parser);
		
		echo ParserCompiler::compile("class $class extends Parser {\n $parser\n}") . "\n\n\n";
		eval(ParserCompiler::compile("class $class extends Parser {\n $parser\n}"));
		return new ParserTestWrapper($this, $class);
	}

}