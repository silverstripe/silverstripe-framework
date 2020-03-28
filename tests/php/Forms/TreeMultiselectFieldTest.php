<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormTemplateHelper;
use SilverStripe\Forms\TreeMultiselectField;
use SilverStripe\ORM\Tests\HierarchyTest\TestObject;
use SilverStripe\View\SSViewer;

class TreeMultiselectFieldTest extends SapphireTest
{
    protected static $fixture_file = 'TreeMultiselectFieldTest.yml';

    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected $formId = 'TheFormID';
    protected $fieldName = 'TestTree';

    /**
     * Mock object of a generic form
     *
     * @var Form
     */
    protected $form;

    /**
     * Instance of the TreeMultiselectField
     *
     * @var TreeMultiselectField
     */
    protected $field;

    /**
     * The File objects of folders loaded from the fixture
     *
     * @var File[]
     */
    protected $folders;

    /**
     * The array of folder ids
     *
     * @var int[]
     */
    protected $folderIds;

    /**
     * Concatenated folder ids for use as a value for the field
     *
     * @var string
     */
    protected $fieldValue;

    protected function setUp() : void
    {
        parent::setUp();

        // Don't let other themes interfere with these tests
        SSViewer::set_themes([]);

        $this->form = $this->buildFormMock();
        $this->field = $this->buildField($this->form);
        $this->folders = $this->loadFolders();

        $this->folderIds = array_map(
            static function ($f) {
                return $f->ID;
            },
            $this->folders
        );
        $this->fieldValue = implode(',', $this->folderIds);
    }

    /**
     * Build a new mock object of a Form
     *
     * @return Form
     */
    protected function buildFormMock()
    {
        $form = $this->createMock(Form::class);

        $form->method('getTemplateHelper')
            ->willReturn(FormTemplateHelper::singleton());

        $form->method('getHTMLID')
            ->willReturn($this->formId);

        return $form;
    }

    /**
     * Build a new instance of TreeMultiselectField
     *
     * @param Form $form The field form
     *
     * @return TreeMultiselectField
     */
    protected function buildField(Form $form)
    {
        $field = new TreeMultiselectField($this->fieldName, 'Test tree', File::class);
        $field->setForm($form);

        return $field;
    }

    /**
     * Load several files from the fixtures and return them in an array
     *
     * @return File[]
     */
    protected function loadFolders()
    {
        $asdf = $this->objFromFixture(File::class, 'asdf');
        $subfolderfile1 = $this->objFromFixture(File::class, 'subfolderfile1');

        return [$asdf, $subfolderfile1];
    }

    /**
     * Test the TreeMultiselectField behaviour with no selected values
     */
    public function testEmpty()
    {
        $field = $this->field;

        $fieldId = $field->ID();
        $this->assertEquals($fieldId, sprintf('%s_%s', $this->formId, $this->fieldName));

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArrayHasKey('id', $schemaStateDefaults);
        $this->assertEquals($fieldId, $schemaStateDefaults['id']);
        $this->assertArrayHasKey('name', $schemaStateDefaults);
        $this->assertEquals($this->fieldName, $schemaStateDefaults['name']);
        $this->assertArrayHasKey('value', $schemaStateDefaults);
        $this->assertNull($schemaStateDefaults['value']);

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertArrayHasKey('id', $schemaDataDefaults);
        $this->assertEquals($fieldId, $schemaDataDefaults['id']);
        $this->assertArrayHasKey('name', $schemaDataDefaults);
        $this->assertEquals($this->fieldName, $schemaDataDefaults['name']);
        $this->assertArrayHasKey('type', $schemaDataDefaults);
        $this->assertEquals('text', $schemaDataDefaults['type']);
        $this->assertArrayHasKey('schemaType', $schemaDataDefaults);
        $this->assertEquals('SingleSelect', $schemaDataDefaults['schemaType']);
        $this->assertArrayHasKey('component', $schemaDataDefaults);
        $this->assertEquals('TreeDropdownField', $schemaDataDefaults['component']);
        $this->assertArrayHasKey('holderId', $schemaDataDefaults);
        $this->assertEquals(sprintf('%s_Holder', $fieldId), $schemaDataDefaults['holderId']);
        $this->assertArrayHasKey('title', $schemaDataDefaults);
        $this->assertEquals('Test tree', $schemaDataDefaults['title']);
        $this->assertArrayHasKey('extraClass', $schemaDataDefaults);
        $this->assertEquals('treemultiselect multiple searchable', $schemaDataDefaults['extraClass']);
        $this->assertArrayHasKey('data', $schemaDataDefaults);
        $this->assertEquals([
            'urlTree' => 'field/TestTree/tree',
            'showSearch' => true,
            'emptyString' => '(Search or choose File)',
            'hasEmptyDefault' => false,
            'multiple' => true
        ], $schemaDataDefaults['data']);

        $items = $field->getItems();
        $this->assertCount(0, $items, 'there must be no items selected');

        $html = $field->Field();
        $this->assertStringContainsString($field->ID(), $html);
    }


    /**
     * Test the field with some values set
     */
    public function testChanged()
    {
        $field = $this->field;

        $field->setValue($this->fieldValue);

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArrayHasKey('id', $schemaStateDefaults);
        $this->assertEquals($field->ID(), $schemaStateDefaults['id']);
        $this->assertArrayHasKey('name', $schemaStateDefaults);
        $this->assertEquals('TestTree', $schemaStateDefaults['name']);
        $this->assertArrayHasKey('value', $schemaStateDefaults);
        $this->assertEquals($this->folderIds, $schemaStateDefaults['value']);

        $items = $field->getItems();
        $this->assertCount(2, $items, 'there must be exactly 2 items selected');

        $html = $field->Field();
        $this->assertStringContainsString($field->ID(), $html);
        $this->assertStringContainsString($this->fieldValue, $html);
    }

    /**
     * Test empty field in readonly mode
     */
    public function testEmptyReadonly()
    {
        $field = $this->field->performReadonlyTransformation();

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArrayHasKey('id', $schemaStateDefaults);
        $this->assertEquals($field->ID(), $schemaStateDefaults['id']);
        $this->assertArrayHasKey('name', $schemaStateDefaults);
        $this->assertEquals('TestTree', $schemaStateDefaults['name']);
        $this->assertArrayHasKey('value', $schemaStateDefaults);
        $this->assertNull($schemaStateDefaults['value']);

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertArrayHasKey('id', $schemaDataDefaults);
        $this->assertEquals($field->ID(), $schemaDataDefaults['id']);
        $this->assertArrayHasKey('name', $schemaDataDefaults);
        $this->assertEquals($this->fieldName, $schemaDataDefaults['name']);
        $this->assertArrayHasKey('type', $schemaDataDefaults);
        $this->assertEquals('text', $schemaDataDefaults['type']);
        $this->assertArrayHasKey('schemaType', $schemaDataDefaults);
        $this->assertEquals('SingleSelect', $schemaDataDefaults['schemaType']);
        $this->assertArrayHasKey('component', $schemaDataDefaults);
        $this->assertEquals('TreeDropdownField', $schemaDataDefaults['component']);
        $this->assertArrayHasKey('holderId', $schemaDataDefaults);
        $this->assertEquals(sprintf('%s_Holder', $field->ID()), $schemaDataDefaults['holderId']);
        $this->assertArrayHasKey('title', $schemaDataDefaults);
        $this->assertEquals('Test tree', $schemaDataDefaults['title']);
        $this->assertArrayHasKey('extraClass', $schemaDataDefaults);
        $this->assertEquals('treemultiselectfield_readonly multiple  searchable', $schemaDataDefaults['extraClass']);
        $this->assertArrayHasKey('data', $schemaDataDefaults);
        $this->assertEquals([
            'urlTree' => 'field/TestTree/tree',
            'showSearch' => true,
            'emptyString' => '(Search or choose File)',
            'hasEmptyDefault' => false,
            'multiple' => true
        ], $schemaDataDefaults['data']);

        $items = $field->getItems();
        $this->assertCount(0, $items, 'there must be 0 selected items');

        $html = $field->Field();
        $this->assertStringContainsString($field->ID(), $html);
    }

    /**
     * Test changed field in readonly mode
     */
    public function testChangedReadonly()
    {
        $field = $this->field;
        $field->setValue($this->fieldValue);
        $field = $field->performReadonlyTransformation();

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArrayHasKey('id', $schemaStateDefaults);
        $this->assertEquals($field->ID(), $schemaStateDefaults['id']);
        $this->assertArrayHasKey('name', $schemaStateDefaults);
        $this->assertEquals('TestTree', $schemaStateDefaults['name']);
        $this->assertArrayHasKey('value', $schemaStateDefaults);
        $this->assertEquals($this->folderIds, $schemaStateDefaults['value']);

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertArrayHasKey('id', $schemaDataDefaults);
        $this->assertEquals($field->ID(), $schemaDataDefaults['id']);
        $this->assertArrayHasKey('name', $schemaDataDefaults);
        $this->assertEquals($this->fieldName, $schemaDataDefaults['name']);
        $this->assertArrayHasKey('type', $schemaDataDefaults);
        $this->assertEquals('text', $schemaDataDefaults['type']);
        $this->assertArrayHasKey('schemaType', $schemaDataDefaults);
        $this->assertEquals('SingleSelect', $schemaDataDefaults['schemaType']);
        $this->assertArrayHasKey('component', $schemaDataDefaults);
        $this->assertEquals('TreeDropdownField', $schemaDataDefaults['component']);
        $this->assertArrayHasKey('holderId', $schemaDataDefaults);
        $this->assertEquals(sprintf('%s_Holder', $field->ID()), $schemaDataDefaults['holderId']);
        $this->assertArrayHasKey('title', $schemaDataDefaults);
        $this->assertEquals('Test tree', $schemaDataDefaults['title']);
        $this->assertArrayHasKey('extraClass', $schemaDataDefaults);
        $this->assertEquals('treemultiselectfield_readonly multiple  searchable', $schemaDataDefaults['extraClass']);
        $this->assertArrayHasKey('data', $schemaDataDefaults);
        $this->assertEquals([
            'urlTree' => 'field/TestTree/tree',
            'showSearch' => true,
            'emptyString' => '(Search or choose File)',
            'hasEmptyDefault' => false,
            'multiple' => true
        ], $schemaDataDefaults['data']);

        $items = $field->getItems();
        $this->assertCount(2, $items, 'there must be exactly 2 selected items');

        $html = $field->Field();
        $this->assertStringContainsString($field->ID(), $html);
        $this->assertStringContainsString($this->fieldValue, $html);
    }
}
