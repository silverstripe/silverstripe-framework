<?php

namespace SilverStripe\Core\Tests\Injector;

use SilverStripe\Core\Injector\AopProxyService;
use SilverStripe\Core\Tests\Injector\AopProxyServiceTest\BeforeAfterCallTestAspect;
use SilverStripe\Core\Tests\Injector\AopProxyServiceTest\ProxyTestObject;
use SilverStripe\Dev\SapphireTest;

/**
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class AopProxyServiceTest extends SapphireTest
{
    public function testBeforeMethodsCalled()
    {
        $proxy = new AopProxyService();
        $aspect = new BeforeAfterCallTestAspect();
        $proxy->beforeCall = [
            'myMethod' => $aspect
        ];

        $proxy->proxied = new ProxyTestObject();

        $result = $proxy->myMethod();

        $this->assertEquals('myMethod', $aspect->called);
        $this->assertEquals(42, $result);
    }

    public function testBeforeMethodBlocks()
    {
        $proxy = new AopProxyService();
        $aspect = new BeforeAfterCallTestAspect();
        $aspect->block = true;

        $proxy->beforeCall = [
            'myMethod' => $aspect
        ];

        $proxy->proxied = new ProxyTestObject();

        $result = $proxy->myMethod();

        $this->assertEquals('myMethod', $aspect->called);

        // the actual underlying method will NOT have been called
        $this->assertNull($result);

        // set up an alternative return value
        $aspect->alternateReturn = 84;

        $result = $proxy->myMethod();

        $this->assertEquals('myMethod', $aspect->called);

        // the actual underlying method will NOT have been called,
        // instead the alternative return value
        $this->assertEquals(84, $result);
    }

    public function testAfterCall()
    {
        $proxy = new AopProxyService();
        $aspect = new BeforeAfterCallTestAspect();

        $proxy->afterCall = [
            'myMethod' => $aspect
        ];

        $proxy->proxied = new ProxyTestObject();

        $aspect->modifier = function ($value) {
            return $value * 2;
        };

        $result = $proxy->myMethod();
        $this->assertEquals(84, $result);
    }
}
