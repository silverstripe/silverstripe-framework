<?php

use Filesystem as SS_Filesystem;

/**
 * @package framework
 * @subpackage tests
 */
class HTMLEditorFieldTest extends FunctionalTest {

	protected static $fixture_file = 'HTMLEditorFieldTest.yml';

	protected static $use_draft_site = true;

	protected $requiredExtensions = array(
		'HTMLEditorField_Toolbar' => array('HTMLEditorFieldTest_DummyMediaFormFieldExtension')
	);

	protected $extraDataObjects = array('HTMLEditorFieldTest_Object');

	public function setUp() {
		parent::setUp();

		// Set backend root to /HTMLEditorFieldTest
		AssetStoreTest_SpyStore::activate('HTMLEditorFieldTest');

		// Create a test files for each of the fixture references
		$files = File::get()->exclude('ClassName', 'Folder');
		foreach($files as $file) {
			$fromPath = BASE_PATH . '/framework/tests/forms/images/' . $file->Name;
			$destPath = AssetStoreTest_SpyStore::getLocalPath($file); // Only correct for test asset store
			SS_Filesystem::makeFolder(dirname($destPath));
			copy($fromPath, $destPath);
		}
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

	public function testBasicSaving() {
		$obj = new HTMLEditorFieldTest_Object();
		$editor   = new HTMLEditorField('Content');

		$editor->setValue('<p class="foo">Simple Content</p>');
		$editor->saveInto($obj);
		$this->assertEquals('<p class="foo">Simple Content</p>', $obj->Content, 'Attributes are preserved.');

		$editor->setValue('<p>Unclosed Tag');
		$editor->saveInto($obj);
		$this->assertEquals('<p>Unclosed Tag</p>', $obj->Content, 'Unclosed tags are closed.');
	}

	public function testNullSaving() {
		$obj = new HTMLEditorFieldTest_Object();
		$editor = new HTMLEditorField('Content');

		$editor->setValue(null);
		$editor->saveInto($obj);
		$this->assertEquals('', $obj->Content, "Doesn't choke on empty/null values.");
	}

	public function testResizedImageInsertion() {
		$obj = new HTMLEditorFieldTest_Object();
		$editor = new HTMLEditorField('Content');

		$fileID = $this->idFromFixture('Image', 'example_image');
		$editor->setValue(sprintf(
			'[image src="assets/HTMLEditorFieldTest_example.jpg" width="10" height="20" id="%d"]',
			$fileID
		));
		$editor->saveInto($obj);

		$parser = new CSSContentParser($obj->dbObject('Content')->forTemplate());
		$xml = $parser->getByXpath('//img');
		$this->assertEquals(
			'HTMLEditorFieldTest example',
			(string)$xml[0]['alt'],
			'Alt tags are added by default based on filename'
		);
		$this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');
		$this->assertEquals(10, (int)$xml[0]['width'], 'Width tag of resized image is set.');
		$this->assertEquals(20, (int)$xml[0]['height'], 'Height tag of resized image is set.');

		$neededFilename
			= '/assets/HTMLEditorFieldTest/f5c7c2f814/HTMLEditorFieldTest-example__ResizedImageWyIxMCIsIjIwIl0.jpg';

		$this->assertEquals($neededFilename, (string)$xml[0]['src'], 'Correct URL of resized image is set.');
		$this->assertTrue(file_exists(BASE_PATH.DIRECTORY_SEPARATOR.$neededFilename), 'File for resized image exists');
		$this->assertEquals(false, $obj->HasBrokenFile, 'Referenced image file exists.');
	}

	public function testMultiLineSaving() {
		$obj = $this->objFromFixture('HTMLEditorFieldTest_Object', 'home');
		$editor   = new HTMLEditorField('Content');
		$editor->setValue('<p>First Paragraph</p><p>Second Paragraph</p>');
		$editor->saveInto($obj);
		$this->assertEquals('<p>First Paragraph</p><p>Second Paragraph</p>', $obj->Content);
	}

	public function testSavingLinksWithoutHref() {
		$obj = $this->objFromFixture('HTMLEditorFieldTest_Object', 'home');
		$editor   = new HTMLEditorField('Content');

		$editor->setValue('<p><a name="example-anchor"></a></p>');
		$editor->saveInto($obj);

		$this->assertEquals (
			'<p><a name="example-anchor"></a></p>', $obj->Content, 'Saving a link without a href attribute works'
		);
	}

	public function testGetAnchors() {
		if (!class_exists('Page')) {
			$this->markTestSkipped();
		}
		$html = '<div name="foo"></div>
<div name=\'bar\'></div>
<div id="baz"></div>
<div id=\'bam\'></div>
<div id = "baz"></div>
<div id = ""></div>
<div id="some\'id"></div>
<div id=bar></div>';
		$expected = array(
			'foo',
			'bar',
			'baz',
			'bam',
			"some&#039;id",
		);
		$page = new Page();
		$page->Title = 'Test';
		$page->Content = $html;
		$page->write();
		$this->useDraftSite(true);

		$request = new SS_HTTPRequest('GET', '/', array(
			'PageID' => $page->ID,
		));

		$toolBar = new HTMLEditorField_Toolbar(new Controller(), 'test');
		$toolBar->setRequest($request);

		$results = json_decode($toolBar->getanchors(), true);
		$this->assertEquals($expected, $results);
	}

	public function testHTMLEditorFieldFileLocal() {
		$file = new HTMLEditorField_Image('http://domain.com/folder/my_image.jpg?foo=bar');
		$this->assertEquals('http://domain.com/folder/my_image.jpg?foo=bar', $file->URL);
		$this->assertEquals('my_image.jpg', $file->Name);
		$this->assertEquals('jpg', $file->Extension);
		// TODO Can't easily test remote file dimensions
	}

	public function testHTMLEditorFieldFileRemote() {
		$fileFixture = new File(array('Name' => 'my_local_image.jpg', 'Filename' => 'folder/my_local_image.jpg'));
		$file = new HTMLEditorField_Image('http://localdomain.com/folder/my_local_image.jpg', $fileFixture);
		$this->assertEquals('http://localdomain.com/folder/my_local_image.jpg', $file->URL);
		$this->assertEquals('my_local_image.jpg', $file->Name);
		$this->assertEquals('jpg', $file->Extension);
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class HTMLEditorFieldTest_DummyMediaFormFieldExtension extends Extension implements TestOnly {
	public static $fields = null;
	public static $update_called = false;

	public function updateImageForm($form) {
		self::$update_called = true;
		self::$fields = $form->Fields();
	}
}

class HTMLEditorFieldTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar',
		'Content' => 'HTMLText',
		'HasBrokenFile' => 'Boolean'
	);
}
