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
		
		$editor->setValue('<img src="assets/example.jpg" />');
		$editor->saveInto($obj);

		$parser = new CSSContentParser($obj->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('', (string)$xml[0]['alt'], 'Alt tags are added by default.');
		$this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');

		$editor->setValue('<img src="assets/example.jpg" alt="foo" title="bar" />');
		$editor->saveInto($obj);

		$parser = new CSSContentParser($obj->Content);
		$xml = $parser->getByXpath('//img');
		$this->assertEquals('foo', (string)$xml[0]['alt'], 'Alt tags are preserved.');
		$this->assertEquals('bar', (string)$xml[0]['title'], 'Title tags are preserved.');
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
		'Content' => 'HTMLText'
	);
}
