<?php

use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link ClassManifest} class.
 */
class ClassManifestTest extends SapphireTest {

	protected $base;
	protected $manifest;
	protected $manifestTests;

	public function setUp() {
		parent::setUp();

		$this->base = dirname(__FILE__) . '/fixtures/classmanifest';
		$this->manifest      = new ClassManifest($this->base, false, true, false);
		$this->manifestTests = new ClassManifest($this->base, true, true, false);
	}

	public function testGetItemPath() {
		$expect = array(
			'CLASSA'     => 'module/classes/ClassA.php',
			'ClassA'     => 'module/classes/ClassA.php',
			'classa'     => 'module/classes/ClassA.php',
			'INTERFACEA' => 'module/interfaces/InterfaceA.php',
			'InterfaceA' => 'module/interfaces/InterfaceA.php',
			'interfacea' => 'module/interfaces/InterfaceA.php',
			'TestTraitA' => 'module/traits/TestTraitA.php',
			'TestNamespace\Testing\TestTraitB' => 'module/traits/TestTraitB.php'
		);

		foreach ($expect as $name => $path) {
			$this->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
		}
	}

	public function testGetClasses() {
		$expect = array(
			'classa'                   => "{$this->base}/module/classes/ClassA.php",
			'classb'                   => "{$this->base}/module/classes/ClassB.php",
			'classc'                   => "{$this->base}/module/classes/ClassC.php",
			'classd'                   => "{$this->base}/module/classes/ClassD.php",
			'sstemplateparser'         => FRAMEWORK_PATH."/View/SSTemplateParser.php",
			'sstemplateparseexception' => FRAMEWORK_PATH."/View/SSTemplateParseException.php"
		);
		$this->assertEquals($expect, $this->manifest->getClasses());
	}

	public function testGetClassNames() {
		$this->assertEquals(
			array('sstemplateparser', 'sstemplateparseexception', 'classa', 'classb', 'classc', 'classd'),
			$this->manifest->getClassNames());
	}

	public function testGetTraitNames() {
		$this->assertEquals(
			array('testtraita', 'testnamespace\testing\testtraitb'),
			$this->manifest->getTraitNames()
		);
	}

	public function testGetDescendants() {
		$expect = array(
			'classa' => array('ClassC', 'ClassD'),
			'classc' => array('ClassD')
		);
		$this->assertEquals($expect, $this->manifest->getDescendants());
	}

	public function testGetDescendantsOf() {
		$expect = array(
			'CLASSA' => array('ClassC', 'ClassD'),
			'classa' => array('ClassC', 'ClassD'),
			'CLASSC' => array('ClassD'),
			'classc' => array('ClassD')
		);

		foreach ($expect as $class => $desc) {
			$this->assertEquals($desc, $this->manifest->getDescendantsOf($class));
		}
	}

	public function testGetInterfaces() {
		$expect = array(
			'interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
			'interfaceb' => "{$this->base}/module/interfaces/InterfaceB.php"
		);
		$this->assertEquals($expect, $this->manifest->getInterfaces());
	}

	public function testGetImplementors() {
		$expect = array(
			'interfacea' => array('ClassB'),
			'interfaceb' => array('ClassC')
		);
		$this->assertEquals($expect, $this->manifest->getImplementors());
	}

	public function testGetImplementorsOf() {
		$expect = array(
			'INTERFACEA' => array('ClassB'),
			'interfacea' => array('ClassB'),
			'INTERFACEB' => array('ClassC'),
			'interfaceb' => array('ClassC')
		);

		foreach ($expect as $interface => $impl) {
			$this->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
		}
	}

	public function testGetConfigs() {
		$expect = array("{$this->base}/module/_config.php");
		$this->assertEquals($expect, $this->manifest->getConfigs());
		$this->assertEquals($expect, $this->manifestTests->getConfigs());
	}

	public function testGetModules() {
		$expect = array(
			"module" => "{$this->base}/module",
			"moduleb" => "{$this->base}/moduleb"
		);
		$this->assertEquals($expect, $this->manifest->getModules());
		$this->assertEquals($expect, $this->manifestTests->getModules());
	}

	public function testTestManifestIncludesTestClasses() {
		$this->assertNotContains('testclassa', array_keys($this->manifest->getClasses()));
		$this->assertContains('testclassa', array_keys($this->manifestTests->getClasses()));
	}

	public function testManifestExcludeFilesPrefixedWithUnderscore() {
		$this->assertNotContains('ignore', array_keys($this->manifest->getClasses()));
	}

	/**
	 * Assert that ClassManifest throws an exception when it encounters two files
	 * which contain classes with the same name
	 * @expectedException Exception
	 */
	public function testManifestWarnsAboutDuplicateClasses() {
		$dummy = new ClassManifest(dirname(__FILE__) . '/fixtures/classmanifest_duplicates', false, true, false);
	}

}
