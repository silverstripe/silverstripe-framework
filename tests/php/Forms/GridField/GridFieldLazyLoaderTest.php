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

    protected function setUp()
    {
        parent::setUp();
        $this->list = DataList::create(Team::class);
        $this->component = new GridFieldLazyLoader();
        $config = GridFieldConfig_RecordEditor::create()->addComponent($this->component);
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
    }

    public function testGetManipulatedDataWithoutHeader()
    {
        $gridFied = $this->getHeaderlessGridField();
        $this->assertCount(
            0,
            $this->component->getManipulatedData($gridFied, $this->list)->toArray(),
            'GridFieldLazyLoader::getManipulatedData should return an empty list if the X-Pjax is unset'
        );
    }
    public function testGetManipulatedDataWithoutTabSet()
    {
        $gridFied = $this->getOutOfTabSetGridField();
        $this->assertSameSize(
            $this->list,
            $this->component->getManipulatedData($gridFied, $this->list)->toArray(),
            'GridFieldLazyLoader::getManipulatedData should return a proper list if gridifield is not in a tab'
        );
    }

    public function testGetManipulatedDataNonLazy()
    {
        $gridFied = $this->getNonLazyGridField();
        $this->assertSameSize(
            $this->list,
            $this->component->getManipulatedData($gridFied, $this->list),
            'GridFieldLazyLoader::getManipulatedData should return a proper list if gridifield is in a tab with the pajax header'
        );
    }

    public function testGetHTMLFragmentsWithoutHeader()
    {
        $gridFied = $this->getHeaderlessGridField();
        $actual = $this->component->getHTMLFragments($gridFied);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field-lazy-loadable', $gridFied->extraClass());
        $this->assertNotContains('grid-field-lazy-loaded', $gridFied->extraClass());
    }

    public function testGetHTMLFragmentsWithoutTabSet()
    {
        $gridFied = $this->getOutOfTabSetGridField();
        $actual = $this->component->getHTMLFragments($gridFied);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field-lazy-loaded', $gridFied->extraClass());
        $this->assertNotContains('grid-field-lazy-loadable', $gridFied->extraClass());
    }

    public function testGetHTMLFragmentsNonLazy()
    {
        $gridFied = $this->getNonLazyGridField();
        $actual = $this->component->getHTMLFragments($gridFied);
        $this->assertEmpty($actual, 'getHTMLFragments should always return an array');
        $this->assertContains('grid-field-lazy-loaded', $gridFied->extraClass());
        $this->assertNotContains('grid-field-lazy-loadable', $gridFied->extraClass());
    }

    /**
     * This GridField will be lazy because it doesn't have a `X-Pjax` header.
     * @return GridField
     */
    private function getHeaderlessGridField()
    {
        $this->gridField->setRequest(new HTTPRequest('GET', 'admin/pages/edit/show/9999'));
        $fieldList = FieldList::create(new TabSet("Root", new Tab("Main")));
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
        $fieldList = new FieldList(new TabSet("Root", new Tab("Main")));
        $fieldList->addFieldToTab('Root', $this->gridField);
        Form::create(null, 'Form', $fieldList, FieldList::create());
        return $this->gridField;
    }
}
