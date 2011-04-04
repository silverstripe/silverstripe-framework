<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class HtmlEditorFieldTest extends FunctionalTest {
	
	public static $fixture_file = 'HtmlEditorFieldTest.yml';
	
	public static $use_draft_site = true;
	
	protected $requiredExtensions = array(
		'HtmlEditorField_Toolbar' => array('HtmlEditorFieldTest_DummyImageFormFieldExtension')
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
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue(null);
		$editor->saveInto($obj);
		$this->assertEquals('', $obj->Content, "Doesn't choke on empty/null values.");
	}
	
	public function testImageInsertion() {
		$obj = new HtmlEditorFieldTest_Object();
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue('<img src="assets/example.jpg" />');
		$editor->saveInto($obj);
		
		$xml = new SimpleXMLElement($obj->Content);
		$this->assertNotNull($xml['alt'], 'Alt tags are added by default.');
		$this->assertNotNull($xml['title'], 'Title tags are added by default.');
		
		$editor->setValue('<img src="assets/example.jpg" alt="foo" title="bar" />');
		$editor->saveInto($obj);
		
		$xml = new SimpleXMLElement($obj->Content);
		$this->assertNotNull('foo', $xml['alt'], 'Alt tags are preserved.');
		$this->assertNotNull('bar', $xml['title'], 'Title tags are preserved.');
	}
	
	public function testMultiLineSaving() {
		$obj = $this->objFromFixture('HtmlEditorFieldTest_Object', 'home');
		$editor   = new HtmlEditorField('Content');
		$editor->setValue("<p>First Paragraph</p><p>Second Paragraph</p>");
		$editor->saveInto($obj);
		$this->assertEquals("<p>First Paragraph</p><p>Second Paragraph</p>", $obj->Content);
	}
	
	public function testSavingLinksWithoutHref() {
		$obj = $this->objFromFixture('HtmlEditorFieldTest_Object', 'home');
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue('<p><a name="example-anchor"></a></p>');
		$editor->saveInto($obj);
		
		$this->assertEquals (
			'<p><a name="example-anchor"/></p>', $obj->Content, 'Saving a link without a href attribute works'
		);
	}

	public function testExtendImageFormFields() {
		if(class_exists('ThumbnailStripField')) {
			$controller = new Controller();

			$toolbar = new HtmlEditorField_Toolbar($controller, 'DummyToolbar');

			$imageForm = $toolbar->ImageForm();
			$this->assertTrue(HtmlEditorFieldTest_DummyImageFormFieldExtension::$update_called);
			$this->assertEquals($imageForm->Fields(), HtmlEditorFieldTest_DummyImageFormFieldExtension::$fields);
		} else {
			$this->markTestSkipped('Test requires cms module (ThumbnailStripfield class)');
		}
		
	}
}

/**
 * @package sapphire
 * @subpackage tests
 */
class HtmlEditorFieldTest_DummyImageFormFieldExtension extends Extension implements TestOnly {
	public static $fields = null;
	public static $update_called = false;

	public function updateImageForm($form) {
		self::$update_called = true;
		self::$fields = $form->Fields();
	}
}

class HtmlEditorFieldTest_Object extends DataObject implements TestOnly {
	static $db = array(
		'Title' => 'Varchar',
		'Content' => 'HTMLText'
	);
}