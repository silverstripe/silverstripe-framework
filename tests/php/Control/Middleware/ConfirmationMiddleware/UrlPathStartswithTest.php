<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Middleware\ConfirmationMiddleware\UrlPathStartswith;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Confirmation\Item;

class UrlPathStartswithTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    public function testPath()
    {
        $url = new UrlPathStartswith('test/path_01');
        $this->assertEquals('test/path_01/', $url->getPath());

        $url->setPath('test/path_02');
        $this->assertEquals('test/path_02/', $url->getPath());
    }

    public function testBypass()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $url = new UrlPathStartswith('dev');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('dev/');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('dev/build');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('dev/build/');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('de');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('dev/buil');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('Dev');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new UrlPathStartswith('dev/builD');
        $this->assertFalse($url->checkRequestForBypass($request));
    }

    public function testConfirmationItem()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $url = new UrlPathStartswith('dev');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('dev/');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('dev/build');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('dev/build/');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('de');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('dev/buil');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('Dev/build');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswith('dev/builD');
        $this->assertNull($url->getRequestConfirmationItem($request));
    }
}
