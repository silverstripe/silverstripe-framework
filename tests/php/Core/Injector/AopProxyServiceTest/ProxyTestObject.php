<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\Injector\AopProxyServiceTest;

class ProxyTestObject
{
    public function myMethod()
    {
        return 42;
    }
}
