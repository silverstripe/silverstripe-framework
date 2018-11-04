<?php
namespace SilverStripe\Core\Tests\Injector;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\SilverStripeServiceConfigurationLocator;
use SilverStripe\Core\Tests\Injector\MethodInjectorTest\AlternateTestDependency;
use SilverStripe\Core\Tests\Injector\MethodInjectorTest\InjectableConstructor;
use SilverStripe\Core\Tests\Injector\MethodInjectorTest\InjectableConstructorTagged;
use SilverStripe\Core\Tests\Injector\MethodInjectorTest\TestDependency;
use SilverStripe\Dev\SapphireTest;

class MethodInjectorTest extends SapphireTest
{
    public function testSimpleInjectableConstructors()
    {
        /** @var InjectableConstructor $object */
        $object = Injector::inst()->get(InjectableConstructor::class);
        $this->assertInstanceOf(TestDependency::class, $object->getProtectedDependency());
    }

    public function testInjectableConstructorsWithAdditionalArgs()
    {
        $extraParams = [1, 2];
        /** @var InjectableConstructor $object */
        $object = Injector::inst()->get(InjectableConstructor::class, true, $extraParams);
        $this->assertInstanceOf(TestDependency::class, $object->getProtectedDependency());
        $this->assertSame($extraParams, $object->getAdditionalParams());
    }

    public function testTaggedInjectableConstructors()
    {
        Config::nest()->set(Injector::class, TestDependency::class . '.testTag', AlternateTestDependency::class);
        // Reset the config locator to dump any existing caches...
        Injector::inst()->setConfigLocator(new SilverStripeServiceConfigurationLocator());


        /** @var InjectableConstructor $object */
        $object = Injector::inst()->get(InjectableConstructorTagged::class);
        $this->assertInstanceOf(AlternateTestDependency::class, $object->getProtectedDependency());
    }
}
