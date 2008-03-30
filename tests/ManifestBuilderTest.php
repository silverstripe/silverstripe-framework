<?php

class ManifestBuilderTest extends SapphireTest {
	function testManifest() {
		$baseFolder = TEMP_FOLDER . '/manifest-test';
		
		global $_CLASS_MANIFEST, $project;
		$originalClassManifest = $_CLASS_MANIFEST;
		$originalProject = $project;
		
		$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);
		Debug::show($manifestInfo);
		
		$this->assertEquals("$baseFolder/sapphire/MyClass.php", $manifestInfo['globals']['_CLASS_MANIFEST']['MyClass']);
		$this->assertEquals("$baseFolder/sapphire/subdir/SubDirClass.php", $manifestInfo['globals']['_CLASS_MANIFEST']['SubDirClass']);
		$this->assertNotContains('OtherFile', array_keys($manifestInfo['globals']['_CLASS_MANIFEST']));

		global $_CLASS_MANIFEST, $project;
		$project = $originalProject;
		$_CLASS_MANIFEST = $originalClassManifest;

		
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

	function setUp() {
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
	}

	function tearDown() { 
		// Kill the folder after we're done
		$baseFolder = TEMP_FOLDER . '/manifest-test/';
		Filesystem::removeFolder($baseFolder);
	}
	
}

?>