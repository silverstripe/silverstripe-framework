<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Middleware\ConfirmationMiddleware\GetParameter;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Confirmation\Item;

class GetParameterTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    public function testName()
    {
        $rule = new GetParameter('name_01');
        $this->assertEquals('name_01', $rule->getName());

        $rule->setName('name_02');
        $this->assertEquals('name_02', $rule->getName());
    }

    public function testBypass()
    {
        $request = $this->buildRequestMock('test/path', ['parameterKey' => 'parameterValue']);

        $rule = new GetParameter('parameterKey_01');
        $this->assertFalse($rule->checkRequestForBypass($request));

        $rule->setName('parameterKey');
        $this->assertTrue($rule->checkRequestForBypass($request));
    }

    public function testConfirmationItem()
    {
        $request = $this->buildRequestMock('test/path', ['parameterKey' => 'parameterValue']);

        $rule = new GetParameter('parameterKey_01');
        $this->assertNull($rule->getRequestConfirmationItem($request));

        $rule->setName('parameterKey');
        $item = $rule->getRequestConfirmationItem($request);
        $this->assertNotNull($item);
        $this->assertInstanceOf(Item::class, $item);
    }
}
