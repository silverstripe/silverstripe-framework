<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Middleware\ConfirmationMiddleware\Url;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Confirmation\Item;

class UrlTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    public function testPath()
    {
        $url = new Url('test/path_01');
        $this->assertEquals('test/path_01/', $url->getPath());

        $url->setPath('test/path_02');
        $this->assertEquals('test/path_02/', $url->getPath());
    }

    public function testHttpMethods()
    {
        $url = new Url('/', ['PUT', 'DELETE']);
        $this->assertCount(2, $url->getHttpMethods());
        $this->assertContains('DELETE', $url->getHttpMethods());
        $this->assertContains('PUT', $url->getHttpMethods());

        $url->addHttpMethods('GET', 'POST');
        $this->assertCount(4, $url->getHttpMethods());
        $this->assertContains('DELETE', $url->getHttpMethods());
        $this->assertContains('GET', $url->getHttpMethods());
        $this->assertContains('POST', $url->getHttpMethods());
        $this->assertContains('PUT', $url->getHttpMethods());
    }

    public function testBypass()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $url = new Url('dev');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new Url('dev/build');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new Url('dev/build/');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new Url('dev/build', 'GET');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new Url('dev/build', 'POST');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new Url('dev/build', ['GET', 'POST']);
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new Url('dev/build', null, []);
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new Url('dev/build', null, ['flush' => null]);
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new Url('dev/build', null, ['flush' => '1']);
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new Url('dev/build', null, ['flush' => 'all']);
        $this->assertTrue($url->checkRequestForBypass($request));
    }

    public function testConfirmationItem()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $url = new Url('dev');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build/');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', 'GET');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', 'POST');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', ['GET', 'POST']);
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', null, []);
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', null, ['flush' => null]);
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', null, ['flush' => '1']);
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new Url('dev/build', null, ['flush' => 'all']);
        $this->assertNotNull($url->getRequestConfirmationItem($request));
    }
}
