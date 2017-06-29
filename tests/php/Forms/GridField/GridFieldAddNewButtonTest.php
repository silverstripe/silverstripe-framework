<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Person;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\PeopleGroup;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Category;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\TestController;
use SilverStripe\ORM\SS_List;

class GridFieldAddNewButtonTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldDetailFormTest.yml';

    protected static $extra_dataobjects = [
        Person::class,
        PeopleGroup::class,
        Category::class,
    ];

    public function testButtonPassesParentContextToSingletonWhenRelationListIsUsed()
    {
        $group = $this->objFromFixture(PeopleGroup::class, 'group');
        $list = $group->People();
        $this->mockSingleton(Person::class)
            ->expects($this->once())
            ->method('canCreate')
            ->with(
                $this->anything(),
                $this->callback(function ($arg) use ($group) {
                    return isset($arg['Parent']) && $arg['Parent']->ID == $group->ID;
                })
            );

        $this->mockButtonFragments($list, $group);
    }

    public function testButtonPassesNoParentContextToSingletonWhenRelationListIsNotUsed()
    {
        $group = $this->objFromFixture(PeopleGroup::class, 'group');
        $this->mockSingleton(Person::class)
            ->expects($this->once())
            ->method('canCreate')
            ->with(
                $this->anything(),
                $this->callback(function ($arg) {
                    return !isset($arg['Parent']);
                })
            );

        $this->mockButtonFragments(Person::get(), $group);
    }

    public function testButtonPassesNoParentContextToSingletonWhenNoParentRecordExists()
    {
        $group = $this->objFromFixture(PeopleGroup::class, 'group');
        $list = $group->People();

        $this->mockSingleton(Person::class)
            ->expects($this->once())
            ->method('canCreate')
            ->with(
                $this->anything(),
                $this->callback(function ($arg) {
                    return !isset($arg['Parent']);
                })
            );

        $this->mockButtonFragments($list, null);
    }

    protected function mockButtonFragments(SS_List $list, $parent = null)
    {
        $form = Form::create(
            new TestController(),
            'test',
            FieldList::create(
                $fakeGrid = GridField::create(
                    'dummy',
                    'dummy',
                    $list,
                    new GridFieldConfig(
                        $button = new GridFieldAddNewButton()
                    )
                )
            ),
            FieldList::create()
        );
        if ($parent) {
            $form->loadDataFrom($parent);
        }

        $button->getHTMLFragments($fakeGrid);
    }

    protected function mockSingleton($class)
    {
        $mock = $this->getMockBuilder($class)
            ->setMethods(['canCreate'])
            ->getMock();
        Injector::inst()->registerService($mock, $class);

        return $mock;
    }
}
