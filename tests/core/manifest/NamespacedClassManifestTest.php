<?php
/**
 * Tests for the {@link SS_ClassManifest} class.
 *
 * @package    sapphire
 * @subpackage tests
 */
class NamespacedClassManifestTest extends SapphireTest {

	protected $base;
	protected $manifest;

	public function setUp() {
		parent::setUp();
		
		if(version_compare(PHP_VERSION, '5.3', '<')) {
			$this->markTestSkipped('Namespaces are not supported before PHP 5.3');
		}
		
		$this->base = dirname(__FILE__) . '/fixtures/namespaced_classmanifest';
		$this->manifest      = new SS_ClassManifest($this->base, false, true, false);
	}

	public function testGetItemPath() {
		$expect = array(
			'SAPPHIRE\TEST\CLASSA'     => 'module/classes/ClassA.php',
			'Sapphire\Test\ClassA'     => 'module/classes/ClassA.php',
			'sapphire\test\classa'     => 'module/classes/ClassA.php',
			'SAPPHIRE\TEST\INTERFACEA' => 'module/interfaces/InterfaceA.php',
			'Sapphire\Test\InterfaceA' => 'module/interfaces/InterfaceA.php',
			'sapphire\test\interfacea' => 'module/interfaces/InterfaceA.php'
		);

		foreach ($expect as $name => $path) {
			$this->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
		}
	}

	public function testGetClasses() {
		$expect = array(
			'sapphire\test\classa' => "{$this->base}/module/classes/ClassA.php",
			'sapphire\test\classb' => "{$this->base}/module/classes/ClassB.php",
			'sapphire\test\classc' => "{$this->base}/module/classes/ClassC.php",
			'sapphire\test\classd' => "{$this->base}/module/classes/ClassD.php",
			'sapphire\test\classe' => "{$this->base}/module/classes/ClassE.php",
			'sapphire\test\classf' => "{$this->base}/module/classes/ClassF.php",
			'sapphire\test\classg' => "{$this->base}/module/classes/ClassG.php"
		);
		
		$this->assertEquals($expect, $this->manifest->getClasses());
	}

	public function testGetClassNames() {
		$this->assertEquals(
			array('sapphire\test\classa', 'sapphire\test\classb', 'sapphire\test\classc', 'sapphire\test\classd', 'sapphire\test\classe', 'sapphire\test\classf', 'sapphire\test\classg'),
			$this->manifest->getClassNames());
	}

	public function testGetDescendants() {
		$expect = array(
			'sapphire\test\classa' => array('sapphire\test\ClassB')
		);
		
		$this->assertEquals($expect, $this->manifest->getDescendants());
	}

	public function testGetDescendantsOf() {
		$expect = array(
			'SAPPHIRE\TEST\CLASSA' => array('sapphire\test\ClassB'),
			'sapphire\test\classa' => array('sapphire\test\ClassB'),
		);

		foreach ($expect as $class => $desc) {
			$this->assertEquals($desc, $this->manifest->getDescendantsOf($class));
		}
	}

	public function testGetInterfaces() {
		$expect = array(
			'sapphire\test\interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
		);
		$this->assertEquals($expect, $this->manifest->getInterfaces());
	}

	public function testGetImplementors() {
		$expect = array(
			'sapphire\test\interfacea' => array('sapphire\test\ClassE'),
			'interfacea' => array('sapphire\test\ClassF'),
			'sapphire\test\subtest\interfacea' => array('sapphire\test\ClassG')
		);
		$this->assertEquals($expect, $this->manifest->getImplementors());
	}

	public function testGetImplementorsOf() {
		$expect = array(
			'SAPPHIRE\TEST\INTERFACEA' => array('sapphire\test\ClassE'),
			'sapphire\test\interfacea' => array('sapphire\test\ClassE'),
			'INTERFACEA' => array('sapphire\test\ClassF'),
			'interfacea' => array('sapphire\test\ClassF'),
			'SAPPHIRE\TEST\SUBTEST\INTERFACEA' => array('sapphire\test\ClassG'),
			'sapphire\test\subtest\interfacea' => array('sapphire\test\ClassG'),
		);

		foreach ($expect as $interface => $impl) {
			$this->assertEquals($impl, $this->manifest->getImplementorsOf($interface));
		}
	}

	public function testGetConfigs() {
		$expect = array("{$this->base}/module/_config.php");
		$this->assertEquals($expect, $this->manifest->getConfigs());
	}
	
	public function testGetModules() {
		$expect = array("module" => "{$this->base}/module");
		$this->assertEquals($expect, $this->manifest->getModules());
	}
}