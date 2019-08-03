<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldLazyLoader;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

class GridFieldLazyLoaderTest extends SapphireTest
{
    /**
     * @var ArrayList
     */
    protected $list;

    /**
     * @var GridField
     */
    protected $gridField;

    /**
     * @var GridFieldLazyLoader
     */
    protected $component;

    protected static $fixture_file = 'GridFieldTest.yml';

    protected static $extra_dataobjects = [
        Permissions::class,
        Cheerleader::class,
        Player::class,
        Team::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->list = DataList::create(Team::class);
        $this->component = new GridFieldLazyLoader();
        $config = GridFieldConfig_RecordEditor::create()->addComponent($this->component);
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
    }

    public function testGetManipulatedDataWithoutHeader()
    {
        $gridField = $this->getHeaderlessGridField();
        $this->assertCount(
            0,
            $this->component->getManipulatedData($gridField, $this->list)->toArray(),
            'GridFieldLazyLoader::getManipulatedData should return an empty list if the X-Pjax is unset'
        );
    }

    public function testGetManipulatedDataWithoutTabSet()
    {
        $gridField = $this->getOutOfTabSetGridField();
        $this->assertSameSize(
            $this->list,
            $this->component->getManipulatedData($gridField, $this->list)->toArray(),
            'GridFieldLazyLoader::getManipulatedData should return a proper list if GridField is not in a tab'
        );
    }

    public function testGetManipulatedDataNonLazy()
    {
        $gridField = $this->getNonLazyGridField();
        $this->assertSameSize(
            $this->list,
            $this->component->getManipulatedData($gridField, $this->list),
            'GridFieldLazyLoader::getManipulatedData should return a proper list if GridField'
            . ' is in a tab with the pajax header'
        );
    }

    public function testGetHTMLFragmentsWithoutHeader()
    {
        $gridField = $this->getHeaderlessGridField();
        $actual = $this->component->getHTMLFragments($gridField);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field--lazy-loadable', $gridField->extraClass());
        $this->assertNotContains('grid-field--lazy-loaded', $gridField->extraClass());
    }

    public function testGetHTMLFragmentsWithoutTabSet()
    {
        $gridField = $this->getOutOfTabSetGridField();
        $actual = $this->component->getHTMLFragments($gridField);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field--lazy-loaded', $gridField->extraClass());
        $this->assertNotContains('grid-field--lazy-loadable', $gridField->extraClass());
    }

    public function testGetHTMLFragmentsNonLazy()
    {
        $gridField = $this->getNonLazyGridField();
        $actual = $this->component->getHTMLFragments($gridField);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field--lazy-loaded', $gridField->extraClass());
        $this->assertNotContains('grid-field--lazy-loadable', $gridField->extraClass());
    }


    public function testReadOnlyGetManipulatedDataWithoutHeader()
    {
        $gridField = $this->makeGridFieldReadonly($this->getHeaderlessGridField());
        $this->assertCount(
            0,
            $this->component->getManipulatedData($gridField, $this->list)->toArray(),
            'Readonly GridFieldLazyLoader::getManipulatedData should return an empty list if the X-Pjax'
            . ' is unset'
        );
    }

    public function testReadOnlyGetManipulatedDataWithoutTabSet()
    {
        $gridField = $this->makeGridFieldReadonly($this->getOutOfTabSetGridField());
        $this->assertSameSize(
            $this->list,
            $this->component->getManipulatedData($gridField, $this->list)->toArray(),
            'Readonly GridFieldLazyLoader::getManipulatedData should return a proper list if GridField is'
            . ' not in a tab'
        );
    }

    public function testReadOnlyGetManipulatedDataNonLazy()
    {
        $gridField = $this->makeGridFieldReadonly($this->getNonLazyGridField());
        $this->assertSameSize(
            $this->list,
            $this->component->getManipulatedData($gridField, $this->list),
            'Readonly GridFieldLazyLoader::getManipulatedData should return a proper list if GridField is in'
            . ' a tab with the pajax header'
        );
    }

    public function testReadOnlyGetHTMLFragmentsWithoutHeader()
    {
        $gridField = $this->makeGridFieldReadonly($this->getHeaderlessGridField());
        $actual = $this->component->getHTMLFragments($gridField);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field--lazy-loadable', $gridField->extraClass());
        $this->assertNotContains('grid-field--lazy-loaded', $gridField->extraClass());
    }

    public function testReadOnlyGetHTMLFragmentsWithoutTabSet()
    {
        $gridField = $this->makeGridFieldReadonly($this->getOutOfTabSetGridField());
        $actual = $this->component->getHTMLFragments($gridField);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field--lazy-loaded', $gridField->extraClass());
        $this->assertNotContains('grid-field--lazy-loadable', $gridField->extraClass());
    }

    public function testReadOnlyGetHTMLFragmentsNonLazy()
    {
        $gridField = $this->makeGridFieldReadonly($this->getNonLazyGridField());
        $actual = $this->component->getHTMLFragments($gridField);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field--lazy-loaded', $gridField->extraClass());
        $this->assertNotContains('grid-field--lazy-loadable', $gridField->extraClass());
    }

    /**
     * This GridField will be lazy because it doesn't have a `X-Pjax` header.
     * @return GridField
     */
    private function getHeaderlessGridField()
    {
        $this->gridField->setRequest(new HTTPRequest('GET', 'admin/pages/edit/show/9999'));
        $fieldList = FieldList::create(new TabSet('Root', new Tab('Main')));
        $fieldList->addFieldToTab('Root.GridField', $this->gridField);
        Form::create(null, 'Form', $fieldList, FieldList::create());
        return $this->gridField;
    }

    /**
     * This GridField will not be lazy because it's in not in a tab set.
     * @return GridField
     */
    private function getOutOfTabSetGridField()
    {
        $r = new HTTPRequest('POST', 'admin/pages/edit/EditForm/9999/field/testfield');
        $r->addHeader('X-Pjax', 'CurrentField');
        $this->gridField->setRequest($r);
        $fieldList = new FieldList($this->gridField);
        Form::create(null, 'Form', $fieldList, FieldList::create());
        return $this->gridField;
    }

    /**
     * This gridfield will not be lazy, because it has `X-Pjax` header equal to `CurrentField`
     * @return GridField
     */
    private function getNonLazyGridField()
    {
        $r = new HTTPRequest('POST', 'admin/pages/edit/EditForm/9999/field/testfield');
        $r->addHeader('X-Pjax', 'CurrentField');
        $this->gridField->setRequest($r);
        $fieldList = new FieldList(new TabSet('Root', new Tab('Main')));
        $fieldList->addFieldToTab('Root', $this->gridField);
        Form::create(null, 'Form', $fieldList, FieldList::create());
        return $this->gridField;
    }

    /**
     * Perform a readonly transformation on our GridField's Form and return the ReadOnly GridField.
     *
     * We need to make sure the LazyLoader component still works after our GridField has been made readonly.
     *
     * @param GridField $gridField
     * @return GridField
     */
    private function makeGridFieldReadonly(GridField $gridField)
    {
        $form = $gridField->getForm()->makeReadonly();
        $fields = $form->Fields()->dataFields();
        foreach ($fields as $field) {
            if ($field->getName() === 'testfield') {
                return $field;
            }
        }
    }
}
