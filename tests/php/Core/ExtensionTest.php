<?php

namespace SilverStripe\Core\Tests;

use BadMethodCallException;
use ReflectionClass;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Tests\ExtensionTest\NamedExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class ExtensionTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset extra_methods so that when we set NamedExtension to null it re-evaluates which methods are available
        $reflectionClass = new ReflectionClass(DataObject::class);
        $reflectionClass->setStaticPropertyValue('extra_methods', []);
        // Add named extension config like we would in yaml
        Config::modify()->merge(DataObject::class, 'extensions', ['NamedExtension' => NamedExtension::class]);
    }

    public function testHasNamedExtension()
    {
        $this->assertTrue(DataObject::has_extension(NamedExtension::class));
        $instance = new DataObject();
        $this->assertTrue($instance->hasMethod('getTestValue'));
        $this->assertSame('test', $instance->getTestValue());
    }

    public function testRemoveNamedExtension()
    {
        Config::modify()->merge(DataObject::class, 'extensions', ['NamedExtension' => null]);
        $this->assertFalse(DataObject::has_extension(NamedExtension::class));
        $instance = new DataObject();
        $this->assertFalse($instance->hasMethod('getTestValue'));
    }

    public function testRemoveNamedExtensionException()
    {
        Config::modify()->merge(DataObject::class, 'extensions', ['NamedExtension' => null]);
        $instance = new DataObject();
        $this->expectException(BadMethodCallException::class);
        $instance->getTestValue();
    }
}
