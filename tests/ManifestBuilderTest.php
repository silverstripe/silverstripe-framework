<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ManifestBuilderTest extends SapphireTest {
	function testManifest() {
		$baseFolder = TEMP_FOLDER . '/manifest-test';
		$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);
		global $project;
		
		$this->assertEquals("$baseFolder/sapphire/MyClass.php", $manifestInfo['globals']['_CLASS_MANIFEST']['MyClass']);
		$this->assertEquals("$baseFolder/sapphire/subdir/SubDirClass.php", $manifestInfo['globals']['_CLASS_MANIFEST']['SubDirClass']);
		$this->assertNotContains('OtherFile', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));

		$this->assertContains('MyClass', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertContains('MyClass_Other', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertContains('MyClass_Final', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertContains('MyClass_ClassBetweenTwoStrings', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		
		// Check aspects of PHP file
		$manifest = ManifestBuilder::generate_php_file($manifestInfo);
		// Debug::message($manifest);
		$this->assertEquals(1, preg_match('/^<\?php/', $manifest), "Starts with <?php");
		$this->assertEquals(1, preg_match('/\$_CLASS_MANIFEST\s*=\s*array/m', $manifest), "\$_CLASS_MANIFEST exists");
		$this->assertEquals(1, preg_match('/\$_TEMPLATE_MANIFEST\s*=\s*array/m', $manifest), "\$_TEMPLATE_MANIFEST exists");
		$this->assertEquals(1, preg_match('/\$_CSS_MANIFEST\s*=\s*array/m', $manifest), "\$_CSS_MANIFEST exists");
		$this->assertEquals(1, preg_match('/\$_ALL_CLASSES\s*=\s*array/m', $manifest), "\$_ALL_CLASSES exists");

		$this->assertEquals(1, preg_match('/require_once\("[^"]+rahbeast\/_config.php"\);/i', $manifest), "rahbeast/_config.php included");
		$this->assertEquals(1, preg_match('/require_once\("[^"]+sapphire\/_config.php"\);/i', $manifest), "sapphire/_config.php included");
	}
	
	
	function testManifestIgnoresClassesInComments() {
		$baseFolder = TEMP_FOLDER . '/manifest-test';
		global $project;

		$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);

		/* Our fixture defines the class MyClass_InComment inside a comment, so it shouldn't be included in the class manifest. */
		$this->assertNotContains('MyClass_InComment', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));
		$this->assertNotContains('MyClass_InComment', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertNotContains('MyClass_InComment', array_keys($manifestInfo['globals']['_ALL_CLASSES']['parents']));

		/* Our fixture defines the class MyClass_InSlashSlashComment inside a //-style comment, so it shouldn't be included in the class manifest. */
		$this->assertNotContains('MyClass_InSlashSlashComment', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));
		$this->assertNotContains('MyClass_InSlashSlashComment', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertNotContains('MyClass_InSlashSlashComment', array_keys($manifestInfo['globals']['_ALL_CLASSES']['parents']));
	}

	function testManifestIgnoresClassesInStrings() {
		$baseFolder = TEMP_FOLDER . '/manifest-test';
		$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);

		/* If a class defintion is listed in a single quote string, then it shouldn't be inlcuded.  Here we have put a class definition for  MyClass_InSingleQuoteString inside a single-quoted string */
		$this->assertNotContains('MyClass_InSingleQuoteString', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));
		$this->assertNotContains('MyClass_InSingleQuoteString', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertNotContains('MyClass_InSingleQuoteString', array_keys($manifestInfo['globals']['_ALL_CLASSES']['parents']));

		/* Ditto for double quotes.  Here we have put a class definition for MyClass_InDoubleQuoteString inside a double-quoted string.  */
		$this->assertNotContains('MyClass_InDoubleQuoteString', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));
		$this->assertNotContains('MyClass_InDoubleQuoteString', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertNotContains('MyClass_InDoubleQuoteString', array_keys($manifestInfo['globals']['_ALL_CLASSES']['parents']));

		/* Finally, we need to ensure that class definitions inside heredoc strings aren't included.  Here, we have defined the class MyClass_InHeredocString inside a heredoc string. */
		$this->assertNotContains('MyClass_InHeredocString', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));
		$this->assertNotContains('MyClass_InHeredocString', array_keys($manifestInfo['globals']['_ALL_CLASSES']['exists']));
		$this->assertNotContains('MyClass_InHeredocString', array_keys($manifestInfo['globals']['_ALL_CLASSES']['parents']));
	}

	
	protected $originalClassManifest, $originalProject, $originalAllClasses;
	protected static $test_fixture_project;

	function setUp() {
		parent::setUp();
		
		// Trick the auto-loder into loading this class before we muck with the manifest
		new TokenisedRegularExpression(null);
		
		include('tests/ManifestBuilderTest.fixture.inc');		

		// Build the fixture specified above
		$baseFolder = TEMP_FOLDER . '/manifest-test/';

		if(file_exists($baseFolder)) Filesystem::removeFolder($baseFolder);
		mkdir($baseFolder);

		foreach($filesystemFixture as $i => $item) {
			if(is_numeric($i)) {
				$itemContent = null;
			} else {
				$itemContent = $item;
				$item = $i;
			}

			// Directory
			if(substr($item,-1) == '/') {
				mkdir($baseFolder . $item);
			} else {
				touch($baseFolder . $item);
				if($itemContent) {
					$fh = fopen($baseFolder . $item, 'w');
					fwrite($fh, $itemContent);
					fclose($fh);
				}
			}
		}

		global $_CLASS_MANIFEST, $_ALL_CLASSES, $project;
		
		$this->originalAllClasses = $_ALL_CLASSES;
		$this->originalClassManifest = $_CLASS_MANIFEST;
		$this->originalProject = $project;

		// Because it's difficult to run multiple tests on a piece of code that uses require_once, we keep a copy of the
		// $project value.
		if(self::$test_fixture_project) $project = self::$test_fixture_project;
		
		global $project;
	}

	function tearDown() { 
		global $_CLASS_MANIFEST, $_ALL_CLASSES, $project;

		if(!self::$test_fixture_project) self::$test_fixture_project = $project;
		
		$project = $this->originalProject;
		$_CLASS_MANIFEST = $this->originalClassManifest;
		$_ALL_CLASSES = $this->originalAllClasses;

		// Kill the folder after we're done
		$baseFolder = TEMP_FOLDER . '/manifest-test/';
		Filesystem::removeFolder($baseFolder);
		
		parent::tearDown();
	}
	
}

?>