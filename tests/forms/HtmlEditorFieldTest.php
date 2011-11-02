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
	
	public function testLinkTracking() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		
		$aboutID   = $this->idFromFixture('SiteTree', 'about');
		$contactID = $this->idFromFixture('SiteTree', 'contact');
		
		$editor->setValue("<a href=\"[sitetree_link id=$aboutID]\">Example Link</a>");
		$editor->saveInto($sitetree);
		$this->assertEquals(array($aboutID => $aboutID), $sitetree->LinkTracking()->getIdList(), 'Basic link tracking works.');
		
		$editor->setValue (
			"<a href=\"[sitetree_link id=$aboutID]\"></a><a href=\"[sitetree_link id=$contactID]\"></a>"
		);
		$editor->saveInto($sitetree);
		$this->assertEquals (
			array($aboutID => $aboutID, $contactID => $contactID),
			$sitetree->LinkTracking()->getIdList(),
			'Tracking works on multiple links'
		);
		
		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$this->assertEquals(array(), $sitetree->LinkTracking()->getIdList(), 'Link tracking is removed when links are.');
	}
	
	public function testFileLinkTracking() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		$fileID   = $this->idFromFixture('File', 'example_file');
		
		$editor->setValue('<a href="assets/example.pdf">Example File</a>');
		$editor->saveInto($sitetree);
		$this->assertEquals (
			array($fileID => $fileID), $sitetree->ImageTracking()->getIDList(), 'Links to assets are tracked.'
		);
		
		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$this->assertEquals(array(), $sitetree->ImageTracking()->getIdList(), 'Asset tracking is removed with links.');
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
	
	public function testImageTracking() {
		$sitetree = $this->objFromFixture('SiteTree', 'home');
		$editor   = new HtmlEditorField('Content');
		$fileID   = $this->idFromFixture('Image', 'example_image');
		
		$editor->setValue('<img src="assets/example.jpg" />');
		$editor->saveInto($sitetree);
		$this->assertEquals (
			array($fileID => $fileID), $sitetree->ImageTracking()->getIDList(), 'Inserted images are tracked.'
		);
		
		$editor->setValue(null);
		$editor->saveInto($sitetree);
		$this->assertEquals (
			array(), $sitetree->ImageTracking()->getIDList(), 'Tracked images are deleted when removed.'
		);
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
	
	public function testBrokenLinkTracking() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');
		
		$this->assertFalse((bool) $sitetree->HasBrokenLink);
		
		$editor->setValue('<p><a href="[sitetree_link id=0]">Broken Link</a></p>');
		$editor->saveInto($sitetree);
		
		$this->assertTrue($sitetree->HasBrokenLink);
		
		$editor->setValue(sprintf (
			'<p><a href="[sitetree_link id=%d]">Working Link</a></p>',
			$this->idFromFixture('SiteTree', 'home')
		));
		$sitetree->HasBrokenLink = false;
		$editor->saveInto($sitetree);
		
		$this->assertFalse((bool) $sitetree->HasBrokenLink);
	}
	
	public function testBrokenLinkHighlighting() {
		$sitetree = new SiteTree();
		$editor   = new HtmlEditorField('Content');
		
		$editor->setValue('<a href="[sitetree_link id=0]">Broken Link</a>');
		
		$element = new SimpleXMLElement(html_entity_decode((string) new SimpleXMLElement($editor->Field())));
		$this->assertContains('ss-broken', (string) $element['class'], 'A broken link class is added to broken links');
		
		$editor->setValue(sprintf (
			'<a href="[sitetree_link id=%d]">Working Link</a>',
			$this->idFromFixture('SiteTree', 'home')
		));
		
		$element = new SimpleXMLElement(html_entity_decode((string) new SimpleXMLElement($editor->Field())));
		$this->assertNotContains('ss-broken', (string) $element['class']);
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