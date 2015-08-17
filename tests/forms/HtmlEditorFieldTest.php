<?php
/**
 * @package framework
 * @subpackage tests
 */
class HtmlEditorFieldTest extends FunctionalTest {

	protected static $fixture_file = 'HtmlEditorFieldTest.yml';

	protected static $use_draft_site = true;

	protected $requiredExtensions = array(
		'HtmlEditorField_Toolbar' => array('HtmlEditorFieldTest_DummyMediaFormFieldExtension')
	);

	protected $extraDataObjects = array('HtmlEditorFieldTest_Object');

	public function testBasicSaving() {
		$obj = new HtmlEditorFieldTest_Object();
		$editor   = new HtmlEditorField('Content');

		$editor->setValue('<p class="foo">Simple Content</p>');
		$editor->saveInto($obj);
		$this->assertEquals('<p class="foo">Simple Content</p>', $obj->Content, 'Attributes are preserved.');

		$editor->setValue('<p>Unclosed Tag');
		$editor->saveInto($obj);
		$this->assertEquals('<p>Unclosed Tag</p>', $obj->Content, 'Unclosed tags are closed.');
	}

	public function testNullSaving() {
		$obj = new HtmlEditorFieldTest_Object();
		$editor = new HtmlEditorField('Content');

		$editor->setValue(null);
		$editor->saveInto($obj);
		$this->assertEquals('', $obj->Content, "Doesn't choke on empty/null values.");
	}

	public function testImageInsertion() {
		$obj = new HtmlEditorFieldTest_Object();
		$editor = new HtmlEditorField('Content');

		$editor->setValue('<img src="assets/HTMLEditorFieldTest_example.jpg" />');
		$editor->saveInto($obj);

		$parser = new CSSContentParser($obj->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('', (string)$xml[0]['alt'], 'Alt tags are added by default.');
		$this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');

		$editor->setValue('<img src="assets/HTMLEditorFieldTest_example.jpg" alt="foo" title="bar" />');
		$editor->saveInto($obj);

		$parser = new CSSContentParser($obj->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('foo', (string)$xml[0]['alt'], 'Alt tags are preserved.');
		$this->assertEquals('bar', (string)$xml[0]['title'], 'Title tags are preserved.');
		$this->assertEquals(false, $obj->HasBrokenFile, 'Referenced image file exists.');
	}

	public function testResizedImageInsertion() {
		$obj = new HtmlEditorFieldTest_Object();
		$editor = new HtmlEditorField('Content');

		/*
		 * Following stuff is neccessary to
		 *     a) use the proper filename for the image we are referencing
		 *     b) not confuse the "existing" filesystem by our test
		 */
		$imageFile = $this->objFromFixture('Image', 'example_image');
		$imageFile->Filename = FRAMEWORK_DIR . '/' . $imageFile->Filename;
		$origUpdateFilesystem = Config::inst()->get('File', 'update_filesystem');
		Config::inst()->update('File', 'update_filesystem', false);
		$imageFile->write();
		Config::inst()->update('File', 'update_filesystem', $origUpdateFilesystem);
		/*
		 * End of test bet setting
		 */

		$editor->setValue('<img src="assets/HTMLEditorFieldTest_example.jpg" width="10" height="20" />');
		$editor->saveInto($obj);

		$parser = new CSSContentParser($obj->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('', (string)$xml[0]['alt'], 'Alt tags are added by default.');
		$this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');
		$this->assertEquals(10, (int)$xml[0]['width'], 'Width tag of resized image is set.');
		$this->assertEquals(20, (int)$xml[0]['height'], 'Height tag of resized image is set.');

		$neededFilename = 'assets/_resampled/ResizedImage' . Convert::base64url_encode(array(10,20)) .
			'-HTMLEditorFieldTest_example.jpg';

		$this->assertEquals($neededFilename, (string)$xml[0]['src'], 'Correct URL of resized image is set.');
		$this->assertTrue(file_exists(BASE_PATH.DIRECTORY_SEPARATOR.$neededFilename), 'File for resized image exists');
		$this->assertEquals(false, $obj->HasBrokenFile, 'Referenced image file exists.');
	}

	public function testMultiLineSaving() {
		$obj = $this->objFromFixture('HtmlEditorFieldTest_Object', 'home');
		$editor   = new HtmlEditorField('Content');
		$editor->setValue('<p>First Paragraph</p><p>Second Paragraph</p>');
		$editor->saveInto($obj);
		$this->assertEquals('<p>First Paragraph</p><p>Second Paragraph</p>', $obj->Content);
	}

	public function testSavingLinksWithoutHref() {
		$obj = $this->objFromFixture('HtmlEditorFieldTest_Object', 'home');
		$editor   = new HtmlEditorField('Content');

		$editor->setValue('<p><a name="example-anchor"></a></p>');
		$editor->saveInto($obj);

		$this->assertEquals (
			'<p><a name="example-anchor"></a></p>', $obj->Content, 'Saving a link without a href attribute works'
		);
	}

	public function testHtmlEditorFieldFileLocal() {
		$file = new HtmlEditorField_File('http://domain.com/folder/my_image.jpg?foo=bar');
		$this->assertEquals('http://domain.com/folder/my_image.jpg?foo=bar', $file->URL);
		$this->assertEquals('my_image.jpg', $file->Name);
		$this->assertEquals('jpg', $file->Extension);
		// TODO Can't easily test remote file dimensions
	}

	public function testHtmlEditorFieldFileRemote() {
		$fileFixture = new File(array('Name' => 'my_local_image.jpg', 'Filename' => 'folder/my_local_image.jpg'));
		$file = new HtmlEditorField_File('http://localdomain.com/folder/my_local_image.jpg', $fileFixture);
		$this->assertEquals('http://localdomain.com/folder/my_local_image.jpg', $file->URL);
		$this->assertEquals('my_local_image.jpg', $file->Name);
		$this->assertEquals('jpg', $file->Extension);
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class HtmlEditorFieldTest_DummyMediaFormFieldExtension extends Extension implements TestOnly {
	public static $fields = null;
	public static $update_called = false;

	public function updateImageForm($form) {
		self::$update_called = true;
		self::$fields = $form->Fields();
	}
}

class HtmlEditorFieldTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar',
		'Content' => 'HTMLText',
		'HasBrokenFile' => 'Boolean'
	);
}
