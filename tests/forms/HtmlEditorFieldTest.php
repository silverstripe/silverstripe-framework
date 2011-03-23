<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class HtmlEditorFieldTest extends FunctionalTest {
	
	public static $fixture_file = 'sapphire/tests/forms/HtmlEditorFieldTest.yml';
	
	public static $use_draft_site = true;
	
	protected $requiredExtensions = array(
		'HtmlEditorField_Toolbar' => array('HtmlEditorFieldTest_DummyImageFormFieldExtension')
	);
	
	public function testBasicSaving() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue('<p class="foo">Simple Content</p>');
		$editor->saveInto($sitetree);
		$this->assertEquals('<p class="foo">Simple Content</p>', $sitetree->Content, 'Attributes are preserved.');
		
		$editor->setValue('<p>Unclosed Tag');
		$editor->saveInto($sitetree);
		$this->assertEquals('<p>Unclosed Tag</p>', $sitetree->Content, 'Unclosed tags are closed.');
	}
	
	public function testNullSaving() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$this->assertEquals('', $sitetree->Content, "Doesn't choke on empty/null values.");
	}
	
	public function testImageInsertion() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue('<img src="assets/example.jpg" />');
		$editor->saveInto($sitetree);
		
		$xml = new SimpleXMLElement($sitetree->Content);
		$this->assertNotNull($xml['alt'], 'Alt tags are added by default.');
		$this->assertNotNull($xml['title'], 'Title tags are added by default.');
		
		$editor->setValue('<img src="assets/example.jpg" alt="foo" title="bar" />');
		$editor->saveInto($sitetree);
		
		$xml = new SimpleXMLElement($sitetree->Content);
		$this->assertNotNull('foo', $xml['alt'], 'Alt tags are preserved.');
		$this->assertNotNull('bar', $xml['title'], 'Title tags are preserved.');
	}
	
	public function testMultiLineSaving() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		$editor->setValue("<p>First Paragraph</p><p>Second Paragraph</p>");
		$editor->saveInto($sitetree);
		$this->assertEquals("<p>First Paragraph</p><p>Second Paragraph</p>", $sitetree->Content);
	}
	
	public function testSavingLinksWithoutHref() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue('<p><a name="example-anchor"></a></p>');
		$editor->saveInto($sitetree);
		
		$this->assertEquals (
			'<p><a name="example-anchor"/></p>', $sitetree->Content, 'Saving a link without a href attribute works'
		);
	}

	public function testExtendImageFormFields() {
		$controller = new ContentController();

		$toolbar = new HtmlEditorField_Toolbar($controller, 'DummyToolbar');

		$imageForm = $toolbar->ImageForm();
		$this->assertTrue(HtmlEditorFieldTest_DummyImageFormFieldExtension::$update_called);
		$this->assertEquals($imageForm->Fields(), HtmlEditorFieldTest_DummyImageFormFieldExtension::$fields);
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
		'Title' => 'Varchar'
	);
}