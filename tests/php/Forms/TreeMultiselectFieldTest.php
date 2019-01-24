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

    protected function setUp()
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
        $this->assertArraySubset(
            [
                'id' => $fieldId,
                'name' => $this->fieldName,
                'value' => 'unchanged'
            ],
            $schemaStateDefaults,
            true
        );

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertArraySubset(
            [
                'id' => $fieldId,
                'name' => $this->fieldName,
                'type' => 'text',
                'schemaType' => 'SingleSelect',
                'component' => 'TreeDropdownField',
                'holderId' => sprintf('%s_Holder', $fieldId),
                'title' => 'Test tree',
                'extraClass' => 'treemultiselect multiple searchable',
                'data' => [
                    'urlTree' => 'field/TestTree/tree',
                    'showSearch' => true,
                    'emptyString' => '(Choose File)',
                    'hasEmptyDefault' => false,
                    'multiple' => true
                ]
            ],
            $schemaDataDefaults,
            true
        );

        $items = $field->getItems();
        $this->assertCount(0, $items, 'there must be no items selected');

        $html = $field->Field();
        $this->assertContains($field->ID(), $html);
        $this->assertContains('unchanged', $html);
    }


    /**
     * Test the field with some values set
     */
    public function testChanged()
    {
        $field = $this->field;

        $field->setValue($this->fieldValue);

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArraySubset(
            [
                'id' => $field->ID(),
                'name' => 'TestTree',
                'value' => $this->folderIds
            ],
            $schemaStateDefaults,
            true
        );

        $items = $field->getItems();
        $this->assertCount(2, $items, 'there must be exactly 2 items selected');

        $html = $field->Field();
        $this->assertContains($field->ID(), $html);
        $this->assertContains($this->fieldValue, $html);
    }

    /**
     * Test empty field in readonly mode
     */
    public function testEmptyReadonly()
    {
        $field = $this->field->performReadonlyTransformation();

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArraySubset(
            [
                'id' => $field->ID(),
                'name' => 'TestTree',
                'value' => 'unchanged'
            ],
            $schemaStateDefaults,
            true
        );

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertArraySubset(
            [
                'id' => $field->ID(),
                'name' => $this->fieldName,
                'type' => 'text',
                'schemaType' => 'SingleSelect',
                'component' => 'TreeDropdownField',
                'holderId' => sprintf('%s_Holder', $field->ID()),
                'title' => 'Test tree',
                'extraClass' => 'treemultiselectfield_readonly multiple  searchable',
                'data' => [
                    'urlTree' => 'field/TestTree/tree',
                    'showSearch' => true,
                    'emptyString' => '(Choose File)',
                    'hasEmptyDefault' => false,
                    'multiple' => true
                ]
            ],
            $schemaDataDefaults,
            true
        );

        $items = $field->getItems();
        $this->assertCount(0, $items, 'there must be 0 selected items');

        $html = $field->Field();
        $this->assertContains($field->ID(), $html);
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
        $this->assertArraySubset(
            [
                'id' => $field->ID(),
                'name' => 'TestTree',
                'value' => $this->folderIds
            ],
            $schemaStateDefaults,
            true
        );

        $schemaDataDefaults = $field->getSchemaDataDefaults();
        $this->assertArraySubset(
            [
                'id' => $field->ID(),
                'name' => $this->fieldName,
                'type' => 'text',
                'schemaType' => 'SingleSelect',
                'component' => 'TreeDropdownField',
                'holderId' => sprintf('%s_Holder', $field->ID()),
                'title' => 'Test tree',
                'extraClass' => 'treemultiselectfield_readonly multiple  searchable',
                'data' => [
                    'urlTree' => 'field/TestTree/tree',
                    'showSearch' => true,
                    'emptyString' => '(Choose File)',
                    'hasEmptyDefault' => false,
                    'multiple' => true
                ]
            ],
            $schemaDataDefaults,
            true
        );

        $items = $field->getItems();
        $this->assertCount(2, $items, 'there must be exactly 2 selected items');

        $html = $field->Field();
        $this->assertContains($field->ID(), $html);
        $this->assertContains($this->fieldValue, $html);
    }
}
