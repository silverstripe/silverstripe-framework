<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use Page;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField_Toolbar;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField_Image;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest\DummyMediaFormFieldExtension;
use SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest\TestObject;
use SilverStripe\ORM\FieldType\DBHTMLText;

class HTMLEditorFieldTest extends FunctionalTest
{

    protected static $fixture_file = 'HTMLEditorFieldTest.yml';

    protected static $use_draft_site = true;

    protected static $required_extensions= array(
        HTMLEditorField_Toolbar::class => array(
            DummyMediaFormFieldExtension::class,
        ),
    );

    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected function setUp()
    {
        parent::setUp();

        // Set backend root to /HTMLEditorFieldTest
        TestAssetStore::activate('HTMLEditorFieldTest');

        // Set the File Name Filter replacements so files have the expected names
        Config::inst()->update(
            FileNameFilter::class,
            'default_replacements',
            array(
            '/\s/' => '-', // remove whitespace
            '/_/' => '-', // underscores to dashes
            '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
            '/[\-]{2,}/' => '-', // remove duplicate dashes
            '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
            )
        );

        // Create a test files for each of the fixture references
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $fromPath = __DIR__ . '/HTMLEditorFieldTest/images/' . $file->Name;
            $destPath = TestAssetStore::getLocalPath($file); // Only correct for test asset store
            Filesystem::makeFolder(dirname($destPath));
            copy($fromPath, $destPath);
        }
    }

    protected function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testCasting()
    {
        // Test special characters
        $inputText = "These are some unicodes: ä, ö, & ü";
        $field = new HTMLEditorField("Test", "Test");
        $field->setValue($inputText);
        $this->assertContains('These are some unicodes: &auml;, &ouml;, &amp; &uuml;', $field->Field());
        // Test shortcodes
        $inputText = "Shortcode: [file_link id=4]";
        $field = new HTMLEditorField("Test", "Test");
        $field->setValue($inputText);
        $this->assertContains('Shortcode: [file_link id=4]', $field->Field());
    }

    public function testBasicSaving()
    {
        $obj = new TestObject();
        $editor   = new HTMLEditorField('Content');

        $editor->setValue('<p class="foo">Simple Content</p>');
        $editor->saveInto($obj);
        $this->assertEquals('<p class="foo">Simple Content</p>', $obj->Content, 'Attributes are preserved.');

        $editor->setValue('<p>Unclosed Tag');
        $editor->saveInto($obj);
        $this->assertEquals('<p>Unclosed Tag</p>', $obj->Content, 'Unclosed tags are closed.');
    }

    public function testNullSaving()
    {
        $obj = new TestObject();
        $editor = new HTMLEditorField('Content');

        $editor->setValue(null);
        $editor->saveInto($obj);
        $this->assertEquals('', $obj->Content, "Doesn't choke on empty/null values.");
    }

    public function testResizedImageInsertion()
    {
        $obj = new TestObject();
        $editor = new HTMLEditorField('Content');

        $fileID = $this->idFromFixture(Image::class, 'example_image');
        $editor->setValue(
            sprintf(
                '[image src="assets/example.jpg" width="10" height="20" id="%d"]',
                $fileID
            )
        );
        $editor->saveInto($obj);

        $parser = new CSSContentParser($obj->dbObject('Content')->forTemplate());
        $xml = $parser->getByXpath('//img');
        $this->assertEquals(
            'example',
            (string)$xml[0]['alt'],
            'Alt tags are added by default based on filename'
        );
        $this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');
        $this->assertEquals(10, (int)$xml[0]['width'], 'Width tag of resized image is set.');
        $this->assertEquals(20, (int)$xml[0]['height'], 'Height tag of resized image is set.');

        $neededFilename
            = '/assets/HTMLEditorFieldTest/f5c7c2f814/example__ResizedImageWyIxMCIsIjIwIl0.jpg';

        $this->assertEquals($neededFilename, (string)$xml[0]['src'], 'Correct URL of resized image is set.');
        $this->assertTrue(file_exists(BASE_PATH.DIRECTORY_SEPARATOR.$neededFilename), 'File for resized image exists');
        $this->assertEquals(false, $obj->HasBrokenFile, 'Referenced image file exists.');
    }

    public function testMultiLineSaving()
    {
        $obj = $this->objFromFixture(TestObject::class, 'home');
        $editor   = new HTMLEditorField('Content');
        $editor->setValue('<p>First Paragraph</p><p>Second Paragraph</p>');
        $editor->saveInto($obj);
        $this->assertEquals('<p>First Paragraph</p><p>Second Paragraph</p>', $obj->Content);
    }

    public function testSavingLinksWithoutHref()
    {
        $obj = $this->objFromFixture(TestObject::class, 'home');
        $editor   = new HTMLEditorField('Content');

        $editor->setValue('<p><a name="example-anchor"></a></p>');
        $editor->saveInto($obj);

        $this->assertEquals(
            '<p><a name="example-anchor"></a></p>',
            $obj->Content,
            'Saving a link without a href attribute works'
        );
    }

    public function testGetAnchors()
    {
        if (!class_exists('Page')) {
            $this->markTestSkipped();
        }
        $linkedPage = new Page();
        $linkedPage->Title = 'Dummy';
        $linkedPage->write();

        $html = <<<EOS
<div name="foo"></div>
<div name='bar'></div>
<div id="baz"></div>
[sitetree_link id="{$linkedPage->ID}"]
<div id='bam'></div>
<div id = "baz"></div>
<div id = ""></div>
<div id="some'id"></div>
<div id=bar></div>
EOS
        ;
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

        $request = new HTTPRequest(
            'GET',
            '/',
            array(
            'PageID' => $page->ID,
            )
        );

        $toolBar = new HTMLEditorField_Toolbar(new Controller(), 'test');
        $toolBar->setRequest($request);

        $results = json_decode($toolBar->getanchors(), true);
        $this->assertEquals($expected, $results);
    }

    public function testHTMLEditorFieldFileLocal()
    {
        $file = new HTMLEditorField_Image('http://domain.com/folder/my_image.jpg?foo=bar');
        $this->assertEquals('http://domain.com/folder/my_image.jpg?foo=bar', $file->URL);
        $this->assertEquals('my_image.jpg', $file->Name);
        $this->assertEquals('jpg', $file->Extension);
        // TODO Can't easily test remote file dimensions
    }

    public function testHTMLEditorFieldFileRemote()
    {
        $fileFixture = new File(array('Name' => 'my_local_image.jpg', 'Filename' => 'folder/my_local_image.jpg'));
        $file = new HTMLEditorField_Image('http://localdomain.com/folder/my_local_image.jpg', $fileFixture);
        $this->assertEquals('http://localdomain.com/folder/my_local_image.jpg', $file->URL);
        $this->assertEquals('my_local_image.jpg', $file->Name);
        $this->assertEquals('jpg', $file->Extension);
    }

    public function testReadonlyField()
    {
        $editor = new HTMLEditorField('Content');
        $fileID = $this->idFromFixture(Image::class, 'example_image');
        $editor->setValue(
            sprintf(
                '[image src="assets/example.jpg" width="10" height="20" id="%d"]',
                $fileID
            )
        );
        /**
 * @var HTMLReadonlyField $readonly
*/
        $readonly = $editor->performReadonlyTransformation();
        /**
 * @var DBHTMLText $readonlyContent
*/
        $readonlyContent = $readonly->Field();

        $this->assertEquals(
            <<<EOS
<span class="readonly typography" id="Content">
	<img src="/assets/HTMLEditorFieldTest/f5c7c2f814/example__ResizedImageWyIxMCIsIjIwIl0.jpg" alt="example" width="10" height="20">
</span>


EOS
            ,
            $readonlyContent->getValue()
        );

        // Test with include input tag
        $readonly = $editor->performReadonlyTransformation()
            ->setIncludeHiddenField(true);
        /**
 * @var DBHTMLText $readonlyContent
*/
        $readonlyContent = $readonly->Field();
        $this->assertEquals(
            <<<EOS
<span class="readonly typography" id="Content">
	<img src="/assets/HTMLEditorFieldTest/f5c7c2f814/example__ResizedImageWyIxMCIsIjIwIl0.jpg" alt="example" width="10" height="20">
</span>

	<input type="hidden" name="Content" value="[image src=&quot;/assets/HTMLEditorFieldTest/f5c7c2f814/example.jpg&quot; width=&quot;10&quot; height=&quot;20&quot; id=&quot;{$fileID}&quot;]" />


EOS
            ,
            $readonlyContent->getValue()
        );
    }
}
