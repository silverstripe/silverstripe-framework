<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\Tests\GridField\GridFieldConfigTest\MyOtherComponent;
use SilverStripe\Forms\Tests\GridField\GridFieldConfigTest\MyComponent;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridField_URLHandler;

class GridFieldConfigTest extends SapphireTest
{

    public function testGetComponents()
    {
        $config = GridFieldConfig::create();
        $this->assertInstanceOf(ArrayList::class, $config->getComponents());
        $this->assertEquals($config->getComponents()->count(), 0);

        $config
            ->addComponent($c1 = new MyComponent())
            ->addComponent($c2 = new MyOtherComponent())
            ->addComponent($c3 = new MyOtherComponent());

        $this->assertEquals(
            new ArrayList(array($c1, $c2, $c3)),
            $config->getComponents()
        );
    }

    public function testGetComponentsByType()
    {
        $config = GridFieldConfig::create()
            ->addComponent($c1 = new MyComponent())
            ->addComponent($c2 = new MyOtherComponent())
            ->addComponent($c3 = new MyOtherComponent());

        $this->assertEquals(
            new ArrayList(array($c1)),
            $config->getComponentsByType(MyComponent::class)
        );
        $this->assertEquals(
            new ArrayList(array($c2, $c3)),
            $config->getComponentsByType(MyOtherComponent::class)
        );
        $this->assertEquals(
            new ArrayList(array($c1, $c2, $c3)),
            $config->getComponentsByType(GridField_URLHandler::class)
        );
        $this->assertEquals(
            new ArrayList(),
            $config->getComponentsByType('GridFieldConfigTest_UnknownComponent')
        );
    }

    public function testGetComponentByType()
    {
        $config = GridFieldConfig::create()
            ->addComponent($c1 = new MyComponent())
            ->addComponent($c2 = new MyOtherComponent())
            ->addComponent($c3 = new MyOtherComponent());

        $this->assertEquals(
            $c1,
            $config->getComponentByType(MyComponent::class)
        );
        $this->assertEquals(
            $c2,
            $config->getComponentByType(MyOtherComponent::class)
        );
        $this->assertNull(
            $config->getComponentByType('GridFieldConfigTest_UnknownComponent')
        );
    }

    public function testAddComponents()
    {
        $config = GridFieldConfig::create()
            ->addComponents(
                $c1 = new MyComponent(),
                $c2 = new MyOtherComponent()
            );

        $this->assertEquals(
            $c1,
            $config->getComponentByType(MyComponent::class)
        );
        $this->assertEquals(
            $c2,
            $config->getComponentByType(MyOtherComponent::class)
        );
    }

    public function testRemoveComponents()
    {
        $config = GridFieldConfig::create()
            ->addComponent($c1 = new MyComponent())
            ->addComponent($c2 = new MyComponent())
            ->addComponent($c3 = new MyOtherComponent())
            ->addComponent($c4 = new MyOtherComponent());

        $this->assertEquals(
            4,
            $config->getComponents()->count()
        );

        $config->removeComponent($c1);
        $this->assertEquals(
            3,
            $config->getComponents()->count()
        );

        $config->removeComponentsByType(MyComponent::class);
        $this->assertEquals(
            2,
            $config->getComponents()->count()
        );

        $config->removeComponentsByType(MyOtherComponent::class);
        $this->assertEquals(
            0,
            $config->getComponents()->count()
        );
    }

    /**
     * Test that components can be removed with an array of class names or interfaces
     */
    public function testRemoveMultipleComponents()
    {
        $config = GridFieldConfig::create()
            ->addComponent(new MyComponent)
            ->addComponent(new MyComponent)
            ->addComponent(new MyOtherComponent);

        $config->removeComponentsByType(
            [
                MyComponent::class,
                MyOtherComponent::class
            ]
        );

        $this->assertSame(0, $config->getComponents()->count());
    }
}
