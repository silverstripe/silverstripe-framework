<?php
/**
 * @package framework
 * @subpackage tests
 */
class TokenisedRegularExpressionTest extends SapphireTest {
	public function getTokens() {
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

interface InterfaceC extends InterfaceA, InterfaceB {
}
interface InterfaceD extends InterfaceA, InterfaceB, InterfaceC {
}

PHP
);
	}

	public function getNamespaceTokens() {
		return token_get_all(<<<PHP
<?php

namespace silverstripe\\test;

class ClassA {

}

class ClassB extends ParentClassB {

}

class ClassC extends \\ParentClassC {

}

class ClassD extends subtest\\ParentClassD {

}

class ClassE implements InterfaceE {

}

class ClassF implements \\InterfaceF {

}

class ClassG implements subtest\\InterfaceG {

}


PHP
);
	}

	public function testClassDefParser() {
		$parser = SS_ClassManifest::get_class_parser();

		$tokens = $this->getTokens();

		$matches = $parser->findAll($tokens);
		$classes = [];
		if($matches) foreach($matches as $match) $classes[$match['className']] = $match;

		$this->assertArrayHasKey('ClassA', $classes);
		$this->assertArrayHasKey('ClassB', $classes);

		$this->assertArrayHasKey('ClassC', $classes);
		$this->assertEquals(['ParentClassC'], $classes['ClassC']['extends']);

		$this->assertArrayHasKey('ClassD', $classes);
		$this->assertEquals(['ParentClassD'], $classes['ClassD']['extends']);
		$this->assertContains('InterfaceA', $classes['ClassD']['interfaces']);

		$this->assertArrayHasKey('ClassE', $classes);
		$this->assertEquals(['ParentClassE'], $classes['ClassE']['extends']);
		$this->assertContains('InterfaceA', $classes['ClassE']['interfaces']);
		$this->assertContains('InterfaceB', $classes['ClassE']['interfaces']);

		$this->assertArrayHasKey('ClassF', $classes);
		$this->assertEquals(['ParentClassF'], $classes['ClassF']['extends']);
		$this->assertContains('InterfaceA', $classes['ClassF']['interfaces']);
		$this->assertContains('InterfaceB', $classes['ClassF']['interfaces']);
	}

	public function testNamesapcedClassDefParser() {
		$parser = SS_ClassManifest::get_namespaced_class_parser();

		$tokens = $this->getNamespaceTokens();

		$matches = $parser->findAll($tokens);

		$classes = [];
		if($matches) foreach($matches as $match) $classes[$match['className']] = $match;

		$this->assertArrayHasKey('ClassA', $classes);
		$this->assertArrayHasKey('ClassB', $classes);
		$this->assertEquals(['ParentClassB'], $classes['ClassB']['extends']);

		$this->assertArrayHasKey('ClassC', $classes);
		$this->assertEquals(['\\', 'ParentClassC'], $classes['ClassC']['extends']);

		$this->assertArrayHasKey('ClassD', $classes);
		$this->assertEquals(['subtest', '\\', 'ParentClassD'], $classes['ClassD']['extends']);

		$this->assertArrayHasKey('ClassE', $classes);
		$this->assertContains('InterfaceE', $classes['ClassE']['interfaces']);

		$this->assertArrayHasKey('ClassF', $classes);
		$this->assertEquals(['\\', 'InterfaceF'], $classes['ClassF']['interfaces']);
	}

	public function testInterfaceDefParser() {
		$parser = SS_ClassManifest::get_interface_parser();

		$tokens = $this->getTokens();

		$matches = $parser->findAll($tokens);
		$interfaces = [];
		if($matches) foreach($matches as $match) $interfaces[$match['interfaceName']] = $match;

		$this->assertArrayHasKey('InterfaceA', $interfaces);
		$this->assertArrayHasKey('InterfaceB', $interfaces);
		$this->assertArrayHasKey('InterfaceC', $interfaces);
		$this->assertArrayHasKey('InterfaceD', $interfaces);
	}

	public function testNamespaceDefParser() {
		$parser = SS_ClassManifest::get_namespace_parser();

		$namespacedTokens = $this->getNamespaceTokens();
		$tokens = $this->getTokens();

		$namespacedMatches = $parser->findAll($namespacedTokens);
		$matches = $parser->findAll($tokens);

		$this->assertEquals([], $matches);
		$this->assertEquals(['silverstripe', '\\', 'test'], $namespacedMatches[0]['namespaceName']);
	}
}
