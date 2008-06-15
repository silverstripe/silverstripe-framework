<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TokenisedRegularExpressionTest extends SapphireTest {
	function getTokens() {
		return token_get_all(<<<PHP
<?php

class ClassA {
	
}

class ClassB{
	
}

class ClassC extends ParentClassC {
	
}

class ClassD extends ParentClassD
implements InterfaceA {
	
}

interface InterfaceA {
	
}

interface InterfaceB extends Something{
	
}

class ClassE extends ParentClassE 
implements InterfaceA,InterfaceB {
	
}

class ClassF extends ParentClassF 
implements InterfaceA, InterfaceB {
	
}

?>
PHP
);
	}
	
	function testClassDefParser() {
		$parser = ManifestBuilder::getClassDefParser();
		
		$tokens = $this->getTokens();

		$matches = $parser->findAll($tokens);
		$classes = array();
		if($matches) foreach($matches as $match) $classes[$match['className']] = $match;

		$this->assertArrayHasKey('ClassA', $classes);
		$this->assertArrayHasKey('ClassB', $classes);
	
		$this->assertArrayHasKey('ClassC', $classes);
		$this->assertEquals('ParentClassC', $classes['ClassC']['extends']);

		$this->assertArrayHasKey('ClassD', $classes);
		$this->assertEquals('ParentClassD', $classes['ClassD']['extends']);
		$this->assertContains('InterfaceA', $classes['ClassD']['interfaces']);

		$this->assertArrayHasKey('ClassE', $classes);
		$this->assertEquals('ParentClassE', $classes['ClassE']['extends']);
		$this->assertContains('InterfaceA', $classes['ClassE']['interfaces']);
		$this->assertContains('InterfaceB', $classes['ClassE']['interfaces']);

		$this->assertArrayHasKey('ClassF', $classes);
		$this->assertEquals('ParentClassF', $classes['ClassF']['extends']);
		$this->assertContains('InterfaceA', $classes['ClassF']['interfaces']);
		$this->assertContains('InterfaceB', $classes['ClassF']['interfaces']);
	}

	function testInterfaceDefParser() {
		$parser = ManifestBuilder::getInterfaceDefParser();
		
		$tokens = $this->getTokens();

		$matches = $parser->findAll($tokens);
		$interfaces = array();
		if($matches) foreach($matches as $match) $interfaces[$match['interfaceName']] = $match;

		$this->assertArrayHasKey('InterfaceA', $interfaces);

		$this->assertArrayHasKey('InterfaceB', $interfaces);
		$this->assertEquals('Something', $interfaces['InterfaceB']['extends']);
	}
}