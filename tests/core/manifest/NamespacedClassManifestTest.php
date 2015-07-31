<?php
/**
 * Tests for the {@link SS_ClassManifest} class.
 *
 * @package framework
 * @subpackage tests
 */
class NamespacedClassManifestTest extends SapphireTest {

	protected $base;
	protected $manifest;

	public function setUp() {
		parent::setUp();

		$this->base = dirname(__FILE__) . '/fixtures/namespaced_classmanifest';
		$this->manifest      = new SS_ClassManifest($this->base, false, true, false);
		SS_ClassLoader::instance()->pushManifest($this->manifest, false);
	}

	public function tearDown() {
		parent::tearDown();
		SS_ClassLoader::instance()->popManifest();
	}

	public function testGetImportedNamespaceParser() {
		$file = file_get_contents($this->base . DIRECTORY_SEPARATOR . 'module/classes/ClassI.php');
		$tokens = token_get_all($file);
		$parsedTokens = SS_ClassManifest::get_imported_namespace_parser()->findAll($tokens);

		$expectedItems = array(
			array('ModelAdmin'),
			array('Controller', '  ', 'as', '  ', 'Cont'),
			array(
				'SS_HTTPRequest', ' ', 'as', ' ', 'Request', ',',
				'SS_HTTPResponse', ' ', 'AS', ' ', 'Response', ',',
				'PermissionProvider', ' ', 'AS', ' ', 'P',
			),
			array('silverstripe', '\\', 'test', '\\', 'ClassA'),
			array('\\', 'DataObject'),
		);

		$this->assertEquals(count($expectedItems), count($parsedTokens));

		foreach ($expectedItems as $i => $item) {
			$this->assertEquals($item, $parsedTokens[$i]['importString']);
		}
	}

	public function testGetImportsFromTokens() {
		$file = file_get_contents($this->base . DIRECTORY_SEPARATOR . 'module/classes/ClassI.php');
		$tokens = token_get_all($file);

		$method = new ReflectionMethod($this->manifest, 'getImportsFromTokens');
		$method->setAccessible(true);

		$expectedImports = array(
			'ModelAdmin',
			'Cont' => 'Controller',
			'Request' => 'SS_HTTPRequest',
			'Response' => 'SS_HTTPResponse',
			'P' => 'PermissionProvider',
			'silverstripe\test\ClassA',
			'\DataObject',
		);

		$imports = $method->invoke($this->manifest, $tokens);

		$this->assertEquals($expectedImports, $imports);

	}

	public function testClassInfoIsCorrect() {
		$this->assertContains('SilverStripe\Framework\Tests\ClassI', ClassInfo::implementorsOf('PermissionProvider'));

		//because we're using a nested manifest we have to "coalesce" the descendants again to correctly populate the
		// descendants of the core classes we want to test against - this is a limitation of the test manifest not
		// including all core classes
		$method = new ReflectionMethod($this->manifest, 'coalesceDescendants');
		$method->setAccessible(true);
		$method->invoke($this->manifest, 'ModelAdmin');

		$this->assertContains('SilverStripe\Framework\Tests\ClassI', ClassInfo::subclassesFor('ModelAdmin'));
	}

	public function testFindClassOrInterfaceFromCandidateImports() {
		$method = new ReflectionMethod($this->manifest, 'findClassOrInterfaceFromCandidateImports');
		$method->setAccessible(true);

		$this->assertTrue(ClassInfo::exists('silverstripe\test\ClassA'));

		$this->assertEquals('PermissionProvider', $method->invokeArgs($this->manifest, array(
			'\PermissionProvider',
			'Test\Namespace',
			array(
				'TestOnly',
				'Controller',
			),
		)));

		$this->assertEquals('PermissionProvider', $method->invokeArgs($this->manifest, array(
			'PermissionProvider',
			'Test\NAmespace',
			array(
				'PermissionProvider',
			)
		)));

		$this->assertEmpty($method->invokeArgs($this->manifest, array(
			'',
			'TextNamespace',
			array(
				'PermissionProvider',
			),
		)));

		$this->assertEmpty($method->invokeArgs($this->manifest, array(
			'',
			'',
			array()
		)));

		$this->assertEquals('silverstripe\test\ClassA', $method->invokeArgs($this->manifest, array(
			'ClassA',
			'Test\Namespace',
			array(
				'silverstripe\test\ClassA',
				'PermissionProvider',
			),
		)));

		$this->assertEquals('ClassA', $method->invokeArgs($this->manifest, array(
			'\ClassA',
			'Test\Namespace',
			array(
				'silverstripe\test',
			),
		)));

		$this->assertEquals('ClassA', $method->invokeArgs($this->manifest, array(
			'ClassA',
			'silverstripe\test',
			array(
				'\ClassA',
			),
		)));

		$this->assertEquals('ClassA', $method->invokeArgs($this->manifest, array(
			'Alias',
			'silverstripe\test',
			array(
				'Alias' => '\ClassA',
			),
		)));

		$this->assertEquals('silverstripe\test\ClassA', $method->invokeArgs($this->manifest, array(
			'ClassA',
			'silverstripe\test',
			array(
				'silverstripe\test\ClassB',
			),
		)));

	}

	public function testGetItemPath() {
		$expect = array(
			'SILVERSTRIPE\TEST\CLASSA'     => 'module/classes/ClassA.php',
			'Silverstripe\Test\ClassA'     => 'module/classes/ClassA.php',
			'silverstripe\test\classa'     => 'module/classes/ClassA.php',
			'SILVERSTRIPE\TEST\INTERFACEA' => 'module/interfaces/InterfaceA.php',
			'Silverstripe\Test\InterfaceA' => 'module/interfaces/InterfaceA.php',
			'silverstripe\test\interfacea' => 'module/interfaces/InterfaceA.php'
		);

		foreach ($expect as $name => $path) {
			$this->assertEquals("{$this->base}/$path", $this->manifest->getItemPath($name));
		}
	}

	public function testGetClasses() {
		$expect = array(
			'silverstripe\test\classa' => "{$this->base}/module/classes/ClassA.php",
			'silverstripe\test\classb' => "{$this->base}/module/classes/ClassB.php",
			'silverstripe\test\classc' => "{$this->base}/module/classes/ClassC.php",
			'silverstripe\test\classd' => "{$this->base}/module/classes/ClassD.php",
			'silverstripe\test\classe' => "{$this->base}/module/classes/ClassE.php",
			'silverstripe\test\classf' => "{$this->base}/module/classes/ClassF.php",
			'silverstripe\test\classg' => "{$this->base}/module/classes/ClassG.php",
			'silverstripe\test\classh' => "{$this->base}/module/classes/ClassH.php",
			'sstemplateparser'         => FRAMEWORK_PATH."/view/SSTemplateParser.php",
			'sstemplateparseexception' => FRAMEWORK_PATH."/view/SSTemplateParser.php",
			'silverstripe\framework\tests\classi' => "{$this->base}/module/classes/ClassI.php",
		);

		$this->assertEquals($expect, $this->manifest->getClasses());
	}

	public function testGetClassNames() {
		$this->assertEquals(
			array('sstemplateparser', 'sstemplateparseexception', 'silverstripe\test\classa',
				'silverstripe\test\classb', 'silverstripe\test\classc', 'silverstripe\test\classd',
				'silverstripe\test\classe', 'silverstripe\test\classf', 'silverstripe\test\classg',
				'silverstripe\test\classh', 'silverstripe\framework\tests\classi'),
			$this->manifest->getClassNames());
	}

	public function testGetDescendants() {
		$expect = array(
			'silverstripe\test\classa' => array('silverstripe\test\ClassB', 'silverstripe\test\ClassH'),
		);

		$this->assertEquals($expect, $this->manifest->getDescendants());
	}

	public function testGetDescendantsOf() {
		$expect = array(
			'SILVERSTRIPE\TEST\CLASSA' => array('silverstripe\test\ClassB', 'silverstripe\test\ClassH'),
			'silverstripe\test\classa' => array('silverstripe\test\ClassB', 'silverstripe\test\ClassH'),
		);

		foreach ($expect as $class => $desc) {
			$this->assertEquals($desc, $this->manifest->getDescendantsOf($class));
		}
	}

	public function testGetInterfaces() {
		$expect = array(
			'silverstripe\test\interfacea' => "{$this->base}/module/interfaces/InterfaceA.php",
		);
		$this->assertEquals($expect, $this->manifest->getInterfaces());
	}

	public function testGetImplementors() {
		$expect = array(
			'silverstripe\test\interfacea' => array('silverstripe\test\ClassE'),
			'interfacea' => array('silverstripe\test\ClassF'),
			'silverstripe\test\subtest\interfacea' => array('silverstripe\test\ClassG'),
			'permissionprovider' => array('SilverStripe\Framework\Tests\ClassI'),
		);
		$this->assertEquals($expect, $this->manifest->getImplementors());
	}

	public function testGetImplementorsOf() {
		$expect = array(
			'SILVERSTRIPE\TEST\INTERFACEA' => array('silverstripe\test\ClassE'),
			'silverstripe\test\interfacea' => array('silverstripe\test\ClassE'),
			'INTERFACEA' => array('silverstripe\test\ClassF'),
			'interfacea' => array('silverstripe\test\ClassF'),
			'SILVERSTRIPE\TEST\SUBTEST\INTERFACEA' => array('silverstripe\test\ClassG'),
			'silverstripe\test\subtest\interfacea' => array('silverstripe\test\ClassG'),
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
		$expect = array(
			"module" => "{$this->base}/module",
			"moduleb" => "{$this->base}/moduleb"
		);
		$this->assertEquals($expect, $this->manifest->getModules());
	}
}
