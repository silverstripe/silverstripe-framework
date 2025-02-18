<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest\TestObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HTMLEditor\TextAreaConfig;

class HTMLEditorFieldTest extends FunctionalTest
{
    protected static $fixture_file = 'HTMLEditorFieldTest.yml';

    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Set backend root to /HTMLEditorFieldTest
        TestAssetStore::activate('HTMLEditorFieldTest');

        // Set the File Name Filter replacements so files have the expected names
        Config::modify()->set(
            FileNameFilter::class,
            'default_replacements',
            [
                '/\s/' => '-', // remove whitespace
                '/_/' => '-', // underscores to dashes
                '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
                '/[\-]{2,}/' => '-', // remove duplicate dashes
                '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
            ]
        );

        // Create a test files for each of the fixture references
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $fromPath = __DIR__ . '/HTMLEditorFieldTest/images/' . $file->Name;
            $destPath = TestAssetStore::getLocalPath($file); // Only correct for test asset store
            Filesystem::makeFolder(dirname($destPath ?? ''));
            copy($fromPath ?? '', $destPath ?? '');
        }

        // Make sure we're using the TextAreaConfig even if a module provides an alternative.
        Injector::inst()->load([
            HTMLEditorConfig::class => [
                'class' => TextAreaConfig::class,
            ]
        ]);
    }

    protected function tearDown(): void
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
        $this->assertStringContainsString('These are some unicodes: ä, ö, & ü', $field->Field());
        // Test shortcodes
        $inputText = "Shortcode: [file_link id=4]";
        $field = new HTMLEditorField("Test", "Test");
        $field->setValue($inputText);
        $this->assertStringContainsString('Shortcode: [file_link id=4]', $field->Field());
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
            '',
            (string)$xml[0]['alt'],
            'Alt attribute is always present, even if empty'
        );
        $this->assertEquals('', (string)$xml[0]['title'], 'Title tags are added by default.');
        $this->assertEquals(10, (int)$xml[0]['width'], 'Width tag of resized image is set.');
        $this->assertEquals(20, (int)$xml[0]['height'], 'Height tag of resized image is set.');

        $neededFilename
            = '/assets/HTMLEditorFieldTest/f5c7c2f814/example__ResizedImageWzEwLDIwXQ.jpg';

        $this->assertEquals($neededFilename, (string)$xml[0]['src'], 'Correct URL of resized image is set.');
        $this->assertTrue(file_exists(PUBLIC_PATH . DIRECTORY_SEPARATOR . $neededFilename), 'File for resized image exists');
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
        /** @var HTMLReadonlyField $readonly */
        $readonly = $editor->performReadonlyTransformation();
        /** @var DBHTMLText $readonlyContent */
        $readonlyContent = $readonly->Field();

        $this->assertEquals(
            <<<EOS
<span class="readonly typography" id="Content">
	<img width="10" height="20" alt="" src="/assets/HTMLEditorFieldTest/f5c7c2f814/example__ResizedImageWzEwLDIwXQ.jpg" loading="lazy">


</span>


EOS
            ,
            $readonlyContent->getValue()
        );

        // Test with include input tag
        $readonly = $editor->performReadonlyTransformation()
            ->setIncludeHiddenField(true);
        /** @var DBHTMLText $readonlyContent */
        $readonlyContent = $readonly->Field();
        $this->assertEquals(
            <<<EOS
<span class="readonly typography" id="Content">
	<img width="10" height="20" alt="" src="/assets/HTMLEditorFieldTest/f5c7c2f814/example__ResizedImageWzEwLDIwXQ.jpg" loading="lazy">


</span>

	<input type="hidden" name="Content" value="[image src=&quot;/assets/HTMLEditorFieldTest/f5c7c2f814/example.jpg&quot; width=&quot;10&quot; height=&quot;20&quot; id=&quot;{$fileID}&quot;]" />


EOS
            ,
            $readonlyContent->getValue()
        );
    }

    public static function provideTestFormattedValueEntities()
    {
        return [
            "ampersand" => [
                "The company &amp; partners",
                "The company &amp; partners"
            ],
            "double ampersand" => [
                "The company &amp;amp; partners",
                "The company &amp;amp; partners"
            ],
            "left arrow and right arrow" => [
                "<p>&lt;strong&gt;The company &amp;amp; partners&lt;/strong&gt;</p>",
                "<p>&amp;lt;strong&amp;gt;The company &amp;amp; partners&amp;lt;/strong&amp;gt;</p>"
            ],
        ];
    }

    #[DataProvider('provideTestFormattedValueEntities')]
    public function testFormattedValueEntities(string $input, string $result)
    {
        $field = new HTMLEditorField("Content");
        $field->setValue($input);

        $this->assertEquals(
            $result,
            $field->obj('FormattedValueEntities')->forTemplate()
        );
    }

    public function testFieldConfigSanitization()
    {
        $obj = TestObject::create();
        $editor = HTMLEditorField::create('Content');
        $validElements = ['p' => true];
        $restrictedConfig = HTMLEditorConfig::get('restricted');
        $restrictedConfig->setElementRulesFromArray($validElements);
        $editor->setEditorConfig($restrictedConfig);

        $expectedHtmlString = '<p>standard text</p>Header';
        $htmlValue = '<p>standard text</p><table><th><tr><td>Header</td></tr></th><tbody></tbody></table>';
        $editor->setValue($htmlValue);
        $editor->saveInto($obj);
        $this->assertEquals($expectedHtmlString, $obj->Content, 'Table is not removed');

        $defaultConfig = HTMLEditorConfig::get('default');
        $editor->setEditorConfig($defaultConfig);

        $editor->setValue($htmlValue);
        $editor->saveInto($obj);
        $this->assertEquals($htmlValue, $obj->Content, 'Table is removed');
    }
}
