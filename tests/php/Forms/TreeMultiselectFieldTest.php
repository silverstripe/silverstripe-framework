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
    protected static $fixture_file = 'TreeDropdownFieldTest.yml';

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

    protected function setUp(): void
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
            $this->folders ?? []
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
        $this->assertSame($fieldId, $schemaStateDefaults['id']);
        $this->assertSame($this->fieldName, $schemaStateDefaults['name']);
        $this->assertSame('unchanged', $schemaStateDefaults['value']);

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertSame($fieldId, $schemaDataDefaults['id']);
        $this->assertSame($this->fieldName, $schemaDataDefaults['name']);
        $this->assertSame('text', $schemaDataDefaults['type']);
        $this->assertSame('SingleSelect', $schemaDataDefaults['schemaType']);
        $this->assertSame('TreeDropdownField', $schemaDataDefaults['component']);
        $this->assertSame(sprintf('%s_Holder', $fieldId), $schemaDataDefaults['holderId']);
        $this->assertSame('Test tree', $schemaDataDefaults['title']);
        $this->assertSame('treemultiselect multiple searchable', $schemaDataDefaults['extraClass']);
        $this->assertSame('field/TestTree/tree', $schemaDataDefaults['data']['urlTree']);
        $this->assertSame(true, $schemaDataDefaults['data']['showSearch']);
        $this->assertSame('(Search or choose File)', $schemaDataDefaults['data']['emptyString']);
        $this->assertSame(false, $schemaDataDefaults['data']['hasEmptyDefault']);
        $this->assertSame(true, $schemaDataDefaults['data']['multiple']);

        $items = $field->getItems();
        $this->assertCount(0, $items, 'there must be no items selected');

        $html = $field->Field();
        $this->assertStringContainsString($field->ID(), $html);
        $this->assertStringContainsString('unchanged', $html);
    }


    /**
     * Test the field with some values set
     */
    public function testChanged()
    {
        $field = $this->field;

        $field->setValue($this->fieldValue);

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertSame($field->ID(), $schemaStateDefaults['id']);
        $this->assertSame('TestTree', $schemaStateDefaults['name']);
        $this->assertSame($this->folderIds, $schemaStateDefaults['value']);

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
        $this->assertSame($field->ID(), $schemaStateDefaults['id']);
        $this->assertSame('TestTree', $schemaStateDefaults['name']);
        $this->assertSame('unchanged', $schemaStateDefaults['value']);

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertSame($field->ID(), $schemaDataDefaults['id']);
        $this->assertSame($this->fieldName, $schemaDataDefaults['name']);
        $this->assertSame('text', $schemaDataDefaults['type']);
        $this->assertSame('SingleSelect', $schemaDataDefaults['schemaType']);
        $this->assertSame('TreeDropdownField', $schemaDataDefaults['component']);
        $this->assertSame(sprintf('%s_Holder', $field->ID()), $schemaDataDefaults['holderId']);
        $this->assertSame('Test tree', $schemaDataDefaults['title']);
        $this->assertSame('treemultiselectfield_readonly multiple searchable', $schemaDataDefaults['extraClass']);
        $this->assertSame('field/TestTree/tree', $schemaDataDefaults['data']['urlTree']);
        $this->assertSame(true, $schemaDataDefaults['data']['showSearch']);
        $this->assertSame('(Search or choose File)', $schemaDataDefaults['data']['emptyString']);
        $this->assertSame(false, $schemaDataDefaults['data']['hasEmptyDefault']);
        $this->assertSame(true, $schemaDataDefaults['data']['multiple']);

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
        $this->assertSame($field->ID(), $schemaStateDefaults['id']);
        $this->assertSame('TestTree', $schemaStateDefaults['name']);
        $this->assertSame($this->folderIds, $schemaStateDefaults['value']);

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertSame($field->ID(), $schemaDataDefaults['id']);
        $this->assertSame($this->fieldName, $schemaDataDefaults['name']);
        $this->assertSame('text', $schemaDataDefaults['type']);
        $this->assertSame('SingleSelect', $schemaDataDefaults['schemaType']);
        $this->assertSame('TreeDropdownField', $schemaDataDefaults['component']);
        $this->assertSame(sprintf('%s_Holder', $field->ID()), $schemaDataDefaults['holderId']);
        $this->assertSame('Test tree', $schemaDataDefaults['title']);
        $this->assertSame('treemultiselectfield_readonly multiple searchable', $schemaDataDefaults['extraClass']);
        $this->assertSame('field/TestTree/tree', $schemaDataDefaults['data']['urlTree']);
        $this->assertSame(true, $schemaDataDefaults['data']['showSearch']);
        $this->assertSame('(Search or choose File)', $schemaDataDefaults['data']['emptyString']);
        $this->assertSame(false, $schemaDataDefaults['data']['hasEmptyDefault']);
        $this->assertSame(true, $schemaDataDefaults['data']['multiple']);

        $items = $field->getItems();
        $this->assertCount(2, $items, 'there must be exactly 2 selected items');

        $html = $field->Field();
        $this->assertStringContainsString($field->ID(), $html);
        $this->assertStringContainsString($this->fieldValue, $html);
    }

    public function testGetItems()
    {
        // Default items scaffolded from 'unchanged' value (empty)
        $field = $this->field;
        $this->assertListEquals(
            [],
            $field->getItems()
        );

        $expectedItem = array_map(
            function ($folder) {
                return [
                    'Filename' => $folder->Filename,
                ];
            },
            $this->loadFolders() ?? []
        );

        // Set list of items by array of ids
        $field->setValue($this->folderIds);
        $this->assertListEquals(
            $expectedItem,
            $field->getItems()
        );

        // Set list of items by comma separated ids
        $field->setValue($this->fieldValue);
        $this->assertListEquals(
            $expectedItem,
            $field->getItems()
        );

        // Handle legacy empty value (form submits 'unchanged')
        $field->setValue('unchanged');
        $this->assertListEquals(
            [],
            $field->getItems()
        );

        // Andle empty string none value
        $field->setValue('');
        $this->assertListEquals(
            [],
            $field->getItems()
        );
    }
}
